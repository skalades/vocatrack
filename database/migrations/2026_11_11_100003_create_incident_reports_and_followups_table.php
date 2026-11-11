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
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_pkl_id')->constrained('student_pkl')->onDelete('cascade');
            $table->enum('category', ['administrative', 'curriculum_mismatch', 'interpersonal', 'safety_health', 'emergency']);
            $table->enum('urgency', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('subject', 200);
            $table->text('description');
            $table->string('attachment_path', 255)->nullable();
            $table->enum('status', ['submitted', 'in_progress', 'resolved', 'dismissed'])->default('submitted');
            $table->foreignId('handler_id')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status', 'urgency'], 'idx_incidents_queue');
        });

        Schema::create('incident_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_report_id')->constrained('incident_reports')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('action_note');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_followups');
        Schema::dropIfExists('incident_reports');
    }
};
