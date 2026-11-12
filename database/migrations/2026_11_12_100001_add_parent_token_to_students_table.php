<?php

use App\Models\Student;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('parent_token', 64)->nullable()->unique()->after('nisn');
            $table->boolean('is_parent_portal_active')->default(true)->after('parent_token');
        });

        // Generate tokens for existing students
        Student::whereNull('parent_token')->each(function ($student) {
            $student->update([
                'parent_token' => Str::random(32),
                'is_parent_portal_active' => true,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['parent_token', 'is_parent_portal_active']);
        });
    }
};
