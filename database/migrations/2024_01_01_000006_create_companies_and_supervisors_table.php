<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('address');
            $table->string('contact_person', 100);
            $table->string('phone', 20);
            $table->boolean('gps_enabled')->default(false);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->unsignedInteger('radius_meters')->default(100);
            $table->boolean('override_hours')->default(false);
            $table->json('custom_work_hours')->nullable();
            $table->enum('work_days', ['5_days', '6_days'])->default('5_days');
            $table->timestamps();

            $table->index('school_id', 'idx_companies_school');
        });

        Schema::create('supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('position', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisors');
        Schema::dropIfExists('companies');
    }
};
