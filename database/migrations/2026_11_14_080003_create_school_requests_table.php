<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_requests', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 10)->index();
            $table->string('school_name', 150);
            $table->string('contact_name', 100);
            $table->string('email', 100);
            $table->string('phone', 25);
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending')->index(); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_requests');
    }
};
