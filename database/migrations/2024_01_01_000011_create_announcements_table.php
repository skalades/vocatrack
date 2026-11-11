<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('category', 50)->default('umum'); // 'umum', 'penting', 'kegiatan', 'akademik', 'libur'
            $table->string('image_path')->nullable();
            $table->string('target_role', 50)->default('all'); // 'all', 'siswa', 'guru-pembimbing', 'pembimbing-dudi'
            $table->foreignId('target_major_id')->nullable()->constrained('majors')->nullOnDelete();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'is_active', 'published_at']);
            $table->index(['school_id', 'target_role']);
            $table->index(['school_id', 'target_major_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
