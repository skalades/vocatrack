<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Models\Invoice;
use App\Models\PlatformSetting;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;

class BillingController extends BaseAdminSekolahController
{
    public function billing()
    {
        $schoolId = $this->getSchoolId();
        $school = School::with(['subscriptions' => function ($q) {
            $q->latest('end_date');
        }])->findOrFail($schoolId);

        $activeSubscription = $school->activeSubscription();
        $invoices = Invoice::where('school_id', $schoolId)->latest()->paginate(10);
        $subscriptions = $school->subscriptions()->latest()->get();

        $studentQuota = $activeSubscription?->student_quota ?? 100;
        $currentStudentsCount = $school->students_count;
        $quotaUsagePercent = min(100, round(($currentStudentsCount / max(1, $studentQuota)) * 100));

        $storageQuotaMb = $school->storage_quota_mb;
        $storageUsedMb = $school->storage_used_mb;
        $storageUsagePercent = $school->storage_usage_percent;

        return view('adminsekolah.billing', compact(
            'school',
            'activeSubscription',
            'invoices',
            'subscriptions',
            'studentQuota',
            'currentStudentsCount',
            'quotaUsagePercent',
            'storageQuotaMb',
            'storageUsedMb',
            'storageUsagePercent'
        ));
    }

    public function downloadInvoicePdf(Invoice $invoice)
    {
        $schoolId = $this->getSchoolId();
        abort_if($invoice->school_id !== $schoolId, 403, 'Akses ditolak.');

        $invoice->load(['school', 'subscription']);
        $platformName = PlatformSetting::get('platform_name', 'VokaTrack');
        $platformTagline = PlatformSetting::get('platform_tagline', 'Enterprise Cloud');
        $systemLogoUrl = PlatformSetting::getLogoUrl();

        $pdf = Pdf::loadView('superadmin.invoice_pdf', compact(
            'invoice',
            'platformName',
            'platformTagline',
            'systemLogoUrl'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }
}
