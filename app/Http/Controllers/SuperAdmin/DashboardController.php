<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function dashboard()
    {
        $metrics = $this->subscriptionService->getSaaSMetrics();

        // Schools with relations
        $recentSchools = School::with(['subscriptions' => function ($q) {
                $q->latest('end_date');
            }, 'users'])
            ->withCount(['users', 'classes', 'companies'])
            ->latest()
            ->take(6)
            ->get();

        // Calculate student count for each school
        foreach ($recentSchools as $sch) {
            $sch->students_count = Student::whereHas('class', function ($q) use ($sch) {
                $q->where('school_id', $sch->id);
            })->count();
        }

        // Schools expiring within 30 days (based on active subscription)
        $thirtyDays = Carbon::today()->addDays(30);
        $expiringSubscriptions = SchoolSubscription::with('school')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('school_subscriptions')
                    ->groupBy('school_id');
            })
            ->where('end_date', '>=', Carbon::today())
            ->where('end_date', '<=', $thirtyDays)
            ->where('status', '!=', 'suspended')
            ->get();

        // Recent Invoices
        $recentInvoices = Invoice::with('school')
            ->latest()
            ->take(5)
            ->get();

        // Available Plans for Quick Actions / Modals
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return view('superadmin.dashboard', compact(
            'metrics',
            'recentSchools',
            'expiringSubscriptions',
            'recentInvoices',
            'plans'
        ));
    }
}
