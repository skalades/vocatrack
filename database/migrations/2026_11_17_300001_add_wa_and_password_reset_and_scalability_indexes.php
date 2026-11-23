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
        // 1. Tambah kolom WhatsApp dan preferensi notifikasi pada tabel users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'wa_number')) {
                $table->string('wa_number', 25)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable()->after('must_change_password');
            }
        });

        // 2. Tabel permintaan reset password siswa (ditangani guru pembimbing)
        if (!Schema::hasTable('student_password_reset_requests')) {
            Schema::create('student_password_reset_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
                $table->string('email');
                $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending');
                $table->text('notes')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'status'], 'idx_sprr_school_status');
                $table->index(['teacher_id', 'status'], 'idx_sprr_teacher_status');
                $table->index(['student_id', 'status'], 'idx_sprr_student_status');
            });
        }

        // 3. Review & penambahan indeks performa tinggi untuk attendances & journals (P1-2 Scalability)
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!Schema::hasIndex('attendances', 'idx_attendances_spkl_status')) {
                    $table->index(['student_pkl_id', 'status'], 'idx_attendances_spkl_status');
                }
                if (!Schema::hasIndex('attendances', 'idx_attendances_school_status_date')) {
                    $table->index(['school_id', 'status', 'date'], 'idx_attendances_school_status_date');
                }
            });
        }

        if (Schema::hasTable('journals')) {
            Schema::table('journals', function (Blueprint $table) {
                if (!Schema::hasIndex('journals', 'idx_journals_spkl_status')) {
                    $table->index(['student_pkl_id', 'status'], 'idx_journals_spkl_status');
                }
                if (!Schema::hasIndex('journals', 'idx_journals_school_status_date')) {
                    $table->index(['school_id', 'status', 'date'], 'idx_journals_school_status_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('journals')) {
            Schema::table('journals', function (Blueprint $table) {
                if (Schema::hasIndex('journals', 'idx_journals_spkl_status')) {
                    $table->dropIndex('idx_journals_spkl_status');
                }
                if (Schema::hasIndex('journals', 'idx_journals_school_status_date')) {
                    $table->dropIndex('idx_journals_school_status_date');
                }
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (Schema::hasIndex('attendances', 'idx_attendances_spkl_status')) {
                    $table->dropIndex('idx_attendances_spkl_status');
                }
                if (Schema::hasIndex('attendances', 'idx_attendances_school_status_date')) {
                    $table->dropIndex('idx_attendances_school_status_date');
                }
            });
        }

        Schema::dropIfExists('student_password_reset_requests');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'notification_preferences')) {
                $table->dropColumn('notification_preferences');
            }
            if (Schema::hasColumn('users', 'wa_number')) {
                $table->dropColumn('wa_number');
            }
        });
    }
};
