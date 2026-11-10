<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_pkl_id')->constrained('student_pkl')->cascadeOnDelete();
            $table->date('date');
            $table->text('activity_description');
            $table->enum('status', ['draft', 'submitted', 'review_guru', 'approved', 'revision'])->default('draft');
            $table->timestamps();

            $table->unique(['student_pkl_id', 'date'], 'uk_journal_day');
            $table->index(['school_id', 'status'], 'idx_journals_status');
        });

        Schema::create('journal_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->string('photo_path', 255);
            $table->string('caption', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('journal_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role_level', ['pembimbing-dudi', 'guru-pembimbing']);
            $table->enum('status', ['approved', 'revision']);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_approvals');
        Schema::dropIfExists('journal_photos');
        Schema::dropIfExists('journals');
    }
};
