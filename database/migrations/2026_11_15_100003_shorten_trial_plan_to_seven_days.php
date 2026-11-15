<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Masa trial demo dipersingkat dari 14 menjadi 7 hari (SubscriptionService::TRIAL_DAYS).
 * Kode paket 'trial-14d' dipertahankan sebagai pengenal internal; hanya nama & deskripsi
 * yang tampil ke pengguna yang diperbarui. Trial yang sedang berjalan tidak dipotong.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscription_plans')->where('code', 'trial-14d')->update([
            'name' => 'Trial Evaluasi (7 Hari)',
            'description' => 'Paket uji coba gratis selama 7 hari untuk evaluasi fitur sistem bagi sekolah baru.',
            'updated_at' => now(),
        ]);

        // Snapshot nama paket pada periode trial yang masih tersimpan dengan label lama.
        DB::table('school_subscriptions')
            ->where('plan_name', 'Trial Evaluasi (14 Hari)')
            ->update(['plan_name' => 'Trial Evaluasi (7 Hari)']);
    }

    public function down(): void
    {
        DB::table('subscription_plans')->where('code', 'trial-14d')->update([
            'name' => 'Trial Evaluasi (14 Hari)',
            'description' => 'Paket uji coba gratis selama 14 hari untuk evaluasi fitur sistem bagi sekolah baru.',
            'updated_at' => now(),
        ]);

        DB::table('school_subscriptions')
            ->where('plan_name', 'Trial Evaluasi (7 Hari)')
            ->update(['plan_name' => 'Trial Evaluasi (14 Hari)']);
    }
};
