<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\School;
use App\Models\SchoolRequest;
use App\Services\SubscriptionService;
use App\Services\TenantLifecycleService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected TenantLifecycleService $lifecycle
    ) {}

    /**
     * Dashboard sebagai kotak tindakan: apa yang harus dikerjakan Super Admin hari ini.
     */
    public function dashboard()
    {
        $metrics = $this->subscriptionService->getSaaSMetrics();

        $pendingRequests = SchoolRequest::where('status', 'pending')->latest()->take(5)->get();
        $pendingRequestsCount = SchoolRequest::where('status', 'pending')->count();

        $openInvoices = Invoice::with('school')
            ->whereIn('status', ['unpaid', 'pending_verification'])
            ->orderBy('due_date')
            ->take(6)
            ->get();

        // Sekolah yang butuh perhatian: akan habis, tenggang, kedaluwarsa, atau trial hampir selesai.
        $schools = School::with('subscriptions')->where('is_active', true)->get();
        $attention = $schools
            ->map(function (School $school) {
                $sub = $school->activeSubscription();
                $stage = $this->lifecycle->stage($school, $sub);
                return compact('school', 'sub', 'stage');
            })
            ->filter(fn ($row) => $row['sub'] && (
                in_array($row['stage'], ['expiring', 'grace', 'expired'], true)
                || ($row['stage'] === 'trial' && $row['sub']->daysRemaining() <= 3)
            ))
            ->sortBy(fn ($row) => $row['sub']->end_date->timestamp)
            ->values();

        $failedJobsCount = DB::table('failed_jobs')->count();
        $recentActivity = ActivityLog::with(['school', 'user'])
            ->whereIn('module', ['billing', 'schools', 'security'])
            ->latest()
            ->take(8)
            ->get();

        return view('superadmin.dashboard', [
            'metrics' => $metrics,
            'pendingRequests' => $pendingRequests,
            'pendingRequestsCount' => $pendingRequestsCount,
            'openInvoices' => $openInvoices,
            'attention' => $attention->take(8),
            'attentionCount' => $attention->count(),
            'failedJobsCount' => $failedJobsCount,
            'recentActivity' => $recentActivity,
        ]);
    }
}
