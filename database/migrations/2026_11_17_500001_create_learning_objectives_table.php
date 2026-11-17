<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('major_id')->constrained('majors')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('element_name', 100)->nullable();
            $table->unsignedInteger('target_hours')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['school_id', 'major_id'], 'idx_lo_school_major');
            $table->index(['major_id', 'is_active'], 'idx_lo_major_active');
        });

        Schema::create('journal_learning_objective', function (Blueprint $table) {
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('learning_objective_id')->constrained('learning_objectives')->cascadeOnDelete();
            $table->primary(['journal_id', 'learning_objective_id'], 'pk_journal_lo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_learning_objective');
        Schema::dropIfExists('learning_objectives');
    }
};
