<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('major_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('major_id')->constrained('majors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teacher_id', 'major_id'], 'uq_teacher_major');
        });

        // Auto-assign existing teachers based on email / historical data
        $pspt = DB::table('majors')->where('code', 'PSPT')->first();
        $gp = DB::table('majors')->where('code', 'GP')->first();

        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->select('teachers.id as teacher_id', 'users.email', 'users.name')
            ->get();

        foreach ($teachers as $t) {
            if ($pspt && (str_contains($t->email, 'pspt') || str_contains($t->name, 'Dadang') || str_contains($t->name, 'Rina'))) {
                DB::table('major_teacher')->insertOrIgnore([
                    'teacher_id' => $t->teacher_id,
                    'major_id' => $pspt->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($gp && (str_contains($t->email, 'guru1') || str_contains($t->email, 'guru2') || str_contains($t->name, 'Yayan') || str_contains($t->name, 'Budi'))) {
                DB::table('major_teacher')->insertOrIgnore([
                    'teacher_id' => $t->teacher_id,
                    'major_id' => $gp->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('major_teacher');
    }
};
