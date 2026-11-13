<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\Profile;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Smkn1GarutTeacherSeeder extends Seeder
{
    /**
     * Seed Guru Pembimbing SMKN 1 Garut lengkap dengan profil dan pemetaan kompetensi keahlian.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20209201')->firstOrFail();

        $teachersData = [
            [
                'name' => 'Drs. Firman Karyadin',
                'email' => 'firman.karyadin@smkn1garut.sch.id',
                'nip' => '196903151995121002',
                'gender' => 'L',
                'phone' => '081223344611',
                'address' => 'Jl. Patriot No. 45, Sukagalih, Tarogong Kidul, Garut',
                'major_codes' => ['PPLG'],
            ],
            [
                'name' => 'Fajar Eka Permana, S.Kom.',
                'email' => 'fajar.permana@smkn1garut.sch.id',
                'nip' => '198807192014021003',
                'gender' => 'L',
                'phone' => '081223344612',
                'address' => 'Jl. Pembangunan No. 88, Tarogong Kidul, Garut',
                'major_codes' => ['PPLG'],
            ],
            [
                'name' => 'Erma Rahmawati, S.Pd.',
                'email' => 'erma.rahmawati@smkn1garut.sch.id',
                'nip' => '197608242006042011',
                'gender' => 'P',
                'phone' => '081223344613',
                'address' => 'Jl. Cimanuk No. 210, Pataruman, Garut',
                'major_codes' => ['TJKT'],
            ],
            [
                'name' => 'Siti Aminah, S.Pd.',
                'email' => 'siti.aminah@smkn1garut.sch.id',
                'nip' => '198104162009022005',
                'gender' => 'P',
                'phone' => '081223344614',
                'address' => 'Jl. Otista No. 102, Tarogong Kaler, Garut',
                'major_codes' => ['TJKT'],
            ],
            [
                'name' => 'Imas Rosidah, S.Pd., M.Ds.',
                'email' => 'imas.rosidah@smkn1garut.sch.id',
                'nip' => '198309112009032007',
                'gender' => 'P',
                'phone' => '081223344615',
                'address' => 'Jl. Pasundan No. 34, Kota Kulon, Garut Kota',
                'major_codes' => ['DKV'],
            ],
            [
                'name' => 'Hj. Nenden Sri Wahyuni, S.E., M.M.',
                'email' => 'nenden.sri@smkn1garut.sch.id',
                'nip' => '197405202000032004',
                'gender' => 'P',
                'phone' => '081223344616',
                'address' => 'Jl. Ahmad Yani No. 115, Garut Kota',
                'major_codes' => ['AKL'],
            ],
            [
                'name' => 'Dedi Suhendar, S.E., Ak.',
                'email' => 'dedi.suhendar@smkn1garut.sch.id',
                'nip' => '197811052008011006',
                'gender' => 'L',
                'phone' => '081223344617',
                'address' => 'Jl. Merdeka No. 78, Jayawaras, Tarogong Kidul, Garut',
                'major_codes' => ['AKL'],
            ],
            [
                'name' => 'Elis Lesiyani Dwi Pratiwi, S.Sos., M.M.',
                'email' => 'elis.lesiyani@smkn1garut.sch.id',
                'nip' => '198006152008012014',
                'gender' => 'P',
                'phone' => '081223344618',
                'address' => 'Jl. Suherman No. 56, Tarogong Kaler, Garut',
                'major_codes' => ['MPLB'],
            ],
            [
                'name' => 'Mumun Munawaroh, S.Pd.',
                'email' => 'mumun.munawaroh@smkn1garut.sch.id',
                'nip' => '197902182006042012',
                'gender' => 'P',
                'phone' => '081223344619',
                'address' => 'Jl. Bratayuda No. 22, Garut Kota',
                'major_codes' => ['MPLB'],
            ],
            [
                'name' => 'Asep Mulyana, S.E., M.M.',
                'email' => 'asep.mulyana@smkn1garut.sch.id',
                'nip' => '197509122005011004',
                'gender' => 'L',
                'phone' => '081223344620',
                'address' => 'Jl. Samarang No. 64, Tarogong Kidul, Garut',
                'major_codes' => ['PMS'],
            ],
            [
                'name' => 'Ir. Bambang Setiawan, M.T.',
                'email' => 'bambang.setiawan@smkn1garut.sch.id',
                'nip' => '197304121999031005',
                'gender' => 'L',
                'phone' => '081223344621',
                'address' => 'Jl. Raya Bayongbong No. 12, Tarogong Kidul, Garut',
                'major_codes' => ['TET', 'TLG'],
            ],
            [
                'name' => 'Apt. Dra. Sri Rahayu, M.Farm.',
                'email' => 'sri.rahayu@smkn1garut.sch.id',
                'nip' => '197203251998022003',
                'gender' => 'P',
                'phone' => '081223344622',
                'address' => 'Jl. Rumah Sakit No. 18, Sukakarya, Garut',
                'major_codes' => ['TF', 'TLM'],
            ],
            [
                'name' => 'Neneng Kurniasih, S.Si., M.Biomed.',
                'email' => 'neneng.kurniasih@smkn1garut.sch.id',
                'nip' => '198401182010012009',
                'gender' => 'P',
                'phone' => '081223344623',
                'address' => 'Jl. Terusan Pembangunan No. 10, Garut Kota',
                'major_codes' => ['TLM', 'TF'],
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
