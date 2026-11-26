<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supervision_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('pkl_period_id')->constrained('pkl_periods')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('major_id')->nullable()->constrained('majors')->nullOnDelete();
            $table->enum('stage', ['monitoring_1', 'monitoring_2']);
            $table->string('assignment_number', 100)->nullable();
            $table->date('scheduled_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'teacher_id', 'pkl_period_id', 'stage'], 'idx_spv_assign_teacher');
        });

        Schema::create('supervision_assignment_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervision_assignment_id')
                ->constrained('supervision_assignments')
                ->cascadeOnDelete();
            $table->foreignId('student_pkl_id')->constrained('student_pkl')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('pkl_period_id')->constrained('pkl_periods')->cascadeOnDelete();
            $table->enum('stage', ['monitoring_1', 'monitoring_2']);
            $table->foreignId('supervision_visit_id')
                ->nullable()
                ->constrained('supervision_visits')
                ->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_pkl_id', 'stage'], 'uniq_spv_assign_spkl_stage');
            $table->index(['supervision_assignment_id', 'company_id'], 'idx_spv_assign_company');
            $table->index(['student_pkl_id', 'stage', 'completed_at'], 'idx_spv_assign_completion');
        });

        Schema::table('supervision_visits', function (Blueprint $table) {
            $table->foreignId('supervision_assignment_id')
                ->nullable()
                ->after('pkl_period_id')
                ->constrained('supervision_assignments')
                ->nullOnDelete();
        });

        Schema::create('supervision_visit_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervision_visit_id')
                ->constrained('supervision_visits')
                ->cascadeOnDelete();
            $table->foreignId('student_pkl_id')->constrained('student_pkl')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['supervision_visit_id', 'student_pkl_id'], 'uniq_spv_visit_spkl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervision_visit_students');

        Schema::table('supervision_visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervision_assignment_id');
        });

        Schema::dropIfExists('supervision_assignment_students');
        Schema::dropIfExists('supervision_assignments');
    }
};
