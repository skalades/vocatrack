<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\ApproveSchoolRequest;
use App\Http\Requests\SuperAdmin\RejectSchoolRequest;
use App\Models\School;
use App\Models\SchoolRequest;
use App\Models\SchoolSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SubscriptionService;
use App\Services\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SchoolRequestController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function schoolRequests(Request $request)
    {
        $query = SchoolRequest::with(['school', 'reviewer'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('school_name', 'like', "%{$search}%")
                  ->orWhere('npsn', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(15)->withQueryString();
        $plans = SubscriptionPlan::where('is_active', true)->get();

        $totalRequests = SchoolRequest::count();
        $pendingRequests = SchoolRequest::where('status', 'pending')->count();
        $approvedRequests = SchoolRequest::where('status', 'approved')->count();

        return view('superadmin.school_requests', compact(
            'requests',
            'plans',
            'totalRequests',
            'pendingRequests',
            'approvedRequests'
        ));
    }

    public function approveSchoolRequest(ApproveSchoolRequest $request, SchoolRequest $schoolRequest, TenantProvisioningService $provisioning)
    {
        if ($schoolRequest->status !== 'pending') {
            return back()->with('error', "Permohonan {$schoolRequest->school_name} sudah diproses sebelumnya.");
        }

        if (School::where('npsn', $schoolRequest->npsn)->exists()) {
            return back()->with('error', "Sekolah dengan NPSN {$schoolRequest->npsn} sudah terdaftar di sistem.");
        }

        if (User::where('email', $schoolRequest->email)->exists()) {
            return back()->with('error', "Email kontak {$schoolRequest->email} sudah digunakan oleh akun lain.");
        }

        [$school, $temporaryPassword] = DB::transaction(function () use ($schoolRequest, $request, $provisioning) {
            [$school, , $temporaryPassword] = $provisioning->provision(
                [
                    'npsn' => $schoolRequest->npsn,
                    'name' => $schoolRequest->school_name,
                    'address' => $schoolRequest->address ?: '-',
                    'phone' => $schoolRequest->phone,
                    'email' => $schoolRequest->email,
                ],
                $schoolRequest->contact_name,
                $schoolRequest->email
            );

            // Subscribe School
            $plan = SubscriptionPlan::findOrFail($request->plan_id);
            $customQuota = $request->custom_quota ? (int) $request->custom_quota : null;
            $status = $plan->price > 0 ? 'active' : 'trial';

            $this->subscriptionService->subscribeSchool(
                $school,
                $plan,
                Carbon::today(),
                $customQuota,
                null,
                $status,
                "Aktivasi via Permohonan Demo Onboarding ({$plan->name})",
                null,
                true,
                $request->input('payment_status', 'paid'),
                $request->payment_method
            );

            // Mark Request as approved
            $schoolRequest->update([
                'status' => 'approved',
                'school_id' => $school->id,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => Carbon::now(),
            ]);

            return [$school, $temporaryPassword];
        });

        AuditLogService::log(
            'school_request_approved',
            'schools',
            "Permohonan demo untuk sekolah {$school->name} (NPSN: {$school->npsn}) disetujui dan tenant dibuat.",
            $school
        );

        return back()
            ->with('success', "Permohonan disetujui! Tenant {$school->name} berhasil dibuat.")
            ->with('temporary_credential', [
                'email' => $schoolRequest->email,
                'password' => $temporaryPassword,
                'context' => "Admin Sekolah {$school->name}",
            ]);
    }

    public function rejectSchoolRequest(RejectSchoolRequest $request, SchoolRequest $schoolRequest)
    {
        $schoolRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => Carbon::now(),
        ]);

        AuditLogService::log(
            'school_request_rejected',
            'schools',
            "Permohonan demo untuk {$schoolRequest->school_name} ditolak. Alasan: {$request->rejection_reason}."
        );

        return back()->with('success', "Permohonan demo untuk {$schoolRequest->school_name} ditolak.");
    }

    public function storePublicSchoolRequest(Request $request)
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:150'],
            'npsn' => ['required', 'string', 'size:8'],
            'contact_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'estimated_students' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (School::where('npsn', $request->npsn)->exists()) {
            return back()->withErrors(['npsn' => 'Sekolah dengan NPSN ini telah terdaftar di sistem kami.'])->withInput();
        }

        $pendingExist = SchoolRequest::where('npsn', $request->npsn)
            ->where('status', 'pending')
            ->exists();

        if ($pendingExist) {
            return back()->withErrors(['npsn' => 'Permohonan untuk NPSN ini sedang dalam proses review oleh tim kami.'])->withInput();
        }

        SchoolRequest::create([
            'school_name' => $validated['school_name'],
            'npsn' => $validated['npsn'],
            'contact_name' => $validated['contact_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'estimated_students' => $validated['estimated_students'] ?? 50,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        AuditLogService::log(
            'school_request_submitted',
            'schools',
            "Permohonan demo/uji coba baru diajukan oleh {$validated['school_name']} (NPSN: {$validated['npsn']})."
        );

        return back()->with('success', 'Terima kasih! Permohonan demo sistem berhasil dikirim. Tim VokaTrack akan segera menghubungi Anda melalui kontak yang terdaftar.');
    }
}
