<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\Smkn2GarutAdditionalTeacherSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Smkn2GarutAdditionalTeacherSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_only_requested_teachers_and_excludes_specified_six(): void
    {
        $school = School::create([
            'npsn' => '20209203',
            'name' => 'SMK Negeri 2 Garut',
            'address' => 'Jl. Prof. Dr. Suherman No. 90, Garut',
            'phone' => '(0262) 233131',
            'email' => 'info@smkn2garut.sch.id',
            'is_active' => true,
        ]);

        // Simulasikan 1 guru sudah ada sebelumnya agar teruji tidak duplikat
        $existingUser = User::create([
            'school_id' => $school->id,
            'name' => 'Dedeh Kurniasih, S.Pd., M.Pd',
            'email' => 'dedeh.kurniasih@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        Teacher::create(['user_id' => $existingUser->id]);

        $this->seed(Smkn2GarutAdditionalTeacherSeeder::class);

        $this->assertEquals(19, Teacher::whereHas('user', fn ($q) => $q->where('school_id', $school->id))->count());

        // Jalankan kedua kali -> tetap 19 (idempoten)
        $this->seed(Smkn2GarutAdditionalTeacherSeeder::class);
        $this->assertEquals(19, Teacher::whereHas('user', fn ($q) => $q->where('school_id', $school->id))->count());

        // Pastikan 6 guru yang dikecualikan tidak ikut dibuat
        foreach (['Herdi', 'Cahya', 'Danang', 'Adbel', 'Yandi', 'Abdul Hakim'] as $excluded) {
            $this->assertDatabaseMissing('users', [
                'school_id' => $school->id,
                'name' => $excluded,
            ]);
            $this->assertFalse(
                User::where('school_id', $school->id)->where('name', 'like', "%{$excluded}%")->exists(),
                "Guru {$excluded} seharusnya tidak dibuat."
            );
        }
    }
}
