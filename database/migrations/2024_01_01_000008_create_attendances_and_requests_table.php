<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_pkl_id')->constrained('student_pkl')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'dinas_luar', 'luar_kantor', 'alfa']);
            $table->time('checkin_time')->nullable();
            $table->string('checkin_photo_path', 255)->nullable();
            $table->decimal('checkin_lat', 10, 8)->nullable();
            $table->decimal('checkin_lng', 11, 8)->nullable();
            $table->boolean('is_within_radius')->nullable();
            $table->time('checkout_time')->nullable();
            $table->string('checkout_notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['student_pkl_id', 'date'], 'uk_attendance_day');
            $table->index(['school_id', 'date', 'status'], 'idx_attendances_report');
        });

        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_pkl_id')->constrained('student_pkl')->cascadeOnDelete();
            $table->enum('type', ['izin', 'sakit', 'dinas_luar', 'luar_kantor']);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->string('destination_location', 255)->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('reviewer_role', ['guru-pembimbing', 'pembimbing-dudi'])->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status', 'type'], 'idx_att_requests_queue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_requests');
        Schema::dropIfExists('attendances');
    }
};
