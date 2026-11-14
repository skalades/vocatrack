<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StorePlanRequest;
use App\Http\Requests\SuperAdmin\UpdatePlanRequest;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function plans()
    {
        if (SubscriptionPlan::count() === 0) {
            (new \Database\Seeders\SubscriptionPlanSeeder())->run();
        }

        $plans = SubscriptionPlan::withCount('subscriptions')->get();

        $totalPlans = $plans->count();
        $activePlans = $plans->where('is_active', true)->count();
        $totalActiveSchools = SchoolSubscription::whereIn('status', ['active', 'trial', 'expiring_soon'])->distinct('school_id')->count('school_id');
        $totalCapacityLicensed = SchoolSubscription::sum('student_quota');

        return view('superadmin.plans', compact(
            'plans',
            'totalPlans',
            'activePlans',
            'totalActiveSchools',
            'totalCapacityLicensed'
        ));
    }

    public function storePlan(StorePlanRequest $request)
    {
        $features = [
            'attendance' => true,
            'journal' => true,
            'assessment' => true,
            'certificate' => $request->boolean('feat_certificate', true),
            'whatsapp_gateway' => $request->boolean('feat_wa', false),
            'custom_domain' => $request->boolean('feat_domain', false),
        ];

        SubscriptionPlan::create([
            'code' => Str::slug($request->code),
            'name' => $request->name,
            'price' => $request->price,
            'billing_period' => $request->billing_period,
            'max_students' => $request->max_students,
            'max_storage_mb' => $request->max_storage_mb,
            'features' => $features,
            'is_popular' => $request->boolean('is_popular'),
            'is_active' => $request->boolean('is_active', true),
            'description' => $request->description,
        ]);

        return back()->with('success', "Paket sewa baru {$request->name} berhasil ditambahkan.");
    }

    public function updatePlan(UpdatePlanRequest $request, SubscriptionPlan $plan)
    {
        $features = $plan->features ?? [];
        if ($request->has('feat_certificate')) {
            $features['certificate'] = $request->boolean('feat_certificate');
        }
        if ($request->has('feat_wa')) {
            $features['whatsapp_gateway'] = $request->boolean('feat_wa');
        }
        if ($request->has('feat_domain')) {
            $features['custom_domain'] = $request->boolean('feat_domain');
        }

        $plan->update([
            'name' => $request->name,
            'price' => $request->price,
            'max_students' => $request->max_students,
            'max_storage_mb' => $request->max_storage_mb,
            'features' => $features,
            'is_popular' => $request->boolean('is_popular'),
            'is_active' => $request->boolean('is_active'),
            'description' => $request->description,
        ]);

        return back()->with('success', "Paket {$plan->name} berhasil diperbarui.");
    }

    public function destroyPlan(SubscriptionPlan $plan)
    {
        if ($plan->code === 'trial-14d') {
            return back()->with('error', "Paket {$plan->name} adalah paket demo bawaan sistem dan tidak dapat dihapus.");
        }

        if ($plan->subscriptions()->exists()) {
            return back()->with('error', "Paket {$plan->name} tidak dapat dihapus karena masih digunakan oleh sekolah mitra.");
        }

        $plan->delete();
        return back()->with('success', "Paket {$plan->name} berhasil dihapus.");
    }
}
