<?php

namespace App\Services;

use App\Jobs\GenerateStudentCertificate;
use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\School;
use App\Models\StudentPkl;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;
use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateService
{
    /**
     * Konversi nilai angka ke predikat kelulusan PKL.
     */
    public function calculatePredicate(float $score): string
    {
        if ($score >= 85) {
            return 'Sangat Baik';
        }
        if ($score >= 75) {
            return 'Baik';
        }
        if ($score >= 65) {
            return 'Cukup';
        }
        return 'Kurang';
    }

    /**
     * Konversi nilai angka ke huruf mutu (A/B/C/D).
     */
    public function calculateGradeLetter(float $score): string
    {
        if ($score >= 85) {
            return 'A';
        }
        if ($score >= 75) {
            return 'B';
        }
        if ($score >= 65) {
            return 'C';
        }
        return 'D';
    }

    /**
     * Generate atau update sertifikat untuk siswa PKL tertentu.
     */
    public function generateForStudentPkl(StudentPkl $studentPkl, array $overrides = []): Certificate
    {
        return DB::transaction(function () use ($studentPkl, $overrides) {
            $studentPkl->loadMissing(['student.user', 'school', 'company', 'assessment']);

            $assessment = $studentPkl->assessment;

            // Hitung nilai akhir jika belum ditentukan
            $finalScore = $overrides['final_score'] ?? null;
            if ($finalScore === null) {
                if ($assessment && $assessment->final_score > 0) {
                    $finalScore = (float) $assessment->final_score;
                } elseif ($assessment) {
                    // Bobot default: Rata-rata DU/DI 60% + Guru 40%
                    $dudiAvg = (($assessment->score_dudi_discipline ?? 80) +
                                ($assessment->score_dudi_technical ?? 80) +
                                ($assessment->score_dudi_initiative ?? 80)) / 3;
                    $teacherScore = $assessment->score_teacher_report ?? 80;
                    $finalScore = round(($dudiAvg * 0.6) + ($teacherScore * 0.4), 2);
                } else {
                    $finalScore = 80.00;
                }
            }

            $predicate = $overrides['final_predicate'] ?? $this->calculatePredicate((float) $finalScore);
            $issuedDate = $overrides['issued_date'] ?? date('Y-m-d');

            // Cek apakah sudah pernah dibuat sertifikat (dengan row-level lock)
            $existing = Certificate::where('student_pkl_id', $studentPkl->id)->lockForUpdate()->first();
            if ($existing) {
                $existing->update([
                    'final_score' => $finalScore,
                    'final_predicate' => $predicate,
                    'issued_date' => $issuedDate,
                ]);
                return $existing;
            }

            // Generate nomor urut sertifikat format: PKL/{YEAR}/{SCHOOL_NPSN_OR_ID}/{COUNTER}
            $year = date('Y', strtotime($issuedDate));
            $schoolIdentifier = $studentPkl->school->npsn ?? ('SCH' . $studentPkl->school_id);
            $uuid = (string) Str::uuid();

            // Mekanisme anti-race condition dengan lock dan loop retry pada duplicate number
            $maxAttempts = 5;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $count = Certificate::where('school_id', $studentPkl->school_id)
                    ->whereYear('issued_date', $year)
                    ->lockForUpdate()
                    ->count() + ($attempt - 1) + 1;

                $certificateNumber = sprintf("PKL/%s/%s/%04d", $year, $schoolIdentifier, $count);

                try {
                    return Certificate::create([
                        'school_id' => $studentPkl->school_id,
                        'student_pkl_id' => $studentPkl->id,
                        'uuid' => $uuid,
                        'certificate_number' => $certificateNumber,
                        'final_score' => $finalScore,
                        'final_predicate' => $predicate,
                        'issued_date' => $issuedDate,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Jika tabrakan nomor sertifikat (unique constraint violation), coba nomor urut berikutnya
                    if (str_contains($e->getMessage(), 'certificate_number') || str_contains($e->getMessage(), 'uk_school_certificate_number')) {
                        if ($attempt < $maxAttempts) {
                            continue;
                        }
                    }

                    // Jika tabrakan student_pkl_id (proses paralel lain berhasil menyisipkan lebih dulu)
                    $existingAfter = Certificate::where('student_pkl_id', $studentPkl->id)->lockForUpdate()->first();
                    if ($existingAfter) {
                        $existingAfter->update([
                            'final_score' => $finalScore,
                            'final_predicate' => $predicate,
                            'issued_date' => $issuedDate,
                        ]);
                        return $existingAfter;
                    }

                    throw $e;
                }
            }

            // Fallback safety (tidak akan tercapai kecuali semua attempt gagal)
            throw new \RuntimeException('Gagal menerbitkan sertifikat karena konkurensi penomoran.');
        });
    }

    public function eligibleStudentPklIds(int $schoolId, ?int $pklPeriodId = null): array
    {
        $query = StudentPkl::where('school_id', $schoolId)
            ->whereHas('assessment')
            ->doesntHave('certificate');

        if ($pklPeriodId) {
            $query->where('pkl_period_id', $pklPeriodId);
        }

        return $query->pluck('id')->all();
    }

    public function dispatchBatchForSchool(int $schoolId, ?int $pklPeriodId = null): \Illuminate\Bus\PendingBatch
    {
        $jobs = collect($this->eligibleStudentPklIds($schoolId, $pklPeriodId))
            ->map(fn (int $studentPklId) => new GenerateStudentCertificate($studentPklId))
            ->all();

        return Bus::batch($jobs)
            ->name("certificate-school-{$schoolId}")
            ->allowFailures()
            ->onQueue('certificates');
    }

    public function generateBatchForSchool(int $schoolId, ?int $pklPeriodId = null): int
    {
        return $this->dispatchBatchForSchool($schoolId, $pklPeriodId)->dispatch()->totalJobs;
    }

    public function batchStatus(string $batchId): ?Batch
    {
        return Bus::findBatch($batchId);
    }

    /**
     * Ambil data sertifikat berdasarkan UUID untuk verifikasi publik.
     */
    public function verifyByUuid(string $uuid): ?Certificate
    {
        return Certificate::where('uuid', $uuid)
            ->with([
                'school',
                'studentPkl.student.user',
                'studentPkl.student.class.major',
                'studentPkl.company',
                'studentPkl.assessment',
                'studentPkl.teacher.user',
                'studentPkl.supervisor.user',
                'studentPkl.pklPeriod',
            ])
            ->first();
    }

    /**
     * List sertifikat untuk tabel admin sekolah.
     */
    public function getCertificatesForSchool(int $schoolId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Certificate::where('school_id', $schoolId)
            ->with([
                'studentPkl.student.user',
                'studentPkl.student.class',
                'studentPkl.company',
                'studentPkl.pklPeriod'
            ])
            ->latest('issued_date');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhereHas('studentPkl.student.user', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Render PDF 2-halaman (Halaman 1: Sertifikat Landscape, Halaman 2: Transkrip Nilai).
     */
    public function renderPdf(Certificate $certificate): DomPdfInstance
    {
        // Optimasi resource untuk lingkungan produksi / live server
        ini_set('memory_limit', '256M');
        set_time_limit(120);

        // Pastikan direktori fonts & cache tersedia untuk DomPDF
        $fontDir = storage_path('fonts');
        if (!file_exists($fontDir)) {
            @mkdir($fontDir, 0755, true);
        }
        $tempDir = storage_path('framework/cache');
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $certificate->loadMissing([
            'school.settings',
            'studentPkl.student.user',
            'studentPkl.student.class.major.learningObjectives',
            'studentPkl.company',
            'studentPkl.assessment',
            'studentPkl.teacher.user',
            'studentPkl.supervisor.user',
            'studentPkl.pklPeriod',
        ]);

        $school = $certificate->school;
        $verificationUrl = url('/verify/cert/' . $certificate->uuid);
        
        // Generate SVG QR Code dan encode Base64 dengan penanganan error defensif & dual-engine
        $qrCodeBase64 = null;
        try {
            if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                $qrSvg = (string) QrCode::format('svg')
                    ->size(130)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate($verificationUrl);

                $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
            }
        } catch (\Throwable $e) {
            Log::warning("CertificateService: Gagal generate QR code via local package (UUID: {$certificate->uuid}): " . $e->getMessage());
        }

        // Fallback otomatis jika package belum terpasang di vendor server live
        if (empty($qrCodeBase64)) {
            try {
                $qrResponse = \Illuminate\Support\Facades\Http::timeout(4)->get(
                    'https://api.qrserver.com/v1/create-qr-code/?size=130x130&format=svg&data=' . urlencode($verificationUrl)
                );
                if ($qrResponse->successful() && str_contains($qrResponse->body(), '<svg')) {
                    $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrResponse->body());
                }
            } catch (\Throwable $e) {
                Log::warning("CertificateService: Gagal generate QR code via fallback service: " . $e->getMessage());
            }
        }

        // Siapkan Logo Sekolah (Base64) dengan fallback aman tanpa ketergantungan ekstensi fileinfo
        $schoolLogoBase64 = null;
        try {
            if ($school && $school->logo_path) {
                if (Storage::disk('public')->exists($school->logo_path)) {
                    $ext = strtolower(pathinfo($school->logo_path, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'svg' => 'image/svg+xml',
                        'webp' => 'image/webp',
                        default => 'image/png',
                    };
                    $content = base64_encode(Storage::disk('public')->get($school->logo_path));
                    $schoolLogoBase64 = "data:{$mime};base64,{$content}";
                } else {
                    $logoFullPath = storage_path('app/public/' . $school->logo_path);
                    if (file_exists($logoFullPath)) {
                        $ext = strtolower(pathinfo($logoFullPath, PATHINFO_EXTENSION));
                        $mime = match ($ext) {
                            'jpg', 'jpeg' => 'image/jpeg',
                            'svg' => 'image/svg+xml',
                            'webp' => 'image/webp',
                            default => 'image/png',
                        };
                        $schoolLogoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFullPath));
                    }
                }
            }
            if (!$schoolLogoBase64 && file_exists(public_path('images/logo.png'))) {
                $logoFullPath = public_path('images/logo.png');
                $schoolLogoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFullPath));
            }
        } catch (\Throwable $e) {
            Log::warning("CertificateService: Gagal memuat logo sekolah: " . $e->getMessage());
        }

        // Ambil nama & NIP kepala sekolah dari setting sekolah
        $kepsekName = $school ? $school->getSetting('kepsek_name', $school->name) : 'Kepala Sekolah';
        $kepsekNip = $school ? $school->getSetting('kepsek_nip', '-') : '-';

        // Ambil baris kop pemerintah jika ada
        $kopLine1 = $school ? $school->getSetting('kop_header_line1', null) : null;
        $kopLine2 = $school ? $school->getSetting('kop_header_line2', null) : null;

        // Ambil kota penerbitan sertifikat dari alamat sekolah
        $schoolCity = 'Garut';
        if ($school && !empty($school->address)) {
            if (preg_match('/(?:Kabupaten|Kota)\s+([A-Za-z\s]+?)(?:,|$)/i', $school->address, $matches)) {
                $schoolCity = trim($matches[1]);
            }
        }

        // Hitung rincian nilai transkrip secara terstruktur & aman dari null
        $assessment = $certificate->studentPkl?->assessment;
        $scoreDiscipline = (float) ($assessment?->score_dudi_discipline ?? 82);
        $scoreTechnical = (float) ($assessment?->score_dudi_technical ?? 85);
        $scoreInitiative = (float) ($assessment?->score_dudi_initiative ?? 80);
        $scoreReport = (float) ($assessment?->score_teacher_report ?? 83);

        $gradeDiscipline = [
            'score' => $scoreDiscipline,
            'letter' => $this->calculateGradeLetter($scoreDiscipline),
            'predicate' => $this->calculatePredicate($scoreDiscipline),
        ];
        $gradeTechnical = [
            'score' => $scoreTechnical,
            'letter' => $this->calculateGradeLetter($scoreTechnical),
            'predicate' => $this->calculatePredicate($scoreTechnical),
        ];
        $gradeInitiative = [
            'score' => $scoreInitiative,
            'letter' => $this->calculateGradeLetter($scoreInitiative),
            'predicate' => $this->calculatePredicate($scoreInitiative),
        ];
        $gradeReport = [
            'score' => $scoreReport,
            'letter' => $this->calculateGradeLetter($scoreReport),
            'predicate' => $this->calculatePredicate($scoreReport),
        ];

        return Pdf::loadView('certificates.pdf_template', [
            'certificate' => $certificate,
            'spkl' => $certificate->studentPkl,
            'student' => $certificate->studentPkl?->student,
            'school' => $school,
            'company' => $certificate->studentPkl?->company,
            'assessment' => $assessment,
            'gradeDiscipline' => $gradeDiscipline,
            'gradeTechnical' => $gradeTechnical,
            'gradeInitiative' => $gradeInitiative,
            'gradeReport' => $gradeReport,
            'finalGradeLetter' => $this->calculateGradeLetter((float) $certificate->final_score),
            'qrCodeBase64' => $qrCodeBase64,
            'verificationUrl' => $verificationUrl,
            'schoolLogoBase64' => $schoolLogoBase64,
            'kepsekName' => $kepsekName,
            'kepsekNip' => $kepsekNip,
            'kopLine1' => $kopLine1,
            'kopLine2' => $kopLine2,
            'schoolCity' => $schoolCity,
            'learningObjectives' => $certificate->studentPkl?->student?->class?->major?->learningObjectives ?? collect(),
        ])->setPaper('a4', 'landscape');
    }

    /**
     * Path penyimpanan berkas PDF terkompilasi di disk privat (storage/app/private).
     */
    public function getPdfStoragePath(Certificate $certificate): string
    {
        return "schools/{$certificate->school_id}/certificates/{$certificate->uuid}.pdf";
    }

    /**
     * Periksa apakah berkas PDF sudah tersedia di cache disk privat.
     */
    public function hasPdfCache(Certificate $certificate): bool
    {
        return Storage::disk('local')->exists($this->getPdfStoragePath($certificate));
    }

    /**
     * Render dan simpan berkas PDF ke disk privat.
     */
    public function saveCompiledPdf(Certificate $certificate): string
    {
        $pdf = $this->renderPdf($certificate);
        $path = $this->getPdfStoragePath($certificate);
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Dapatkan response unduh dari cache berkas statis (menghindari re-render DomPDF berulang).
     */
    public function getCachedPdfResponse(Certificate $certificate, string $downloadFilename): \Symfony\Component\HttpFoundation\Response
    {
        $path = $this->getPdfStoragePath($certificate);
        if (!Storage::disk('local')->exists($path)) {
            $this->saveCompiledPdf($certificate);
        }

        $absolutePath = Storage::disk('local')->path($path);

        return response()->download($absolutePath, $downloadFilename, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
