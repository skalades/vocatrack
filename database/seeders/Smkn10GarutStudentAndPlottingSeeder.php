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

class Smkn10GarutStudentAndPlottingSeeder extends Seeder
{
    /**
     * Seed Siswa-siswi SMKN 10 Garut lintas jurusan dan plotting penempatan PKL aktif.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20262128')->firstOrFail();

        // 1. Buat atau Dapatkan Periode PKL Aktif SMKN 10 Garut
        $period = PklPeriod::firstOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'PKL Terpadu Vokasi Industri & Perbankan Syariah SMKN 10 Garut TP 2026/2027',
            ],
            [
                'start_date' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->addMonths(4)->endOfMonth()->toDateString(),
                'is_active' => true,
            ]
        );

        // 2. Daftar 24 Siswa Representatif Lintas 6 Jurusan SMKN 10 Garut
        $studentsData = [
            // --- TKRO ---
            [
                'name' => 'Ilham Kurniawan',
                'email' => 'ilham.tkro@smkn10garut.sch.id',
                'nis' => '242101',
                'nisn' => '0072100001',
                'gender' => 'L',
                'phone' => '081398702101',
                'address' => 'Jl. Raya Cilawu No. 12, Cilawu, Garut',
                'class_name' => 'XII TKRO 1',
                'company_keyword' => 'Auto2000',
                'teacher_email' => 'satia.otomotif@smkn10garut.sch.id',
            ],
            [
                'name' => 'Diki Candra',
                'email' => 'diki.tkro@smkn10garut.sch.id',
                'nis' => '242102',
                'nisn' => '0072100002',
                'gender' => 'L',
                'phone' => '081398702102',
                'address' => 'Jl. Bratayuda No. 90, Garut Kota',
                'class_name' => 'XII TKRO 1',
                'company_keyword' => 'Bratayudha',
                'teacher_email' => 'adi.wildan@smkn10garut.sch.id',
            ],
            [
                'name' => 'Yoga Pratama',
                'email' => 'yoga.tkro@smkn10garut.sch.id',
                'nis' => '242103',
                'nisn' => '0072100003',
                'gender' => 'L',
                'phone' => '081398702103',
                'address' => 'Desa Ngamplang, Cilawu, Garut',
                'class_name' => 'XII TKRO 2',
                'company_keyword' => 'Sinar Otomotif',
                'teacher_email' => 'satia.otomotif@smkn10garut.sch.id',
            ],
            [
                'name' => 'Rizki Fauzi',
                'email' => 'rizki.tkro@smkn10garut.sch.id',
                'nis' => '242104',
                'nisn' => '0072100004',
                'gender' => 'L',
                'phone' => '081398702104',
                'address' => 'Jl. Suherman No. 34, Tarogong Kaler, Garut',
                'class_name' => 'XII TKRO 2',
                'company_keyword' => 'Auto2000',
                'teacher_email' => 'adi.wildan@smkn10garut.sch.id',
            ],

            // --- TBSM ---
            [
                'name' => 'Sandi Permana',
                'email' => 'sandi.tbsm@smkn10garut.sch.id',
                'nis' => '242201',
                'nisn' => '0072200001',
                'gender' => 'L',
                'phone' => '081398702201',
                'address' => 'Jl. Cimanuk No. 200, Tarogong Kidul, Garut',
                'class_name' => 'XII TBSM 1',
                'company_keyword' => 'AHASS',
                'teacher_email' => 'dadan.herdiana@smkn10garut.sch.id',
            ],
            [
                'name' => 'Taufik Hidayat',
                'email' => 'taufik.tbsm@smkn10garut.sch.id',
                'nis' => '242202',
                'nisn' => '0072200002',
                'gender' => 'L',
                'phone' => '081398702202',
                'address' => 'Jl. Pasundan No. 44, Garut Kota',
                'class_name' => 'XII TBSM 1',
                'company_keyword' => 'Bratayudha',
                'teacher_email' => 'adi.wildan@smkn10garut.sch.id',
            ],
            [
                'name' => 'Andri Gunawan',
                'email' => 'andri.tbsm@smkn10garut.sch.id',
                'nis' => '242203',
                'nisn' => '0072200003',
                'gender' => 'L',
                'phone' => '081398702203',
                'address' => 'Kp. Babakan Cilawu, Garut',
                'class_name' => 'XII TBSM 2',
                'company_keyword' => 'Sinar Otomotif',
                'teacher_email' => 'dadan.herdiana@smkn10garut.sch.id',
            ],
            [
                'name' => 'Fahmi Ramdani',
                'email' => 'fahmi.tbsm@smkn10garut.sch.id',
                'nis' => '242204',
                'nisn' => '0072200004',
                'gender' => 'L',
                'phone' => '081398702204',
                'address' => 'Jl. Pembangunan No. 80, Garut',
                'class_name' => 'XII TBSM 2',
                'company_keyword' => 'AHASS',
                'teacher_email' => 'adi.wildan@smkn10garut.sch.id',
            ],

            // --- TJKT ---
            [
                'name' => 'Rifki Fauzan',
                'email' => 'rifki.tjkt@smkn10garut.sch.id',
                'nis' => '242301',
                'nisn' => '0072300001',
                'gender' => 'L',
                'phone' => '081398702301',
                'address' => 'Jl. Raya Garut-Tasik Km. 9, Cilawu, Garut',
                'class_name' => 'XII TJKT 1',
                'company_keyword' => 'Telkom',
                'teacher_email' => 'ihsan.sibaweh@smkn10garut.sch.id',
            ],
            [
                'name' => 'Sindi Aulia',
                'email' => 'sindi.tjkt@smkn10garut.sch.id',
                'nis' => '242302',
                'nisn' => '0072300002',
                'gender' => 'P',
                'phone' => '081398702302',
                'address' => 'Jl. Pembangunan No. 150, Tarogong Kidul, Garut',
                'class_name' => 'XII TJKT 1',
                'company_keyword' => 'Diskominfo',
                'teacher_email' => 'anita@smkn10garut.sch.id',
            ],
            [
                'name' => 'Wildan Nugraha',
                'email' => 'wildan.tjkt@smkn10garut.sch.id',
                'nis' => '242303',
                'nisn' => '0072300003',
                'gender' => 'L',
                'phone' => '081398702303',
                'address' => 'Desa Dangiang, Cilawu, Garut',
                'class_name' => 'XII TJKT 2',
                'company_keyword' => 'Balai Benih',
                'teacher_email' => 'ihsan.sibaweh@smkn10garut.sch.id',
            ],
            [
                'name' => 'Maya Rosita',
                'email' => 'maya.tjkt@smkn10garut.sch.id',
                'nis' => '242304',
                'nisn' => '0072300004',
                'gender' => 'P',
                'phone' => '081398702304',
                'address' => 'Jl. Patriot No. 30, Garut Kota',
                'class_name' => 'XII TJKT 2',
                'company_keyword' => 'Diskominfo',
                'teacher_email' => 'anita@smkn10garut.sch.id',
            ],

            // --- DKV ---
            [
                'name' => 'Firman Syahputra',
                'email' => 'firman.dkv@smkn10garut.sch.id',
                'nis' => '242401',
                'nisn' => '0072400001',
                'gender' => 'L',
                'phone' => '081398702401',
                'address' => 'Jl. Raya Garut-Tasik Km. 6, Cilawu, Garut',
                'class_name' => 'XII DKV 1',
                'company_keyword' => 'Duta Media',
                'teacher_email' => 'asep.saepuloh@smkn10garut.sch.id',
            ],
            [
                'name' => 'Neng Siska',
                'email' => 'siska.dkv@smkn10garut.sch.id',
                'nis' => '242402',
                'nisn' => '0072400002',
                'gender' => 'P',
                'phone' => '081398702402',
                'address' => 'Jl. Pramuka No. 10, Garut Kota',
                'class_name' => 'XII DKV 1',
                'company_keyword' => 'Telkom',
                'teacher_email' => 'asep.saepuloh@smkn10garut.sch.id',
            ],
            [
                'name' => 'Rendy Ardiansyah',
                'email' => 'rendy.dkv@smkn10garut.sch.id',
                'nis' => '242403',
                'nisn' => '0072400003',
                'gender' => 'L',
                'phone' => '081398702403',
                'address' => 'Kp. Bojong Cilawu, Garut',
                'class_name' => 'XII DKV 2',
                'company_keyword' => 'Duta Media',
                'teacher_email' => 'asep.saepuloh@smkn10garut.sch.id',
            ],
            [
                'name' => 'Silvi Anggraeni',
                'email' => 'silvi.dkv@smkn10garut.sch.id',
                'nis' => '242404',
                'nisn' => '0072400004',
                'gender' => 'P',
                'phone' => '081398702404',
                'address' => 'Jl. Pembangunan No. 112, Garut',
                'class_name' => 'XII DKV 2',
                'company_keyword' => 'Diskominfo',
                'teacher_email' => 'asep.saepuloh@smkn10garut.sch.id',
            ],

            // --- AKL ---
            [
                'name' => 'Naufal Azhar',
                'email' => 'naufal.akl@smkn10garut.sch.id',
                'nis' => '242501',
                'nisn' => '0072500001',
                'gender' => 'L',
                'phone' => '081398702501',
                'address' => 'Jl. Ahmad Yani No. 120, Garut Kota',
                'class_name' => 'XII AKL 1',
                'company_keyword' => 'BSI',
                'teacher_email' => 'yanti.mulyani@smkn10garut.sch.id',
            ],
            [
                'name' => 'Desi Ratnasari',
                'email' => 'desi.akl@smkn10garut.sch.id',
                'nis' => '242502',
                'nisn' => '0072500002',
                'gender' => 'P',
                'phone' => '081398702502',
                'address' => 'Jl. Cimanuk No. 88, Garut',
                'class_name' => 'XII AKL 1',
                'company_keyword' => 'BPRS',
                'teacher_email' => 'yanti.mulyani@smkn10garut.sch.id',
            ],
            [
                'name' => 'Fajar Sidik',
                'email' => 'fajar.akl@smkn10garut.sch.id',
                'nis' => '242503',
                'nisn' => '0072500003',
                'gender' => 'L',
                'phone' => '081398702503',
                'address' => 'Desa Karyamekar, Cilawu, Garut',
                'class_name' => 'XII AKL 2',
                'company_keyword' => 'Balai Benih',
                'teacher_email' => 'yanti.mulyani@smkn10garut.sch.id',
            ],
            [
                'name' => 'Rina Agustina',
                'email' => 'rina.akl@smkn10garut.sch.id',
                'nis' => '242504',
                'nisn' => '0072500004',
                'gender' => 'P',
                'phone' => '081398702504',
                'address' => 'Jl. Merdeka No. 65, Garut',
                'class_name' => 'XII AKL 2',
                'company_keyword' => 'BSI',
                'teacher_email' => 'yanti.mulyani@smkn10garut.sch.id',
            ],

            // --- LPS (Layanan Perbankan Syariah) ---
            [
                'name' => 'Ahmad Fauzan',
                'email' => 'fauzan.lps@smkn10garut.sch.id',
                'nis' => '242601',
                'nisn' => '0072600001',
                'gender' => 'L',
                'phone' => '081398702601',
                'address' => 'Jl. Ahmad Yani No. 95, Garut Kota',
                'class_name' => 'XII LPS 1',
                'company_keyword' => 'BSI',
                'teacher_email' => 'rini.anggraeni@smkn10garut.sch.id',
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.lps@smkn10garut.sch.id',
                'nis' => '242602',
                'nisn' => '0072600002',
                'gender' => 'P',
                'phone' => '081398702602',
                'address' => 'Jl. Cimanuk No. 135, Tarogong Kidul, Garut',
                'class_name' => 'XII LPS 1',
                'company_keyword' => 'BPRS',
                'teacher_email' => 'rini.anggraeni@smkn10garut.sch.id',
            ],
            [
                'name' => 'Dani Hamdani',
                'email' => 'dani.lps@smkn10garut.sch.id',
                'nis' => '242603',
                'nisn' => '0072600003',
                'gender' => 'L',
                'phone' => '081398702603',
                'address' => 'Desa Sukamaju, Cilawu, Garut',
                'class_name' => 'XII LPS 2',
                'company_keyword' => 'BPRS',
                'teacher_email' => 'rini.anggraeni@smkn10garut.sch.id',
            ],
            [
                'name' => 'Putri Maharani',
                'email' => 'putri.lps@smkn10garut.sch.id',
                'nis' => '242604',
                'nisn' => '0072600004',
                'gender' => 'P',
                'phone' => '081398702604',
                'address' => 'Jl. Raya Garut-Tasik Km. 10, Cilawu, Garut',
                'class_name' => 'XII LPS 2',
                'company_keyword' => 'BSI',
                'teacher_email' => 'rini.anggraeni@smkn10garut.sch.id',
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
