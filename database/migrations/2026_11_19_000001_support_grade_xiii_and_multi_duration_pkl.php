<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Perluas grade_level pada tabel classes untuk mendukung Kelas XIII
        Schema::table('classes', function (Blueprint $table) {
            $table->string('grade_level', 10)->default('XII')->change();
        });

        // 2. Tambahkan pengaturan program dan durasi magang pada tabel majors
        Schema::table('majors', function (Blueprint $table) {
            $table->unsignedTinyInteger('program_duration_years')->default(3)->after('name');
            $table->unsignedTinyInteger('default_pkl_duration_months')->default(6)->after('program_duration_years');
        });

        // 3. Tambahkan default tanggal magang pada perusahaan (DU/DI) jika memiliki jadwal spesifik
        Schema::table('companies', function (Blueprint $table) {
            $table->date('default_start_date')->nullable()->after('work_days');
            $table->date('default_end_date')->nullable()->after('default_start_date');
        });

        // 4. Tambahkan tanggal mulai & berakhir spesifik per penempatan di student_pkl
        Schema::table('student_pkl', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('supervisor_id');
            $table->date('end_date')->nullable()->after('start_date');
            $table->index(['school_id', 'status', 'start_date', 'end_date'], 'idx_spkl_dates');
        });

        // 5. Tambahkan status akademik dan tahun kelulusan pada tabel students
        Schema::table('students', function (Blueprint $table) {
            $table->string('academic_status', 20)->default('active')->after('is_parent_portal_active');
            $table->string('graduation_year', 10)->nullable()->after('academic_status');
            $table->index(['academic_status'], 'idx_students_academic_status');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_academic_status');
            $table->dropColumn(['academic_status', 'graduation_year']);
        });

        Schema::table('student_pkl', function (Blueprint $table) {
            $table->dropIndex('idx_spkl_dates');
            $table->dropColumn(['start_date', 'end_date']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['default_start_date', 'default_end_date']);
        });

        Schema::table('majors', function (Blueprint $table) {
            $table->dropColumn(['program_duration_years', 'default_pkl_duration_months']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->string('grade_level', 10)->default('XII')->change();
        });
    }
};
