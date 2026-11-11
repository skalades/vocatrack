<?php

namespace App\Http\Controllers;

use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CertificateVerificationController extends Controller
{
    /**
     * Halaman Publik Verifikasi E-Sertifikat PKL Anti-Pemalsuan.
     */
    public function verify(string $uuid, CertificateService $certificateService)
    {
        $certificate = $certificateService->verifyByUuid($uuid);

        return view('public.certificate-verify', [
            'isValid' => $certificate !== null,
            'certificate' => $certificate,
            'uuid' => $uuid,
        ]);
    }

    /**
     * Download salinan PDF Sertifikat resmi dari verifikasi publik.
     */
    public function download(string $uuid, CertificateService $certificateService)
    {
        $certificate = $certificateService->verifyByUuid($uuid);

        if (!$certificate) {
            abort(404, 'Sertifikat tidak ditemukan atau kode verifikasi tidak valid.');
        }

        $pdf = $certificateService->renderPdf($certificate);
        $studentName = Str::slug($certificate->studentPkl->student->user->name ?? 'Siswa');
        $safeCertNumber = str_replace(['/', '\\'], '-', $certificate->certificate_number);

        return $pdf->download("E-Sertifikat_PKL_{$studentName}_{$safeCertNumber}.pdf");
    }
}
