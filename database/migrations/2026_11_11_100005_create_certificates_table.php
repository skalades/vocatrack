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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_pkl_id')->unique()->constrained('student_pkl')->cascadeOnDelete();
            $table->char('uuid', 36)->unique();
            $table->string('certificate_number', 100);
            $table->decimal('final_score', 5, 2);
            $table->enum('final_predicate', ['Sangat Baik', 'Baik', 'Cukup', 'Kurang']);
            $table->date('issued_date');
            $table->string('pdf_path', 255)->nullable();
            $table->timestamps();

            $table->index(['school_id', 'certificate_number'], 'idx_cert_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
