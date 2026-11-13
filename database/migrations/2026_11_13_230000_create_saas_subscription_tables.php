<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Paket Langganan
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->decimal('price', 12, 2)->default(0);
            $table->string('billing_period', 20)->default('yearly'); // monthly, semester, yearly
            $table->integer('max_students')->default(100);
            $table->integer('max_storage_mb')->default(5120);
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Langganan Aktif Sekolah Mitra
        Schema::create('school_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->string('plan_name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('price_paid', 12, 2)->default(0);
            $table->integer('student_quota')->default(100);
            $table->integer('storage_quota_mb')->default(5120);
            $table->string('status', 30)->default('active')->index(); // trial, active, grace_period, expired, suspended
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Faktur / Tagihan (Invoices)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('school_subscriptions')->nullOnDelete();
            $table->string('title', 150);
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('status', 30)->default('unpaid')->index(); // unpaid, pending_verification, paid, cancelled
            $table->string('payment_method', 80)->nullable();
            $table->string('payment_proof_path', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('school_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
