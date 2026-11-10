<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->timestamps();

            $table->index('school_id', 'idx_majors_school');
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('major_id')->constrained('majors')->restrictOnDelete();
            $table->string('name', 50);
            $table->enum('grade_level', ['X', 'XI', 'XII'])->default('XII');
            $table->string('academic_year', 15);
            $table->timestamps();

            $table->index('school_id', 'idx_classes_school');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
        Schema::dropIfExists('majors');
    }
};
