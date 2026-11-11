<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\School;
use App\Models\StudentPkl;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
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

            // Cek apakah sudah pernah dibuat sertifikat
            $existing = Certificate::where('student_pkl_id', $studentPkl->id)->first();
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
            $count = Certificate::where('school_id', $studentPkl->school_id)
                ->whereYear('issued_date', $year)
                ->count() + 1;

            $certificateNumber = sprintf("PKL/%s/%s/%04d", $year, $schoolIdentifier, $count);
            $uuid = (string) Str::uuid();

            return Certificate::create([
                'school_id' => $studentPkl->school_id,
                'student_pkl_id' => $studentPkl->id,
                'uuid' => $uuid,
                'certificate_number' => $certificateNumber,
                'final_score' => $finalScore,
                'final_predicate' => $predicate,
                'issued_date' => $issuedDate,
            ]);
        });
    }

    /**
     * Generate batch untuk seluruh siswa pada suatu periode PKL yang sudah memiliki penilaian.
     */
    public function generateBatchForSchool(int $schoolId, ?int $pklPeriodId = null): int
    {
        $query = StudentPkl::where('school_id', $schoolId)
            ->whereHas('assessment')
            ->doesntHave('certificate');

        if ($pklPeriodId) {
            $query->where('pkl_period_id', $pklPeriodId);
        }

        $students = $query->get();
        $generatedCount = 0;

        foreach ($students as $spkl) {
            $this->generateForStudentPkl($spkl);
            $generatedCount++;
        }

        return $generatedCount;
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
        $certificate->loadMissing([
            'school.settings',
            'studentPkl.student.user',
            'studentPkl.student.class.major',
            'studentPkl.company',
            'studentPkl.assessment',
            'studentPkl.teacher.user',
            'studentPkl.supervisor.user',
            'studentPkl.pklPeriod',
        ]);

        $school = $certificate->school;
        $verificationUrl = url('/verify/cert/' . $certificate->uuid);
        
        // Generate SVG QR Code dan encode Base64 untuk kompatibilitas DomPDF yang sempurna
        $qrSvg = QrCode::format('svg')
            ->size(130)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($verificationUrl);

        $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

        // Siapkan Logo Sekolah (Base64) agar selalu aman dirender oleh DomPDF
        $schoolLogoBase64 = null;
        if ($school && $school->logo_path) {
            $logoFullPath = storage_path('app/public/' . $school->logo_path);
            if (file_exists($logoFullPath)) {
                $mimeType = mime_content_type($logoFullPath) ?: 'image/png';
                $schoolLogoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($logoFullPath));
            }
        }
        if (!$schoolLogoBase64 && file_exists(public_path('images/logo.png'))) {
            $logoFullPath = public_path('images/logo.png');
            $schoolLogoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFullPath));
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

        return Pdf::loadView('certificates.pdf_template', [
            'certificate' => $certificate,
            'spkl' => $certificate->studentPkl,
            'student' => $certificate->studentPkl->student,
            'school' => $school,
            'company' => $certificate->studentPkl->company,
            'assessment' => $certificate->studentPkl->assessment,
            'qrCodeBase64' => $qrCodeBase64,
            'verificationUrl' => $verificationUrl,
            'schoolLogoBase64' => $schoolLogoBase64,
            'kepsekName' => $kepsekName,
            'kepsekNip' => $kepsekNip,
            'kopLine1' => $kopLine1,
            'kopLine2' => $kopLine2,
            'schoolCity' => $schoolCity,
        ])->setPaper('a4', 'landscape');
    }
}
