<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'homeroom_teacher_id')) {
                $table->foreignId('homeroom_teacher_id')
                    ->nullable()
                    ->after('major_id')
                    ->constrained('teachers')
                    ->nullOnDelete();

                $table->index(['school_id', 'homeroom_teacher_id'], 'idx_classes_school_homeroom');
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'homeroom_teacher_id')) {
                $table->dropIndex('idx_classes_school_homeroom');
                $table->dropForeign(['homeroom_teacher_id']);
                $table->dropColumn('homeroom_teacher_id');
            }
        });
    }
};
