<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('majors', function (Blueprint $table) {
            if (!Schema::hasColumn('majors', 'head_teacher_id')) {
                $table->foreignId('head_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'created_by_user_id')) {
                $table->dropForeign(['created_by_user_id']);
                $table->dropColumn('created_by_user_id');
            }
        });

        Schema::table('majors', function (Blueprint $table) {
            if (Schema::hasColumn('majors', 'head_teacher_id')) {
                $table->dropForeign(['head_teacher_id']);
                $table->dropColumn('head_teacher_id');
            }
        });
    }
};
