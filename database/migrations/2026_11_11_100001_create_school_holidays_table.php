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
        Schema::create('school_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('title', 150);
            $table->date('holiday_date');
            $table->string('description', 255)->nullable();
            $table->boolean('is_national')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'holiday_date'], 'uk_school_holiday');
            $table->index(['school_id', 'holiday_date'], 'idx_holiday_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_holidays');
    }
};
