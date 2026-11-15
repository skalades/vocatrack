<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Invoice kini membawa "niat" tagihannya (aktivasi, perpanjangan, upgrade, manual) beserta
 * paket & durasi yang akan diterapkan saat lunas. Periode langganan baru hanya dibuat
 * ketika invoice perpanjangan/upgrade dibayar, sehingga penagihan dan hak akses selalu sinkron.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('type', 20)->default('manual')->after('subscription_id')->index();
            $table->foreignId('plan_id')->nullable()->after('type')->constrained('subscription_plans')->nullOnDelete();
            $table->unsignedSmallInteger('period_months')->nullable()->after('plan_id');
            $table->unsignedInteger('student_quota')->nullable()->after('period_months');
            $table->unsignedInteger('storage_quota_mb')->nullable()->after('student_quota');
            $table->timestamp('applied_at')->nullable()->after('paid_at');
        });

        // Klasifikasi invoice lama berdasarkan judul yang dibuat sistem sebelumnya.
        DB::table('invoices')->where('title', 'like', 'Perpanjangan Sewa%')->update(['type' => 'renewal']);
        DB::table('invoices')->where('title', 'like', 'Biaya Sewa%')->update(['type' => 'activation']);
        // Invoice lunas dari sistem lama sudah tercermin di periode langganan yang ada.
        DB::table('invoices')->whereIn('type', ['renewal', 'activation'])->where('status', 'paid')
            ->update(['applied_at' => DB::raw('paid_at')]);

        // Periode lama menumpuk price_paid setiap perpanjangan. Normalisasi ke nilai
        // pembayaran terakhir agar estimasi MRR/ARR tidak menggelembung.
        $subscriptions = DB::table('school_subscriptions')->get(['id', 'price_paid']);
        foreach ($subscriptions as $sub) {
            $lastPaid = DB::table('invoices')
                ->where('subscription_id', $sub->id)
                ->where('status', 'paid')
                ->where('amount', '>', 0)
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->value('amount');

            if ($lastPaid !== null && (float) $lastPaid < (float) $sub->price_paid) {
                DB::table('school_subscriptions')->where('id', $sub->id)->update(['price_paid' => $lastPaid]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['type', 'period_months', 'student_quota', 'storage_quota_mb', 'applied_at']);
        });
    }
};
