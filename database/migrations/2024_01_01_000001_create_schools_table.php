<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 10)->unique();
            $table->string('name', 150);
            $table->text('address');
            $table->string('phone', 20);
            $table->string('email', 100);
            $table->string('logo_path', 255)->nullable();
            $table->string('stamp_path', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('key', 50);
            $table->text('value');
            $table->timestamps();

            $table->unique(['school_id', 'key'], 'uk_school_setting');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
        Schema::dropIfExists('schools');
    }
};
