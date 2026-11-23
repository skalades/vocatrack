<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Models\Certificate;
use App\Models\PklPeriod;
use App\Models\StudentPkl;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CertificateController extends BaseAdminSekolahController
{
    public function certificates(Request $request, CertificateService $certificateService)
    {
        $schoolId = $this->getSchoolId();
        $certificates = $certificateService->getCertificatesForSchool($schoolId, $request->only('search'));

        $unissuedStudents = StudentPkl::where('school_id', $schoolId)
            ->whereHas('assessment')
            ->doesntHave('certificate')
            ->with(['student.user', 'company', 'assessment'])
            ->get();

        $periods = PklPeriod::where('school_id', $schoolId)->get();

        $stats = [
            'total_issued' => Certificate::where('school_id', $schoolId)->count(),
            'ready_to_issue' => $unissuedStudents->count(),
            'sangat_baik' => Certificate::where('school_id', $schoolId)->where('final_predicate', 'Sangat Baik')->count(),
        ];

        return view('adminsekolah.certificates', compact('certificates', 'unissuedStudents', 'periods', 'stats'));
    }

    public function generateCertificate(Request $request, CertificateService $certificateService)
    {
        $school = Auth::user()->school;
        if ($school && !$school->hasFeature('certificate')) {
            return back()->with('error', 'Fitur penerbitan E-Sertifikat tidak aktif pada paket langganan sekolah Anda. Silakan tingkatkan paket lisensi ke paket Pro/Enterprise.');
        }

        $schoolId = $this->getSchoolId();
        $validated = $request->validate([
            'student_pkl_id' => ['required', 'exists:student_pkl,id'],
        ]);

        $spkl = StudentPkl::where('school_id', $schoolId)->findOrFail($validated['student_pkl_id']);
        $certificate = $certificateService->generateForStudentPkl($spkl);

        return back()->with('success', "E-Sertifikat untuk {$spkl->student->user->name} berhasil diterbitkan (No: {$certificate->certificate_number}).");
    }

    public function generateBatchCertificates(Request $request, CertificateService $certificateService)
    {
        $school = Auth::user()->school;
        if ($school && !$school->hasFeature('certificate')) {
            return back()->with('error', 'Fitur penerbitan E-Sertifikat tidak aktif pada paket langganan sekolah Anda. Silakan tingkatkan paket lisensi ke paket Pro/Enterprise.');
        }

        $schoolId = $this->getSchoolId();
        $periodId = $request->input('pkl_period_id') ? (int) $request->input('pkl_period_id') : null;

        $count = $certificateService->generateBatchForSchool($schoolId, $periodId);

        return back()->with('success', "Berhasil menerbitkan {$count} E-Sertifikat baru secara massal.");
    }

    public function downloadCertificate(Certificate $certificate, CertificateService $certificateService)
    {
        $schoolId = $this->getSchoolId();
        abort_if($certificate->school_id !== $schoolId, 403, 'Akses ditolak.');

        try {
            $nameSlug = Str::slug($certificate->studentPkl?->student?->user?->name ?? 'Siswa');
            $safeCertNumber = str_replace(['/', '\\'], '-', $certificate->certificate_number);
            $filename = "E-Sertifikat_PKL_{$nameSlug}_{$safeCertNumber}.pdf";

            return $certificateService->getCachedPdfResponse($certificate, $filename);
        } catch (\Throwable $e) {
            Log::error("Download E-Sertifikat gagal (ID: {$certificate->id}): " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', 'Gagal memproses pengunduhan dokumen PDF E-Sertifikat: ' . $e->getMessage());
        }
    }
}
