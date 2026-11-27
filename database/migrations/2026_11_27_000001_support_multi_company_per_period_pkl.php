<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_pkl', function (Blueprint $table) {
            // Pastikan foreign key student_id & pkl_period_id tetap terindeks sebelum melepas unique constraint
            $table->index(['student_id', 'pkl_period_id'], 'idx_student_pkl_student_period');
            $table->dropUnique('uk_student_period');
        });
    }

    public function down(): void
    {
        Schema::table('student_pkl', function (Blueprint $table) {
            $table->unique(['student_id', 'pkl_period_id'], 'uk_student_period');
            $table->dropIndex('idx_student_pkl_student_period');
        });
    }
};
