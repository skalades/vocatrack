<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\Profile;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Smkn10GarutTeacherSeeder extends Seeder
{
    /**
     * Seed Guru Pembimbing Produktif SMKN 10 Garut lengkap dengan profil dan pemetaan jurusan.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20262128')->firstOrFail();

        $teachersData = [
            [
                'name' => 'Satia, S.T.',
                'email' => 'satia.otomotif@smkn10garut.sch.id',
                'nip' => '198206082009021002',
                'gender' => 'L',
                'phone' => '081223344711',
                'address' => 'Jl. Raya Cilawu No. 60, Cilawu, Garut',
                'major_codes' => ['TKRO'],
            ],
            [
                'name' => 'Adi Wildan Ali, S.Pd.',
                'email' => 'adi.wildan@smkn10garut.sch.id',
                'nip' => '198705142012011004',
                'gender' => 'L',
                'phone' => '081223344712',
                'address' => 'Jl. Bratayuda No. 70, Garut Kota',
                'major_codes' => ['TKRO', 'TBSM'],
            ],
            [
                'name' => 'Dadan Herdiana, S.T.',
                'email' => 'dadan.herdiana@smkn10garut.sch.id',
                'nip' => '198503222010011008',
                'gender' => 'L',
                'phone' => '081223344713',
                'address' => 'Jl. Sukagalih No. 45, Tarogong Kidul, Garut',
                'major_codes' => ['TBSM'],
            ],
            [
                'name' => 'Muhammad Ihsan Sibaweh, S.ST., M.Pd.',
                'email' => 'ihsan.sibaweh@smkn10garut.sch.id',
                'nip' => '198308152009011003',
                'gender' => 'L',
                'phone' => '081223344714',
                'address' => 'Perum Cilawu Indah Blok B No. 12, Cilawu, Garut',
                'major_codes' => ['TJKT'],
            ],
            [
                'name' => 'Anita, S.P.',
                'email' => 'anita@smkn10garut.sch.id',
                'nip' => '196906132007012005',
                'gender' => 'P',
                'phone' => '081223344715',
                'address' => 'Jl. Raya Garut-Tasik Km. 7, Cilawu, Garut',
                'major_codes' => ['TJKT'],
            ],
            [
                'name' => 'Asep Saepuloh, S.Pd.',
                'email' => 'asep.saepuloh@smkn10garut.sch.id',
                'nip' => '198411202010011006',
                'gender' => 'L',
                'phone' => '081223344716',
                'address' => 'Jl. Pasundan No. 89, Garut Kota',
                'major_codes' => ['DKV'],
            ],
            [
                'name' => 'Yanti Mulyani, S.E., M.M.',
                'email' => 'yanti.mulyani@smkn10garut.sch.id',
                'nip' => '197809142006042015',
                'gender' => 'P',
                'phone' => '081223344717',
                'address' => 'Jl. Cimanuk No. 110, Tarogong Kidul, Garut',
                'major_codes' => ['AKL'],
            ],
            [
                'name' => 'Rini Anggraeni, S.E., Sy.',
                'email' => 'rini.anggraeni@smkn10garut.sch.id',
                'nip' => '198604122011012007',
                'gender' => 'P',
                'phone' => '081223344718',
                'address' => 'Jl. Ahmad Yani No. 145, Garut Kota',
                'major_codes' => ['LPS'],
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
                [
                    'gender' => $t['gender'],
                    'phone' => $t['phone'],
                    'address' => $t['address'],
                ]
            );

            $teacher = Teacher::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nip' => $t['nip'],
                ]
            );

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
