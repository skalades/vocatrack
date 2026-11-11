<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_major', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('major_id')->constrained('majors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'major_id'], 'uq_company_major');
        });

        // Backfill existing seeded companies with GP (Teknik Geologi Pertambangan)
        $gp = DB::table('majors')->where('code', 'GP')->first();
        $log = DB::table('majors')->where('code', 'LOG')->first();
        if ($gp) {
            $companies = DB::table('companies')->get();
            foreach ($companies as $c) {
                DB::table('company_major')->insertOrIgnore([
                    'company_id' => $c->id,
                    'major_id' => $gp->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($log && str_contains($c->name, 'Aneka')) {
                    DB::table('company_major')->insertOrIgnore([
                        'company_id' => $c->id,
                        'major_id' => $log->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_major');
    }
};
