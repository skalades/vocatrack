<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_pkl_id')->unique()->constrained('student_pkl')->cascadeOnDelete();
            $table->decimal('score_dudi_discipline', 5, 2)->default(0.00);
            $table->decimal('score_dudi_technical', 5, 2)->default(0.00);
            $table->decimal('score_dudi_initiative', 5, 2)->default(0.00);
            $table->decimal('score_teacher_report', 5, 2)->default(0.00);
            $table->decimal('final_score', 5, 2)->default(0.00);
            $table->text('notes_dudi')->nullable();
            $table->text('notes_teacher')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
