<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pkl_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'is_active'], 'idx_periods_school_active');
        });

        Schema::create('student_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('pkl_period_id')->constrained('pkl_periods')->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('supervisors')->nullOnDelete();
            $table->enum('status', ['assigned', 'active', 'completed', 'canceled'])->default('assigned');
            $table->timestamps();

            $table->unique(['student_id', 'pkl_period_id'], 'uk_student_period');
            $table->index(['school_id', 'company_id', 'teacher_id'], 'idx_student_pkl_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_pkl');
        Schema::dropIfExists('pkl_periods');
    }
};
