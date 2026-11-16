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
     * Seed Guru Pembimbing SMKN 04 Bombana (data contoh) beserta profil dan pemetaan jurusan.
     */
    public function run(): void
    {
        $school = School::where('npsn', Smkn4BombanaTrialSeeder::NPSN)->firstOrFail();

        $teachersData = [
            [
                'name' => 'La Ode Muhammad Arif, S.T.',
                'email' => 'arif.tkro@smkn4bombana.sch.id',
                'nip' => '198403122010011004',
                'gender' => 'L',
                'phone' => '082345670101',
                'address' => 'Kel. Lameroro, Kec. Rumbia, Kabupaten Bombana',
                'major_codes' => ['TKRO'],
            ],
            [
                'name' => 'Andi Suryadi, S.Kom.',
                'email' => 'andi.tjkt@smkn4bombana.sch.id',
                'nip' => '198807202014021003',
                'gender' => 'L',
                'phone' => '082345670102',
                'address' => 'Kel. Kasipute, Kec. Rumbia, Kabupaten Bombana',
                'major_codes' => ['TJKT'],
            ],
            [
                'name' => 'Wa Ode Nurhayati, S.P.',
                'email' => 'nurhayati.atph@smkn4bombana.sch.id',
                'nip' => '198605142011012006',
                'gender' => 'P',
                'phone' => '082345670103',
                'address' => 'Desa Lantawonua, Kec. Rumbia Tengah, Kabupaten Bombana',
                'major_codes' => ['ATPH'],
            ],
            [
                'name' => 'Sitti Rahmawati, S.E.',
                'email' => 'rahmawati.akl@smkn4bombana.sch.id',
                'nip' => '199001082015032005',
                'gender' => 'P',
                'phone' => '082345670104',
                'address' => 'Kel. Kasipute, Kec. Rumbia, Kabupaten Bombana',
                'major_codes' => ['AKL'],
            ],
        ];

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

            $majorIds = Major::where('school_id', $school->id)
                ->whereIn('code', $t['major_codes'])
                ->pluck('id')
                ->toArray();

            if (!empty($majorIds)) {
                $teacher->majors()->syncWithoutDetaching($majorIds);
            }
        }
    }
}
