<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\Profile;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Smkn4BombanaTeacherSeeder extends Seeder
{
    /**
     * Seed Guru Pembimbing produktif Geologi Pertambangan SMKN 04 Bombana.
     * Nama & NIP adalah data contoh (data guru riil tidak dipublikasikan) — ganti dengan data sekolah.
     */
    public function run(): void
    {
        $school = School::where('npsn', Smkn4BombanaTrialSeeder::NPSN)->firstOrFail();

        $teachersData = [
            [
                'name' => 'La Ode Hasrun, S.T.',
                'email' => 'hasrun.gp@smkn4bombana.sch.id',
                'nip' => '198502142011011005',
                'gender' => 'L',
                'phone' => '082345670101',
                'address' => 'Desa Baliara, Kec. Kabaena Barat, Kabupaten Bombana',
            ],
            [
                'name' => 'Wa Ode Sartina, S.T.',
                'email' => 'sartina.gp@smkn4bombana.sch.id',
                'nip' => '198808232019032008',
                'gender' => 'P',
                'phone' => '082345670102',
                'address' => 'Kel. Tedubara, Kec. Kabaena Utara, Kabupaten Bombana',
            ],
            [
                'name' => 'Muhammad Akbar, S.Pd.',
                'email' => 'akbar.gp@smkn4bombana.sch.id',
                'nip' => '199105102022211004',
                'gender' => 'L',
                'phone' => '082345670103',
                'address' => 'Kel. Rahantari, Kec. Kabaena Timur, Kabupaten Bombana',
            ],
        ];

        $gpId = Major::where('school_id', $school->id)->where('code', 'GP')->value('id');

        foreach ($teachersData as $t) {
            $user = User::firstOrCreate(
                ['email' => $t['email']],
                [
                    'school_id' => $school->id,
                    'name' => $t['name'],
                    'password' => Hash::make('password'),
                    'role' => 'guru-pembimbing',
                    'is_active' => true,
                ]
            );

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                ['gender' => $t['gender'], 'phone' => $t['phone'], 'address' => $t['address']]
            );

            $teacher = Teacher::firstOrCreate(['user_id' => $user->id], ['nip' => $t['nip']]);

            if ($gpId) {
                $teacher->majors()->syncWithoutDetaching([$gpId]);
            }
        }
    }
}
