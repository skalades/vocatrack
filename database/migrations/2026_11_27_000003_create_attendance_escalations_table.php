<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_pkl_id')->constrained('student_pkl')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('escalated_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->json('missing_types');
            $table->text('note')->nullable();
            $table->boolean('sent_in_app')->default(true);
            $table->boolean('sent_whatsapp')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'date'], 'idx_att_escalations_school_date');
            $table->index(['student_pkl_id', 'date'], 'idx_att_escalations_spkl_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_escalations');
    }
};
