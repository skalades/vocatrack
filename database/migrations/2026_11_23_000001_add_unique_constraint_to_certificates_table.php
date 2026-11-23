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
        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                // Menambahkan unique constraint untuk mencegah race condition duplikasi nomor sertifikat per sekolah
                if (!Schema::hasIndex('certificates', 'uk_school_certificate_number')) {
                    $table->unique(['school_id', 'certificate_number'], 'uk_school_certificate_number');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                if (Schema::hasIndex('certificates', 'uk_school_certificate_number')) {
                    $table->dropUnique('uk_school_certificate_number');
                }
            });
        }
    }
};
