<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Smkn1GarutStudentAndPlottingSeeder extends Seeder
{
    /**
     * Seed Siswa-siswi SMKN 1 Garut lintas jurusan dan plotting penempatan PKL aktif.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20209201')->firstOrFail();

        // 1. Buat atau Dapatkan Periode PKL Aktif SMKN 1 Garut
        $period = PklPeriod::firstOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'PKL Terpadu Vokasi Industri SMKN 1 Garut TP 2026/2027',
            ],
            [
                'start_date' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->addMonths(4)->endOfMonth()->toDateString(),
                'is_active' => true,
            ]
        );

        // 2. Daftar 28 Siswa Representatif di Seluruh 10 Kompetensi Keahlian
        $studentsData = [
            // --- PPLG ---
            [
                'name' => 'Muhammad Raihan Pratama',
                'email' => 'raihan.pplg@smkn1garut.sch.id',
                'nis' => '241101',
                'nisn' => '0071100001',
                'gender' => 'L',
                'phone' => '081398701101',
                'address' => 'Jl. Cimanuk No. 80, Pataruman, Tarogong Kidul, Garut',
                'class_name' => 'XII PPLG 1',
                'company_keyword' => 'Diskominfo',
                'teacher_email' => 'firman.karyadin@smkn1garut.sch.id',
            ],
            [
                'name' => 'Nabila Putri Salsabila',
                'email' => 'nabila.pplg@smkn1garut.sch.id',
                'nis' => '241102',
                'nisn' => '0071100002',
                'gender' => 'P',
                'phone' => '081398701102',
                'address' => 'Jl. Otista No. 44, Tarogong Kaler, Garut',
                'class_name' => 'XII PPLG 1',
                'company_keyword' => 'Telkom',
                'teacher_email' => 'fajar.permana@smkn1garut.sch.id',
            ],
            [
                'name' => 'Aditya Farhan Maulana',
                'email' => 'aditya.pplg@smkn1garut.sch.id',
                'nis' => '241103',
                'nisn' => '0071100003',
                'gender' => 'L',
                'phone' => '081398701103',
                'address' => 'Jl. Merdeka No. 50, Jayawaras, Garut',
                'class_name' => 'XII PPLG 2',
                'company_keyword' => 'Berdnations',
                'teacher_email' => 'firman.karyadin@smkn1garut.sch.id',
            ],
            [
                'name' => 'Syifa Nurfadilah',
                'email' => 'syifa.pplg@smkn1garut.sch.id',
                'nis' => '241104',
                'nisn' => '0071100004',
                'gender' => 'P',
                'phone' => '081398701104',
                'address' => 'Jl. Patriot No. 18, Tarogong Kidul, Garut',
                'class_name' => 'XII PPLG 2',
                'company_keyword' => 'Statistik',
                'teacher_email' => 'fajar.permana@smkn1garut.sch.id',
            ],

            // --- TJKT ---
            [
                'name' => 'Ilham Ramadhan',
                'email' => 'ilham.tjkt@smkn1garut.sch.id',
                'nis' => '241201',
                'nisn' => '0071200001',
                'gender' => 'L',
                'phone' => '081398701201',
                'address' => 'Jl. Pembangunan No. 120, Tarogong Kidul, Garut',
                'class_name' => 'XII TJKT 1',
                'company_keyword' => 'Telkom',
                'teacher_email' => 'erma.rahmawati@smkn1garut.sch.id',
            ],
            [
                'name' => 'Annisa Lutfiah',
                'email' => 'annisa.tjkt@smkn1garut.sch.id',
                'nis' => '241202',
                'nisn' => '0071200002',
                'gender' => 'P',
                'phone' => '081398701202',
                'address' => 'Jl. Terusan Pembangunan No. 35, Garut Kota',
                'class_name' => 'XII TJKT 1',
                'company_keyword' => 'Diskominfo',
                'teacher_email' => 'siti.aminah@smkn1garut.sch.id',
            ],
            [
                'name' => 'Dimas Arya Putra',
                'email' => 'dimas.tjkt@smkn1garut.sch.id',
                'nis' => '241203',
                'nisn' => '0071200003',
                'gender' => 'L',
                'phone' => '081398701203',
                'address' => 'Jl. Sukagalih No. 90, Tarogong Kidul, Garut',
                'class_name' => 'XII TJKT 2',
                'company_keyword' => 'Telkom',
                'teacher_email' => 'erma.rahmawati@smkn1garut.sch.id',
            ],
            [
                'name' => 'Zahra Nurul Izzah',
                'email' => 'zahra.tjkt@smkn1garut.sch.id',
                'nis' => '241204',
                'nisn' => '0071200004',
                'gender' => 'P',
                'phone' => '081398701204',
                'address' => 'Jl. Pasundan No. 15, Garut Kota',
                'class_name' => 'XII TJKT 2',
                'company_keyword' => 'Diskominfo',
                'teacher_email' => 'siti.aminah@smkn1garut.sch.id',
            ],

            // --- DKV ---
            [
                'name' => 'Fauzan Maulana',
                'email' => 'fauzan.dkv@smkn1garut.sch.id',
                'nis' => '241301',
                'nisn' => '0071300001',
                'gender' => 'L',
                'phone' => '081398701301',
                'address' => 'Jl. Bratayuda No. 40, Garut Kota',
                'class_name' => 'XII DKV 1',
                'company_keyword' => 'Berdnations',
                'teacher_email' => 'imas.rosidah@smkn1garut.sch.id',
            ],
            [
                'name' => 'Tiara Cantika',
                'email' => 'tiara.dkv@smkn1garut.sch.id',
                'nis' => '241302',
                'nisn' => '0071300002',
                'gender' => 'P',
                'phone' => '081398701302',
                'address' => 'Jl. Suherman No. 67, Tarogong Kaler, Garut',
                'class_name' => 'XII DKV 2',
                'company_keyword' => 'Telkom',
                'teacher_email' => 'imas.rosidah@smkn1garut.sch.id',
            ],

            // --- AKL ---
            [
                'name' => 'Rizky Wahyudi',
                'email' => 'rizky.akl@smkn1garut.sch.id',
                'nis' => '241401',
                'nisn' => '0071400001',
                'gender' => 'L',
                'phone' => '081398701401',
                'address' => 'Jl. Ahmad Yani No. 55, Garut Kota',
                'class_name' => 'XII AKL 1',
                'company_keyword' => 'Bank bjb',
                'teacher_email' => 'nenden.sri@smkn1garut.sch.id',
            ],
            [
                'name' => 'Allya Nur Azizah',
                'email' => 'allya.akl@smkn1garut.sch.id',
                'nis' => '241402',
                'nisn' => '0071400002',
                'gender' => 'P',
                'phone' => '081398701402',
                'address' => 'Jl. Merdeka No. 110, Garut',
                'class_name' => 'XII AKL 1',
                'company_keyword' => 'Statistik',
                'teacher_email' => 'dedi.suhendar@smkn1garut.sch.id',
            ],
            [
                'name' => 'Fahmi Hidayat',
                'email' => 'fahmi.akl@smkn1garut.sch.id',
                'nis' => '241403',
                'nisn' => '0071400003',
                'gender' => 'L',
                'phone' => '081398701403',
                'address' => 'Jl. Samarang No. 25, Tarogong Kidul, Garut',
                'class_name' => 'XII AKL 2',
                'company_keyword' => 'Pertanahan',
                'teacher_email' => 'nenden.sri@smkn1garut.sch.id',
            ],
            [
                'name' => 'Siti Sarah Maulida',
                'email' => 'siti.akl@smkn1garut.sch.id',
                'nis' => '241404',
                'nisn' => '0071400004',
                'gender' => 'P',
                'phone' => '081398701404',
                'address' => 'Jl. Raya Bayongbong No. 19, Garut',
                'class_name' => 'XII AKL 2',
                'company_keyword' => 'Yogya',
                'teacher_email' => 'dedi.suhendar@smkn1garut.sch.id',
            ],

            // --- MPLB ---
            [
                'name' => 'Dea Ananda',
                'email' => 'dea.mplb@smkn1garut.sch.id',
                'nis' => '241501',
                'nisn' => '0071500001',
                'gender' => 'P',
                'phone' => '081398701501',
                'address' => 'Jl. Cimanuk No. 140, Pataruman, Garut',
                'class_name' => 'XII MPLB 1',
                'company_keyword' => 'Pertanahan',
                'teacher_email' => 'elis.lesiyani@smkn1garut.sch.id',
            ],
            [
                'name' => 'Aldi Firmansyah',
                'email' => 'aldi.mplb@smkn1garut.sch.id',
                'nis' => '241502',
                'nisn' => '0071500002',
                'gender' => 'L',
                'phone' => '081398701502',
                'address' => 'Jl. Patriot No. 82, Sukagalih, Garut',
                'class_name' => 'XII MPLB 1',
                'company_keyword' => 'Diskominfo',
                'teacher_email' => 'mumun.munawaroh@smkn1garut.sch.id',
            ],
            [
                'name' => 'Gita Pratiwi',
                'email' => 'gita.mplb@smkn1garut.sch.id',
                'nis' => '241503',
                'nisn' => '0071500003',
                'gender' => 'P',
                'phone' => '081398701503',
                'address' => 'Jl. Pembangunan No. 65, Garut Kota',
                'class_name' => 'XII MPLB 2',
                'company_keyword' => 'Bank bjb',
                'teacher_email' => 'elis.lesiyani@smkn1garut.sch.id',
            ],
            [
                'name' => 'Bagas Saputra',
                'email' => 'bagas.mplb@smkn1garut.sch.id',
                'nis' => '241504',
                'nisn' => '0071500004',
                'gender' => 'L',
                'phone' => '081398701504',
                'address' => 'Jl. Sukakarya No. 12, Tarogong Kidul, Garut',
                'class_name' => 'XII MPLB 2',
                'company_keyword' => 'Slamet',
                'teacher_email' => 'mumun.munawaroh@smkn1garut.sch.id',
            ],

            // --- PMS ---
            [
                'name' => 'Kevin Sanjaya',
                'email' => 'kevin.pms@smkn1garut.sch.id',
                'nis' => '241601',
                'nisn' => '0071600001',
                'gender' => 'L',
                'phone' => '081398701601',
                'address' => 'Jl. Pramuka No. 42, Garut Kota',
                'class_name' => 'XII PMS 1',
                'company_keyword' => 'Yogya',
                'teacher_email' => 'asep.mulyana@smkn1garut.sch.id',
            ],
            [
                'name' => 'Melani Putri',
                'email' => 'melani.pms@smkn1garut.sch.id',
                'nis' => '241602',
                'nisn' => '0071600002',
                'gender' => 'P',
                'phone' => '081398701602',
                'address' => 'Jl. Otista No. 120, Tarogong Kaler, Garut',
                'class_name' => 'XII PMS 2',
                'company_keyword' => 'Bank bjb',
                'teacher_email' => 'asep.mulyana@smkn1garut.sch.id',
            ],

            // --- TET ---
            [
                'name' => 'Fikri Haikal',
                'email' => 'fikri.tet@smkn1garut.sch.id',
                'nis' => '241701',
                'nisn' => '0071700001',
                'gender' => 'L',
                'phone' => '081398701701',
                'address' => 'Jl. Raya Samarang No. 140, Garut',
                'class_name' => 'XII TET 1',
                'company_keyword' => 'Geothermal',
                'teacher_email' => 'bambang.setiawan@smkn1garut.sch.id',
            ],
            [
                'name' => 'Rian Ardiansyah',
                'email' => 'rian.tet@smkn1garut.sch.id',
                'nis' => '241702',
                'nisn' => '0071700002',
                'gender' => 'L',
                'phone' => '081398701702',
                'address' => 'Jl. Kamojang No. 15, Samarang, Garut',
                'class_name' => 'XII TET 1',
                'company_keyword' => 'Geothermal',
                'teacher_email' => 'bambang.setiawan@smkn1garut.sch.id',
            ],

            // --- TF ---
            [
                'name' => 'Salma Aulia',
                'email' => 'salma.tf@smkn1garut.sch.id',
                'nis' => '241801',
                'nisn' => '0071800001',
                'gender' => 'P',
                'phone' => '081398701801',
                'address' => 'Jl. Cimanuk No. 45, Jayawaras, Garut',
                'class_name' => 'XII TF 1',
                'company_keyword' => 'Kimia Farma',
                'teacher_email' => 'sri.rahayu@smkn1garut.sch.id',
            ],
            [
                'name' => 'Nadya Shafira',
                'email' => 'nadya.tf@smkn1garut.sch.id',
                'nis' => '241802',
                'nisn' => '0071800002',
                'gender' => 'P',
                'phone' => '081398701802',
                'address' => 'Jl. Rumah Sakit No. 30, Sukakarya, Garut',
                'class_name' => 'XII TF 1',
                'company_keyword' => 'Slamet',
                'teacher_email' => 'sri.rahayu@smkn1garut.sch.id',
            ],

            // --- TLM ---
            [
                'name' => 'Farrel Alghifari',
                'email' => 'farrel.tlm@smkn1garut.sch.id',
                'nis' => '241901',
                'nisn' => '0071900001',
                'gender' => 'L',
                'phone' => '081398701901',
                'address' => 'Jl. Sukakarya No. 5, Tarogong Kidul, Garut',
                'class_name' => 'XII TLM 1',
                'company_keyword' => 'Slamet',
                'teacher_email' => 'neneng.kurniasih@smkn1garut.sch.id',
            ],
            [
                'name' => 'Aisyah Rahmadani',
                'email' => 'aisyah.tlm@smkn1garut.sch.id',
                'nis' => '241902',
                'nisn' => '0071900002',
                'gender' => 'P',
                'phone' => '081398701902',
                'address' => 'Jl. Terusan Cimanuk No. 88, Garut',
                'class_name' => 'XII TLM 1',
                'company_keyword' => 'Kimia Farma',
                'teacher_email' => 'neneng.kurniasih@smkn1garut.sch.id',
            ],

            // --- TLG ---
            [
                'name' => 'Bayu Setiawan',
                'email' => 'bayu.tlg@smkn1garut.sch.id',
                'nis' => '242001',
                'nisn' => '0072000001',
                'gender' => 'L',
                'phone' => '081398702001',
                'address' => 'Jl. Pramuka No. 60, Garut Kota',
                'class_name' => 'XII TLG 1',
                'company_keyword' => 'Yogya',
                'teacher_email' => 'bambang.setiawan@smkn1garut.sch.id',
            ],
            [
                'name' => 'Arya Wiguna',
                'email' => 'arya.tlg@smkn1garut.sch.id',
                'nis' => '242002',
                'nisn' => '0072000002',
                'gender' => 'L',
                'phone' => '081398702002',
                'address' => 'Jl. Samarang No. 102, Garut',
                'class_name' => 'XII TLG 1',
                'company_keyword' => 'Geothermal',
                'teacher_email' => 'bambang.setiawan@smkn1garut.sch.id',
            ],
        ];

        foreach ($studentsData as $s) {
            // A. User Akun Siswa
            $user = User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'school_id' => $school->id,
                    'name' => $s['name'],
                    'password' => Hash::make('password'),
                    'role' => 'siswa',
                    'is_active' => true,
                ]
            );

            // B. Profil Siswa
            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'gender' => $s['gender'],
                    'phone' => $s['phone'],
                    'address' => $s['address'],
                ]
            );

            // C. Kelas Siswa
            $class = SchoolClass::where('school_id', $school->id)
                ->where('name', $s['class_name'])
                ->firstOrFail();

            // D. Model Student
            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'class_id' => $class->id,
                    'nis' => $s['nis'],
                    'nisn' => $s['nisn'],
                    'parent_token' => Str::random(32),
                    'is_parent_portal_active' => true,
                ]
            );

            // E. Temukan Perusahaan / Tempat PKL
            $company = Company::where('school_id', $school->id)
                ->where('name', 'like', '%' . $s['company_keyword'] . '%')
                ->firstOrFail();

            // F. Temukan Supervisor DUDI
            $supervisor = Supervisor::where('company_id', $company->id)->first();

            // G. Temukan Guru Pembimbing
            $teacherUser = User::where('email', $s['teacher_email'])->firstOrFail();
            $teacher = Teacher::where('user_id', $teacherUser->id)->firstOrFail();

            // H. Plotting StudentPkl (Penempatan PKL Aktif)
            StudentPkl::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'pkl_period_id' => $period->id,
                ],
                [
                    'school_id' => $school->id,
                    'company_id' => $company->id,
                    'teacher_id' => $teacher->id,
                    'supervisor_id' => $supervisor?->id,
                    'status' => 'active',
                ]
            );
        }
    }
}
