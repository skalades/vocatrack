<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\LearningObjective;
use App\Models\Major;
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

class GeologiPertambanganPklSeeder extends Seeder
{
    /**
     * Jalankan seeder modular untuk data Penempatan Siswa PKL Jurusan Teknik Geologi Pertambangan (GP).
     * Mencakup:
     * 1. Jurusan & Kelas GP (XII GP 1 & XII GP 2)
     * 2. Kepala Jurusan & 6 Guru Pembimbing Kejuruan GP
     * 3. 12 Instansi / DUDI Pertambangan & Eksplorasi beserta Pembimbing DUDI
     * 4. 68 Siswa Lengkap (User, Profile, NIS/NISN, Parent Portal Token, & Plotting PKL Aktif)
     */
    public function run(): void
    {
        // 1. Dapatkan referensi Sekolah SMKN 2 Garut
        $school = School::where('npsn', '20209203')->first() ?? School::firstOrFail();

        // 2. Buat atau dapatkan Jurusan Teknik Geologi Pertambangan (GP)
        $majorGp = Major::firstOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'GP',
            ],
            [
                'name' => 'Teknik Geologi Pertambangan',
            ]
        );

        // 3. Buat atau dapatkan Kelas XII GP 1 & XII GP 2
        $classGp1 = SchoolClass::firstOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'XII GP 1',
            ],
            [
                'major_id' => $majorGp->id,
                'grade_level' => 'XII',
                'academic_year' => '2026/2027',
            ]
        );

        $classGp2 = SchoolClass::firstOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'XII GP 2',
            ],
            [
                'major_id' => $majorGp->id,
                'grade_level' => 'XII',
                'academic_year' => '2026/2027',
            ]
        );

        // 3.5. Capaian Pembelajaran (CP) Kurikulum Merdeka Jurusan Geologi Pertambangan
        $gpLearningObjectives = [
            [
                'code' => 'GP-CP01',
                'title' => 'Penerapan K3LH dan Budaya Kerja Pertambangan (Good Mining Practice)',
                'element_name' => 'K3LH & Budaya Kerja Industri',
                'description' => 'Menerapkan prosedur keselamatan pertambangan (K3LH), pemakaian APD spesifik tambang, tanggap darurat, dan SOP kerja ramah lingkungan.',
                'target_hours' => 60,
                'sort_order' => 1,
            ],
            [
                'code' => 'GP-CP02',
                'title' => 'Pemetaan Geologi Lapangan & Orientasi Medan Tambang',
                'element_name' => 'Geologi Lapangan & Navigasi',
                'description' => 'Melakukan orientasi lapangan menggunakan kompas geologi dan GPS, pengukuran strike & dip struktur geologi, serta pencatatan singkapan batuan.',
                'target_hours' => 120,
                'sort_order' => 2,
            ],
            [
                'code' => 'GP-CP03',
                'title' => 'Pemboran Eksplorasi & Logging Inti Bor (Core Logging)',
                'element_name' => 'Eksplorasi & Pengeboran',
                'description' => 'Melakukan preparasi pemboran geologi, deskripsi litologi core batuan, pengukuran RQD dan recovery core, serta dokumentasi core box.',
                'target_hours' => 140,
                'sort_order' => 3,
            ],
            [
                'code' => 'GP-CP04',
                'title' => 'Teknik Penambangan, Pemindahan Tanah Mekanis & Loading-Hauling',
                'element_name' => 'Operasi Tambang & Alat Berat',
                'description' => 'Mengidentifikasi sistem penambangan, produktivitas alat gali-muat-angkut, stabilitas lereng tambang terbuka/bawah tanah, dan drainase tambang.',
                'target_hours' => 160,
                'sort_order' => 4,
            ],
            [
                'code' => 'GP-CP05',
                'title' => 'Pengolahan Bahan Galian, Reklamasi & Pengendalian Lingkungan',
                'element_name' => 'Lingkungan & Pascatambang',
                'description' => 'Melakukan penanganan sampel mineral/batuan, pemantauan air asam tambang (AAT), penataan lahan bekas tambang, dan revegetasi.',
                'target_hours' => 80,
                'sort_order' => 5,
            ],
        ];

        foreach ($gpLearningObjectives as $cp) {
            LearningObjective::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'major_id' => $majorGp->id,
                    'code' => $cp['code'],
                ],
                [
                    'title' => $cp['title'],
                    'element_name' => $cp['element_name'],
                    'description' => $cp['description'],
                    'target_hours' => $cp['target_hours'],
                    'sort_order' => $cp['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // 4. Bersihkan data dummy lama Geologi Pertambangan jika ada
        $dummyStudentEmails = [
            'rendi@smkn2garut.sch.id',
            'gina@smkn2garut.sch.id',
            'dika@smkn2garut.sch.id',
        ];
        $dummyUsers = User::with(['student', 'profile'])->where('school_id', $school->id)->whereIn('email', $dummyStudentEmails)->get();
        foreach ($dummyUsers as $du) {
            if ($du->student) {
                StudentPkl::where('student_id', $du->student->id)->delete();
                $du->student->delete();
            }
            $du->profile?->delete();
            $du->delete();
        }

        // Hapus dummy company lama jika ada
        $dummyCompanyNames = [
            'PT Aneka Tambang Tbk - Unit Bisnis Geomin & Eksplorasi Mineral',
            'PSDMBP Badan Geologi - Kementerian ESDM',
            'PT Timah Tbk - Divisi Eksplorasi Darat & Pemboran Tambang',
        ];
        $dummyCompanies = Company::with(['supervisors.user.profile'])->where('school_id', $school->id)->whereIn('name', $dummyCompanyNames)->get();
        foreach ($dummyCompanies as $dc) {
            StudentPkl::where('company_id', $dc->id)->delete();
            foreach ($dc->supervisors as $spv) {
                $spvUser = $spv->user;
                $spv->delete();
                $spvUser?->profile?->delete();
                $spvUser?->delete();
            }
            $dc->majors()->detach();
            $dc->delete();
        }

        // 5. Guru Pembimbing Kejuruan (6 Guru sesuai data)
        $teachersData = [
            'Danang Kusumah, S.T.' => [
                'name' => 'Danang Kusumah, S.T.',
                'email' => 'danang.kusumah@smkn2garut.sch.id',
                'nip' => '198305152010011012',
                'gender' => 'L',
                'phone' => '081223450011',
                'address' => 'Tarogong Kaler, Kabupaten Garut',
            ],
            'Yandi Rusyandi, S.Pd., M.Pd' => [
                'name' => 'Yandi Rusyandi, S.Pd., M.Pd',
                'email' => 'yandi.rusyandi@smkn2garut.sch.id',
                'nip' => '198108202008011009',
                'gender' => 'L',
                'phone' => '081223450012',
                'address' => 'Jl. Pembangunan No. 88, Garut',
            ],
            'Abdul Hakim, S.T., M.T.' => [
                'name' => 'Abdul Hakim, S.T., M.T.',
                'email' => 'abdul.hakim@smkn2garut.sch.id',
                'nip' => '197904122006041008',
                'gender' => 'L',
                'phone' => '081223450013',
                'address' => 'Tarogong Kidul, Kabupaten Garut',
            ],
            'Herdi Pebryana Putra, S.T.' => [
                'name' => 'Herdi Pebryana Putra, S.T.',
                'email' => 'guru@smkn2garut.sch.id', // Akun utama guru / kajur GP
                'nip' => '198702142011011003',
                'gender' => 'L',
                'phone' => '081223344552',
                'address' => 'Jl. Pembangunan No. 42, Garut Kota',
            ],
            'Cahya Nugraha, S.T., M.T.' => [
                'name' => 'Cahya Nugraha, S.T., M.T.',
                'email' => 'cahya.nugraha@smkn2garut.sch.id',
                'nip' => '198411032009021003',
                'gender' => 'L',
                'phone' => '081223450014',
                'address' => 'Samarang, Kabupaten Garut',
            ],
            'Adbel Yuki Edward, S.T, M.T.' => [
                'name' => 'Adbel Yuki Edward, S.T, M.T.',
                'email' => 'adbel.yuki@smkn2garut.sch.id',
                'nip' => '198607192011011007',
                'gender' => 'L',
                'phone' => '081223450015',
                'address' => 'Garut Kota, Kabupaten Garut',
            ],
        ];

        $seededTeachers = [];
        foreach ($teachersData as $key => $td) {
            $user = User::updateOrCreate(
                ['email' => $td['email']],
                [
                    'school_id' => $school->id,
                    'name' => $td['name'],
                    'password' => Hash::make('password'),
                    'role' => 'guru-pembimbing',
                    'is_active' => true,
                ]
            );

            Profile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'gender' => $td['gender'],
                    'phone' => $td['phone'],
                    'address' => $td['address'],
                ]
            );

            $teacher = Teacher::updateOrCreate(
                ['user_id' => $user->id],
                ['nip' => $td['nip']]
            );

            $teacher->majors()->syncWithoutDetaching([$majorGp->id]);
            $seededTeachers[$key] = $teacher;
        }

        // Tetapkan Herdi Pebryana Putra, S.T. sebagai Kepala Jurusan (Kajur) GP
        if (isset($seededTeachers['Herdi Pebryana Putra, S.T.'])) {
            $majorGp->update([
                'head_teacher_id' => $seededTeachers['Herdi Pebryana Putra, S.T.']->id,
            ]);
        }

        // 6. Dapatkan atau buat Periode PKL aktif
        $period = PklPeriod::where('school_id', $school->id)->where('is_active', true)->first();
        if (!$period) {
            $period = PklPeriod::create([
                'school_id' => $school->id,
                'name' => 'PKL Eksplorasi & Industri Pertambangan TP 2026/2027',
                'start_date' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->addMonths(4)->endOfMonth()->toDateString(),
                'is_active' => true,
            ]);
        }

        // 7. Data 12 DUDI / Instansi Penempatan PKL Geologi Pertambangan
        $companiesData = [
            'PT. SANGGA BUANA NUSANTARA' => [
                'name' => 'PT. SANGGA BUANA NUSANTARA',
                'address' => 'Ruko Angsana Blok S, Jln. Angsana Raya No. 1 RT. 001 RW.001 Pasar Minggu, DKI Jakarta',
                'contact_person' => 'Bambang Setyawan, S.T.',
                'phone' => '021-78839001',
                'latitude' => -6.28420000,
                'longitude' => 106.84310000,
                'supervisor_name' => 'Bambang Setyawan, S.T.',
                'supervisor_email' => 'spv.sanggabuana@sbn.co.id',
                'supervisor_gender' => 'L',
            ],
            'CV. GUNUNG BATUJAJAR' => [
                'name' => 'CV. GUNUNG BATUJAJAR',
                'address' => 'Kp. Cikuya Tonggoh/Jl. Baros No. 65/31 Kota Cimahi, Jawa Barat',
                'contact_person' => 'Asep Hidayat, S.T.',
                'phone' => '022-6654210',
                'latitude' => -6.88560000,
                'longitude' => 107.54120000,
                'supervisor_name' => 'Asep Hidayat, S.T.',
                'supervisor_email' => 'spv.gunungbatujajar@mining.co.id',
                'supervisor_gender' => 'L',
            ],
            'PT. TIMAH Tbk.' => [
                'name' => 'PT. TIMAH Tbk.',
                'address' => 'Jl. Jendral Soedirman 51 Pangkalpinang 33121 Bangka Belitung',
                'contact_person' => 'Ir. Bambang Triyono, S.T., M.Sc.',
                'phone' => '0717-4258000',
                'latitude' => -2.12450000,
                'longitude' => 106.11280000,
                'supervisor_name' => 'Ir. Bambang Triyono, S.T., M.Sc.',
                'supervisor_email' => 'supervisor.timah@timah.co.id',
                'supervisor_gender' => 'L',
            ],
            'PT. DATEM' => [
                'name' => 'PT. DATEM',
                'address' => 'Jl. Jamrud Barat No. 05 Cisaranten Kulon Arcamanik Kota Bandung, Jawa Barat',
                'contact_person' => 'Rudi Hartono, S.T.',
                'phone' => '022-7208112',
                'latitude' => -6.92470000,
                'longitude' => 107.67890000,
                'supervisor_name' => 'Rudi Hartono, S.T.',
                'supervisor_email' => 'spv.datem@datem.co.id',
                'supervisor_gender' => 'L',
            ],
            'PT. PERTAMINA GEOTHERMAL ENERGI AREA KAMOJANG' => [
                'name' => 'PT. PERTAMINA GEOTHERMAL ENERGI AREA KAMOJANG',
                'address' => 'Jl. Laksana, Kec. Ibun, Kabupaten Bandung, Jawa Barat',
                'contact_person' => 'Dwi Prasetyo, S.T., M.T.',
                'phone' => '022-85920000',
                'latitude' => -7.13280000,
                'longitude' => 107.79540000,
                'supervisor_name' => 'Dwi Prasetyo, S.T., M.T.',
                'supervisor_email' => 'spv.pge.kamojang@pertamina.com',
                'supervisor_gender' => 'L',
            ],
            'PT. Solusi Bangun Indonesia' => [
                'name' => 'PT. Solusi Bangun Indonesia',
                'address' => 'Jl. Nangka Jl. Raya Klapanunggal No. 7 Kec. Klapanunggal Kabupaten Bogor, Jawa Barat',
                'contact_person' => 'Hendra Wijaya, S.T.',
                'phone' => '021-8231234',
                'latitude' => -6.47120000,
                'longitude' => 106.94580000,
                'supervisor_name' => 'Hendra Wijaya, S.T.',
                'supervisor_email' => 'spv.sbi@solusibangun.id',
                'supervisor_gender' => 'L',
            ],
            'ATR BPN Kab. Garut' => [
                'name' => 'ATR BPN Kab. Garut',
                'address' => 'Jl. Suherman, Jati, Kec. Tarogong Kaler, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Rahmat Kurniawan, S.SiT.',
                'phone' => '0262-233451',
                'latitude' => -7.19850000,
                'longitude' => 107.89670000,
                'supervisor_name' => 'Rahmat Kurniawan, S.SiT.',
                'supervisor_email' => 'spv.atrbpn@garutkab.atrbpn.go.id',
                'supervisor_gender' => 'L',
            ],
            'BBPMB tekMIRA' => [
                'name' => 'BBPMB tekMIRA',
                'address' => 'Jl. Jend. Sudirman No.623, Wr. Muncang, Kec. Bandung Kulon, Kota Bandung, Jawa Barat 40211',
                'contact_person' => 'Dr. Ir. Agus Supriatna, M.T.',
                'phone' => '022-6011081',
                'latitude' => -6.92130000,
                'longitude' => 107.57860000,
                'supervisor_name' => 'Dr. Ir. Agus Supriatna, M.T.',
                'supervisor_email' => 'spv.tekmira@esdm.go.id',
                'supervisor_gender' => 'L',
            ],
            'PT. GEOSERVICES LTD' => [
                'name' => 'PT. GEOSERVICES LTD',
                'address' => 'Jl. Jababeka Raya, Mekarmukti, Kec. Cikarang Utara, Kab. Bekasi, Jawa Barat 17530',
                'contact_person' => 'Ir. Dedi Supriyadi, M.Sc.',
                'phone' => '021-8934567',
                'latitude' => -6.30510000,
                'longitude' => 107.16850000,
                'supervisor_name' => 'Ir. Dedi Supriyadi, M.Sc.',
                'supervisor_email' => 'spv.geoservices@geoservices.com',
                'supervisor_gender' => 'L',
            ],
            'PT. BHADRA PINGGALA SEJAHTERA MINING SERVICE' => [
                'name' => 'PT. BHADRA PINGGALA SEJAHTERA MINING SERVICE',
                'address' => 'Prima Amerta Residence Jl. Alamanda No. 3 Cingcin Kec. Soreang Bandung, Jawa Barat',
                'contact_person' => 'Bayu Anggoro, S.T.',
                'phone' => '022-5891102',
                'latitude' => -7.02780000,
                'longitude' => 107.52450000,
                'supervisor_name' => 'Bayu Anggoro, S.T.',
                'supervisor_email' => 'spv.bhadrapinggala@bpsmining.com',
                'supervisor_gender' => 'L',
            ],
            'PT. ARYA SWASCIPTA REKAYASA' => [
                'name' => 'PT. ARYA SWASCIPTA REKAYASA',
                'address' => 'Jl. Pahlawan No. 95, Sukaluyu, Kec. Cibeunying, Kota Bandung, Jawa Barat 40123',
                'contact_person' => 'Firman Maulana, S.T.',
                'phone' => '022-2503411',
                'latitude' => -6.89740000,
                'longitude' => 107.63210000,
                'supervisor_name' => 'Firman Maulana, S.T.',
                'supervisor_email' => 'spv.aryaswascipta@asrekayasa.co.id',
                'supervisor_gender' => 'L',
            ],
            'PT. MAHATMA' => [
                'name' => 'PT. MAHATMA',
                'address' => 'Jl. Indramayu 8 No. 1 Antapani Kidul, Kec. Antapani Kota Bandung Jawa Barat 40291',
                'contact_person' => 'Wahyu Nugroho, S.T.',
                'phone' => '022-7104523',
                'latitude' => -6.91890000,
                'longitude' => 107.65980000,
                'supervisor_name' => 'Wahyu Nugroho, S.T.',
                'supervisor_email' => 'spv.mahatma@mahatma.co.id',
                'supervisor_gender' => 'L',
            ],
        ];

        $seededCompanies = [];
        $seededSupervisors = [];

        foreach ($companiesData as $key => $cd) {
            $company = Company::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $cd['name'],
                ],
                [
                    'address' => $cd['address'],
                    'contact_person' => $cd['contact_person'],
                    'phone' => $cd['phone'],
                    'gps_enabled' => true,
                    'latitude' => $cd['latitude'],
                    'longitude' => $cd['longitude'],
                    'radius_meters' => 200,
                    'override_hours' => false,
                    'work_days' => '5_days',
                ]
            );

            $company->majors()->syncWithoutDetaching([$majorGp->id]);

            $supervisorUser = User::updateOrCreate(
                ['email' => $cd['supervisor_email']],
                [
                    'school_id' => $school->id,
                    'name' => $cd['supervisor_name'],
                    'password' => Hash::make('password'),
                    'role' => 'pembimbing-dudi',
                    'is_active' => true,
                ]
            );

            Profile::updateOrCreate(
                ['user_id' => $supervisorUser->id],
                [
                    'gender' => $cd['supervisor_gender'],
                    'phone' => $cd['phone'],
                    'address' => $cd['address'],
                ]
            );

            $supervisor = Supervisor::updateOrCreate(
                ['user_id' => $supervisorUser->id],
                [
                    'company_id' => $company->id,
                    'position' => 'Pembimbing Lapangan Industri DU/DI',
                ]
            );

            $seededCompanies[$key] = $company;
            $seededSupervisors[$key] = $supervisor;
        }

        // 8. Data 68 Siswa Jurusan Geologi Pertambangan & Plotting Penempatan
        $studentsRaw = [
            // PT. SANGGA BUANA NUSANTARA (8 siswa - XII GP 2 - Danang Kusumah, S.T.)
            ['name' => 'ANDIKA SATRIA SAPUTRA', 'nis' => '242510503', 'nisn' => '0094286823', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'Danang Kusumah, S.T.', 'class' => 'XII GP 2'],
            ['name' => 'IBNU ALI HAJJ', 'nis' => '242510513', 'nisn' => '3070590887', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'Danang Kusumah, S.T.', 'class' => 'XII GP 2'],
            ['name' => 'MUHAMAD IRFAN ARDIANSYAH', 'nis' => '242510519', 'nisn' => '0095567392', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'Danang Kusumah, S.T.', 'class' => 'XII GP 2'],
            ['name' => 'MUHAMMAD RIZKI SAPUTRA', 'nis' => '242510524', 'nisn' => '0098666825', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'Danang Kusumah, S.T.', 'class' => 'XII GP 2'],
            ['name' => 'MOHAMAD FATHAN RAMADANI', 'nis' => '242510517', 'nisn' => '0098722997', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'Danang Kusumah, S.T.', 'class' => 'XII GP 2'],
            ['name' => 'RENZA TAUFIK YUGASWARA', 'nis' => '242510531', 'nisn' => '0094397515', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'Danang Kusumah, S.T.', 'class' => 'XII GP 2'],
            ['name' => 'REFIE WIBAWA SAPUTRA', 'nis' => '242510530', 'nisn' => '0095029468', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'Danang Kusumah, S.T.', 'class' => 'XII GP 2'],
            ['name' => 'AFFANDI DANUNINGRAT', 'nis' => '242510502', 'nisn' => '0086089719', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'Danang Kusumah, S.T.', 'class' => 'XII GP 2'],
            // CV. GUNUNG BATUJAJAR (4 siswa - XII GP 2 - Yandi Rusyandi, S.Pd., M.Pd)
            ['name' => 'MUHAMMAD RAFA SYAMLA IMANI', 'nis' => '242510523', 'nisn' => '3094845475', 'company' => 'CV. GUNUNG BATUJAJAR', 'teacher' => 'Yandi Rusyandi, S.Pd., M.Pd', 'class' => 'XII GP 2'],
            ['name' => 'NOFA ADITHYA DWI HERYANTO', 'nis' => '242510527', 'nisn' => '0093063341', 'company' => 'CV. GUNUNG BATUJAJAR', 'teacher' => 'Yandi Rusyandi, S.Pd., M.Pd', 'class' => 'XII GP 2'],
            ['name' => 'REZA SETYA ROLAND PRATAMA', 'nis' => '242510532', 'nisn' => '0096056982', 'company' => 'CV. GUNUNG BATUJAJAR', 'teacher' => 'Yandi Rusyandi, S.Pd., M.Pd', 'class' => 'XII GP 2'],
            ['name' => 'RIDHO RIZKI HABIBI', 'nis' => '242510534', 'nisn' => '0092109182', 'company' => 'CV. GUNUNG BATUJAJAR', 'teacher' => 'Yandi Rusyandi, S.Pd., M.Pd', 'class' => 'XII GP 2'],
            // PT. TIMAH Tbk. (18 siswa - Abdul Hakim, S.T., M.T. - 12 di XII GP 1, 6 di XII GP 2)
            ['name' => 'ANUGRAH ILMAN NURHADI', 'nis' => '242510467', 'nisn' => '0092253991', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'AZRIL MUHAMAD ASSYURO', 'nis' => '242510468', 'nisn' => '0098499926', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'FARUK FIRMANSYAH AZIZ', 'nis' => '242510474', 'nisn' => '0105262820', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'GHASSAN FATIH RAYYAN', 'nis' => '242510475', 'nisn' => '0094512375', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'IHSAN TAMYIZ', 'nis' => '242510477', 'nisn' => '0082580068', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'IKMAL LATHIF DARAJAT', 'nis' => '242510478', 'nisn' => '0085330049', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'MUHAMMAD AKBAR ZAELANI', 'nis' => '242510484', 'nisn' => '0093723103', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'FAREL ELSANDI', 'nis' => '242510473', 'nisn' => '0097324725', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'NAZMY HUDZAIFAH FAZRA NURHIKMAT', 'nis' => '242510489', 'nisn' => '0093490485', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'REVAN JANUAR', 'nis' => '242510496', 'nisn' => '0092167743', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'VINCENT ACHMAD FAUZI', 'nis' => '242510500', 'nisn' => '0081888616', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'ZAHRAN HAIKAL GURSIDA', 'nis' => '242510501', 'nisn' => '0094267185', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'AZFAR SHAZWAN MALAZI', 'nis' => '242510504', 'nisn' => '0094394213', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 2'],
            ['name' => 'FARHAN NAIF RAYHANA', 'nis' => '242510508', 'nisn' => '0097323758', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 2'],
            ['name' => 'TRIYOSEPH MARCHELIANO', 'nis' => '242510536', 'nisn' => '0097348577', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 2'],
            ['name' => 'HADI WIJAYA', 'nis' => '242510511', 'nisn' => '0089755605', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 2'],
            ['name' => 'HARLAN JULIAN ABDULAH', 'nis' => '242510512', 'nisn' => '0099880763', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 2'],
            ['name' => 'MUHAMMAD AZKA ALAWI', 'nis' => '242510522', 'nisn' => '0096900374', 'company' => 'PT. TIMAH Tbk.', 'teacher' => 'Abdul Hakim, S.T., M.T.', 'class' => 'XII GP 2'],
            // PT. DATEM (14 siswa - Herdi Pebryana Putra, S.T. - 11 di XII GP 1, 3 di XII GP 2)
            ['name' => 'DIKA HERMAWAN', 'nis' => '242510470', 'nisn' => '0089330696', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            ['name' => 'FADLI YUDHA PRASETIA RAMDANI', 'nis' => '242510471', 'nisn' => '0085711691', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            ['name' => 'FAISAL FAHRI RAMADHAN', 'nis' => '242510472', 'nisn' => '0081495350', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            ['name' => 'KEVIN OKTAVIAN REVANO', 'nis' => '242510480', 'nisn' => '0094170221', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            ['name' => 'LANGIT KRESNA HARZADI', 'nis' => '242510481', 'nisn' => '0082682201', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            ['name' => 'MUHAMMAD HAWARI MAOLANA', 'nis' => '242510486', 'nisn' => '0096979330', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            ['name' => 'PASHA KAISAR FIRMANSYAH', 'nis' => '242510491', 'nisn' => '0089786866', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            ['name' => 'RANDY PUTRA ANDIKA', 'nis' => '242510493', 'nisn' => '0092359360', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            ['name' => 'RIDHO AHMAD PAMUNGKAS', 'nis' => '242510497', 'nisn' => '0095993883', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            ['name' => 'SYAHDAN TRI ATALA', 'nis' => '242510499', 'nisn' => '0096266478', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            ['name' => 'BINTANG MALIK AS SHIDIQ', 'nis' => '242510505', 'nisn' => '0096583456', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 2'],
            ['name' => 'RIDHO JULIANSYAH', 'nis' => '242510533', 'nisn' => '0098587030', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 2'],
            ['name' => 'MUHAMAD SYAFIK AL AKBAR', 'nis' => '242510520', 'nisn' => '0079405198', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 2'],
            ['name' => 'NOVALDIO', 'nis' => '242510490', 'nisn' => '3087103824', 'company' => 'PT. DATEM', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 1'],
            // PT. PERTAMINA GEOTHERMAL ENERGI AREA KAMOJANG (4 siswa - Cahya Nugraha, S.T., M.T. - 3 di XII GP 1, 1 di XII GP 2)
            ['name' => 'CARREL MUNJID AL RASYID', 'nis' => '242510469', 'nisn' => '0083191727', 'company' => 'PT. PERTAMINA GEOTHERMAL ENERGI AREA KAMOJANG', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'KENNINO FARRAS', 'nis' => '242510479', 'nisn' => '3093095601', 'company' => 'PT. PERTAMINA GEOTHERMAL ENERGI AREA KAMOJANG', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'RIVAL KARTONO', 'nis' => '242510498', 'nisn' => '0083482124', 'company' => 'PT. PERTAMINA GEOTHERMAL ENERGI AREA KAMOJANG', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'MUHAMMAD ADZKA ALINDRA', 'nis' => '242510521', 'nisn' => '0098003728', 'company' => 'PT. PERTAMINA GEOTHERMAL ENERGI AREA KAMOJANG', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 2'],
            // PT. Solusi Bangun Indonesia (7 siswa - XII GP 1 - Adbel Yuki Edward, S.T, M.T.)
            ['name' => 'HAURA ANNISA AWWALIYAH', 'nis' => '242510476', 'nisn' => '3082524222', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'Adbel Yuki Edward, S.T, M.T.', 'class' => 'XII GP 1'],
            ['name' => 'MOH. TRISTAN MAULANA IBRAHIM', 'nis' => '242510482', 'nisn' => '0098284999', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'Adbel Yuki Edward, S.T, M.T.', 'class' => 'XII GP 1'],
            ['name' => 'MUHAMAD FATHUR ROHMAN', 'nis' => '242510483', 'nisn' => '0081677595', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'Adbel Yuki Edward, S.T, M.T.', 'class' => 'XII GP 1'],
            ['name' => 'MUHAMMAD FAUZAN', 'nis' => '242510485', 'nisn' => '0095191695', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'Adbel Yuki Edward, S.T, M.T.', 'class' => 'XII GP 1'],
            ['name' => 'NADHIF MUSYAFFA AL GHANI', 'nis' => '242510488', 'nisn' => '0097114236', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'Adbel Yuki Edward, S.T, M.T.', 'class' => 'XII GP 1'],
            ['name' => 'PIKI PAHRURRAZI', 'nis' => '242510492', 'nisn' => '0095192399', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'Adbel Yuki Edward, S.T, M.T.', 'class' => 'XII GP 1'],
            ['name' => 'MUHAMMAD KAHFI', 'nis' => '242510487', 'nisn' => '0099412440', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'Adbel Yuki Edward, S.T, M.T.', 'class' => 'XII GP 1'],
            // ATR BPN Kab. Garut (1 siswa - XII GP 2 - Yandi Rusyandi, S.Pd., M.Pd)
            ['name' => 'FANI SOFIANTI', 'nis' => '242510507', 'nisn' => '0095772575', 'company' => 'ATR BPN Kab. Garut', 'teacher' => 'Yandi Rusyandi, S.Pd., M.Pd', 'class' => 'XII GP 2'],
            // BBPMB tekMIRA (4 siswa - XII GP 2 - Cahya Nugraha, S.T., M.T.)
            ['name' => 'FITRI RAMDANI', 'nis' => '242510509', 'nisn' => '0098648868', 'company' => 'BBPMB tekMIRA', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 2'],
            ['name' => 'ILHAM BAHIR ADITYA', 'nis' => '242510515', 'nisn' => '0081794543', 'company' => 'BBPMB tekMIRA', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 2'],
            ['name' => 'PANJI PUTRA RAMADHAN', 'nis' => '242510528', 'nisn' => '0087934205', 'company' => 'BBPMB tekMIRA', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 2'],
            ['name' => 'SATRIO RAIZAL ABDULLAH', 'nis' => '242510535', 'nisn' => '0091098552', 'company' => 'BBPMB tekMIRA', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 2'],
            // PT. GEOSERVICES LTD (4 siswa - Cahya Nugraha, S.T., M.T. - 1 di XII GP 1, 3 di XII GP 2)
            ['name' => 'REIHAN MAHAPUTRA ADINATHA', 'nis' => '242510495', 'nisn' => '0093127445', 'company' => 'PT. GEOSERVICES LTD', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 1'],
            ['name' => 'FITRIA KHOERUNNISA', 'nis' => '242510510', 'nisn' => '0092511104', 'company' => 'PT. GEOSERVICES LTD', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 2'],
            ['name' => 'IKHSAN AHMAD RIFAI', 'nis' => '242510514', 'nisn' => '3097495595', 'company' => 'PT. GEOSERVICES LTD', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 2'],
            ['name' => 'KESYHA APRILIANI', 'nis' => '242510516', 'nisn' => '0091514548', 'company' => 'PT. GEOSERVICES LTD', 'teacher' => 'Cahya Nugraha, S.T., M.T.', 'class' => 'XII GP 2'],
            // PT. BHADRA PINGGALA SEJAHTERA MINING SERVICE (2 siswa - XII GP 2 - Yandi Rusyandi, S.Pd., M.Pd)
            ['name' => 'MUHAMAD DIAZ FADLUROHMAN', 'nis' => '242510518', 'nisn' => '0093287983', 'company' => 'PT. BHADRA PINGGALA SEJAHTERA MINING SERVICE', 'teacher' => 'Yandi Rusyandi, S.Pd., M.Pd', 'class' => 'XII GP 2'],
            ['name' => 'NADHIF FATONI', 'nis' => '242510525', 'nisn' => '0098569417', 'company' => 'PT. BHADRA PINGGALA SEJAHTERA MINING SERVICE', 'teacher' => 'Yandi Rusyandi, S.Pd., M.Pd', 'class' => 'XII GP 2'],
            // PT. ARYA SWASCIPTA REKAYASA (1 siswa - XII GP 2 - Herdi Pebryana Putra, S.T.)
            ['name' => 'NAZIL AHKAM MUSABBIH', 'nis' => '242510526', 'nisn' => '0097486431', 'company' => 'PT. ARYA SWASCIPTA REKAYASA', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 2'],
            // PT. MAHATMA (1 siswa - XII GP 2 - Herdi Pebryana Putra, S.T.)
            ['name' => 'RADINAN AZKA NAJMUR RASYAD', 'nis' => '242510529', 'nisn' => '0091140603', 'company' => 'PT. MAHATMA', 'teacher' => 'Herdi Pebryana Putra, S.T.', 'class' => 'XII GP 2'],
        ];

        $femaleNames = ['HAURA ANNISA AWWALIYAH', 'FANI SOFIANTI', 'FITRI RAMDANI', 'FITRIA KHOERUNNISA', 'KESYHA APRILIANI'];
        $studentCounter = 0;

        foreach ($studentsRaw as $index => $st) {
            $number = $index + 1;
            $nis = $st['nis'];
            $nisn = $st['nisn'];
            $gender = in_array(strtoupper($st['name']), $femaleNames) ? 'P' : 'L';

            $targetClass = ($st['class'] === 'XII GP 1') ? $classGp1 : $classGp2;
            $company = $seededCompanies[$st['company']] ?? null;
            $supervisor = $seededSupervisors[$st['company']] ?? null;
            $teacher = $seededTeachers[$st['teacher']] ?? null;

            if (!$company || !$supervisor || !$teacher) {
                continue;
            }

            // Siswa 1 mendapatkan alias 'siswa@smkn2garut.sch.id' untuk memudahkan login demo
            $email = ($number === 1) ? 'siswa@smkn2garut.sch.id' : "siswa.{$nis}@smkn2garut.sch.id";

            // 1. Akun User Siswa
            $studentUser = User::where('school_id', $school->id)
                ->where('role', 'siswa')
                ->where('name', $st['name'])
                ->first();

            if ($studentUser) {
                $studentUser->update([
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]);
            } else {
                $studentUser = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'school_id' => $school->id,
                        'name' => $st['name'],
                        'password' => Hash::make('password'),
                        'role' => 'siswa',
                        'is_active' => true,
                    ]
                );
            }

            // 2. Profil Siswa
            Profile::updateOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'gender' => $gender,
                    'phone' => '08' . rand(11, 99) . rand(1000000, 9999999),
                    'address' => 'Kabupaten Garut, Jawa Barat',
                ]
            );

            // 3. Rekord Siswa (Student)
            $student = Student::where('user_id', $studentUser->id)->first();
            if ($student) {
                $student->update([
                    'class_id' => $targetClass->id,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'is_parent_portal_active' => true,
                ]);
            } else {
                $student = Student::create([
                    'user_id' => $studentUser->id,
                    'class_id' => $targetClass->id,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'parent_token' => Str::random(32),
                    'is_parent_portal_active' => true,
                ]);
            }

            // 4. Plotting PKL Siswa (StudentPkl)
            StudentPkl::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'pkl_period_id' => $period->id,
                ],
                [
                    'school_id' => $school->id,
                    'company_id' => $company->id,
                    'teacher_id' => $teacher->id,
                    'supervisor_id' => $supervisor->id,
                    'status' => 'active',
                ]
            );

            $studentCounter++;
        }

        if (isset($this->command)) {
            $majorGp->loadMissing('headTeacher.user');
            $this->command->info("✅ [GeologiPertambanganPklSeeder] Berhasil men-seed data Geologi Pertambangan SMKN 2 Garut:");
            $this->command->info("   - Jurusan: {$majorGp->name} ({$majorGp->code})");
            $this->command->info("   - Kepala Jurusan: " . ($majorGp->headTeacher?->user?->name ?? 'Herdi Pebryana Putra, S.T.'));
            $this->command->info("   - Kelas: XII GP 1 (34 siswa) & XII GP 2 (34 siswa)");
            $this->command->info("   - Guru Pembimbing: " . count($seededTeachers) . " guru");
            $this->command->info("   - Total Instansi / DUDI: " . count($seededCompanies) . " perusahaan");
            $this->command->info("   - Total Siswa & Plotting PKL: {$studentCounter} siswa aktif");
        }
    }
}
