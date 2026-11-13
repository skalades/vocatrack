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

        // 2. Attach initial subscriptions to existing schools if they don't have one
        $subService = app(SubscriptionService::class);
        $proPlan = SubscriptionPlan::where('code', 'pro-yearly')->first();
        $starterPlan = SubscriptionPlan::where('code', 'starter-semester')->first();

        $smkn1 = School::where('npsn', '20209201')->first() ?? School::first();
        if ($smkn1 && !$smkn1->activeSubscription()) {
            $subService->subscribeSchool(
                $smkn1,
                $proPlan,
                Carbon::today()->subMonths(1),
                150,
                6000000,
                'active',
                'Langganan Tahunan Tahun Ajaran 2026/2027'
            );
        }

        $smkn2 = School::where('npsn', '20209203')->first();
        if ($smkn2 && !$smkn2->activeSubscription()) {
            $subService->subscribeSchool(
                $smkn2,
                $starterPlan,
                Carbon::today()->subMonths(4),
                100,
                2500000,
                'active',
                'Langganan Semester Ganjil 2026/2027 (Habis dalam 2 bulan)'
            );
        }
    }
}
