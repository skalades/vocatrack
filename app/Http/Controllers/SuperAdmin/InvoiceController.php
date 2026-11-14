<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Services\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function invoices(Request $request)
    {
        $query = Invoice::with(['school', 'subscription'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('school', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->paginate(15)->withQueryString();
        $schools = School::where('is_active', true)->orderBy('name')->get();

        $totalInvoicesCount = Invoice::count();
        $paidInvoicesCount = Invoice::where('status', 'paid')->count();
        $unpaidInvoicesCount = Invoice::where('status', 'unpaid')->count();
        $totalInvoicedAmount = (float) Invoice::sum('amount');

        return view('superadmin.invoices', compact(
            'invoices', 
            'schools', 
            'totalInvoicesCount', 
            'paidInvoicesCount', 
            'unpaidInvoicesCount', 
            'totalInvoicedAmount'
        ));
    }

    public function storeInvoice(StoreInvoiceRequest $request)
    {
        $school = School::findOrFail($request->school_id);
        $invoiceNumber = 'INV-' . date('Ym') . '-' . strtoupper(Str::random(5));

        Invoice::create([
            'invoice_number' => $invoiceNumber,
            'school_id' => $school->id,
            'subscription_id' => $school->activeSubscription()?->id,
            'title' => $request->title,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => 'unpaid',
            'notes' => $request->notes,
        ]);

        AuditLogService::log(
            'invoice_create',
            'billing',
            "Faktur tagihan {$invoiceNumber} untuk {$school->name} senilai Rp " . number_format($request->amount, 0, ',', '.') . " berhasil diterbitkan.",
            $school
        );

        return back()->with('success', "Invoice tagihan {$invoiceNumber} untuk {$school->name} berhasil diterbitkan.");
    }

    public function markInvoicePaid(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
        ]);

        // If subscription is linked and was expired, mark subscription active
        if ($invoice->subscription && $invoice->subscription->status === 'expired') {
            $invoice->subscription->update(['status' => 'active']);
        }

        AuditLogService::log(
            'invoice_verify',
            'billing',
            "Faktur tagihan {$invoice->invoice_number} senilai Rp " . number_format($invoice->amount, 0, ',', '.') . " diverifikasi LUNAS.",
            $invoice->school
        );

        return back()->with('success', "Invoice {$invoice->invoice_number} berhasil ditandai LUNAS.");
    }

    public function cancelInvoice(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', "Invoice yang sudah lunas tidak dapat dibatalkan.");
        }

        $invoice->update(['status' => 'cancelled']);

        AuditLogService::log(
            'invoice_cancel',
            'billing',
            "Faktur tagihan {$invoice->invoice_number} dibatalkan oleh Super Admin.",
            $invoice->school
        );

        return back()->with('success', "Invoice {$invoice->invoice_number} berhasil dibatalkan.");
    }

    public function destroyInvoice(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', "Invoice yang sudah berstatus LUNAS tidak dapat dihapus demi audit finansial.");
        }

        $number = $invoice->invoice_number;
        $school = $invoice->school;
        $invoice->delete();

        AuditLogService::log(
            'invoice_delete',
            'billing',
            "Faktur tagihan {$number} dihapus dari sistem.",
            $school
        );

        return back()->with('success', "Invoice tagihan {$number} berhasil dihapus.");
    }

    public function downloadInvoicePdf(Invoice $invoice)
    {
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
