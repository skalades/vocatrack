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
        Schema::create('supervision_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('pkl_period_id')->constrained('pkl_periods')->cascadeOnDelete();
            $table->enum('stage', ['penyerahan', 'monitoring_1', 'monitoring_2', 'penjemputan', 'insidental']);
            $table->date('visit_date');
            $table->text('student_progress_summary');
            $table->text('company_feedback');
            $table->text('follow_up_agreement')->nullable();
            $table->string('photo_with_students_path')->nullable();
            $table->string('photo_with_supervisor_path')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'company_id', 'teacher_id'], 'idx_supervision_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervision_visits');
    }
};
