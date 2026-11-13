<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Subscription Plans
        $plans = [
            [
                'code' => 'trial-14d',
                'name' => 'Trial Evaluasi (14 Hari)',
                'price' => 0,
                'billing_period' => 'monthly',
                'max_students' => 50,
                'max_storage_mb' => 2048,
                'features' => [
                    'attendance' => true,
                    'journal' => true,
                    'assessment' => true,
                    'certificate' => false,
                    'whatsapp_gateway' => false,
                    'custom_domain' => false,
                ],
                'description' => 'Paket uji coba gratis selama 14 hari untuk evaluasi fitur sistem bagi sekolah baru.',
                'is_popular' => false,
                'is_active' => true,
            ],
            [
                'code' => 'starter-semester',
                'name' => 'Paket Starter (Semesteran)',
                'price' => 2500000,
                'billing_period' => 'semester',
                'max_students' => 100,
                'max_storage_mb' => 5120,
                'features' => [
                    'attendance' => true,
                    'journal' => true,
                    'assessment' => true,
                    'certificate' => true,
                    'whatsapp_gateway' => false,
                    'custom_domain' => false,
                ],
                'description' => 'Cocok untuk SMK skala kecil atau jurusan tertentu dengan kuota hingga 100 siswa per semester.',
                'is_popular' => false,
                'is_active' => true,
            ],
            [
                'code' => 'pro-yearly',
                'name' => 'Paket Pro Tahunan (Rekomendasi)',
                'price' => 6000000,
                'billing_period' => 'yearly',
                'max_students' => 300,
                'max_storage_mb' => 20480,
                'features' => [
                    'attendance' => true,
                    'journal' => true,
                    'assessment' => true,
                    'certificate' => true,
                    'whatsapp_gateway' => true,
                    'custom_domain' => false,
                ],
                'description' => 'Paket terlaris untuk SMK negeri/swasta menengah dengan masa aktif 1 tahun ajaran penuh (s.d. 300 siswa).',
                'is_popular' => true,
                'is_active' => true,
            ],
            [
                'code' => 'enterprise-yearly',
                'name' => 'Paket Enterprise Cloud',
                'price' => 12000000,
                'billing_period' => 'yearly',
                'max_students' => 1000,
                'max_storage_mb' => 102400,
                'features' => [
                    'attendance' => true,
                    'journal' => true,
                    'assessment' => true,
                    'certificate' => true,
                    'whatsapp_gateway' => true,
                    'custom_domain' => true,
                    'priority_support' => true,
                ],
                'description' => 'Solusi lengkap untuk SMK besar dengan ribuan peserta PKL, kuota 100 GB, dan prioritas dukungan teknis 24/7.',
                'is_popular' => false,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $p) {
            SubscriptionPlan::updateOrCreate(['code' => $p['code']], $p);
        }

        // 2. Attach Demo / Trial subscription to all registered schools
        $subService = app(SubscriptionService::class);
        $trialPlan = SubscriptionPlan::where('code', 'trial-14d')->first();

        // Seluruh sekolah yang terdaftar saat ini bersifat Paket Demo / Trial Evaluasi (14 Hari)
        $schools = School::all();
        foreach ($schools as $school) {
            $activeSub = $school->activeSubscription();
            if (!$activeSub || $activeSub->status !== 'trial') {
                // If there were previous non-trial subscriptions, delete or supersede with demo package
                $school->subscriptions()->delete();

                // Allocate demo quota: default 50, but if school already has more registered students, give sufficient trial quota
                $currentStudents = $school->students_count;
                $demoQuota = max(50, $currentStudents + 10);

                $subService->subscribeSchool(
                    $school,
                    $trialPlan,
                    Carbon::today()->subDays(2), // 2 days elapsed
                    $demoQuota,
                    0,
                    'trial',
                    "Paket Demo Evaluasi Sistem (14 Hari) — Tenant {$school->name}"
                );
            }
        }
    }
}
