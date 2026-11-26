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
     * Seeder modular Penempatan Siswa PKL Jurusan Teknik Geologi Pertambangan (GP) SMKN 2 Garut (disinkronkan dengan data live):
     * 1. Jurusan & Kelas GP (XII GP 1 & XII GP 2) beserta Wali Kelas masing-masing
     * 2. Kepala Jurusan & Guru Pembimbing PKL GP (15 guru aktif di plotting + Yandi Rusyandi)
     * 3. 11 Instansi / DU/DI Pertambangan & Eksplorasi beserta Pembimbing DU/DI
     * 4. 68 Siswa Lengkap (User, Profile, NIS/NISN, No. WhatsApp, & Plotting PKL Aktif)
     */
    public function run(): void
    {
        $school = School::where('npsn', '20209203')->first() ?? School::firstOrFail();

        $majorGp = Major::firstOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'GP',
            ],
            [
                'name' => 'Teknik Geologi Pertambangan',
            ]
        );

        // 1. Capaian Pembelajaran (CP) Kurikulum Merdeka Jurusan Geologi Pertambangan
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

        // 2. Guru Pembimbing & Wali Kelas Jurusan GP
        $passwordHash = Hash::make('password');
        $teachersData = [
            'DANANG' => [
                'name' => 'Danang Kusumah, S.T.',
                'base_name' => 'Danang Kusumah',
                'email' => 'danang.kusumah@smkn2garut.sch.id',
                'nip' => '198305152010011012',
                'gender' => 'L',
                'phone' => '081223450011',
                'address' => 'Tarogong Kaler, Kabupaten Garut',
                'attach_gp_major' => true,
            ],
            'YANDI' => [
                'name' => 'Yandi Rusyandi, S.Pd., M.Pd',
                'base_name' => 'Yandi Rusyandi',
                'email' => 'yandi.rusyandi@smkn2garut.sch.id',
                'nip' => '198108202008011009',
                'gender' => 'L',
                'phone' => '081223450012',
                'address' => 'Jl. Pembangunan No. 88, Garut',
                'attach_gp_major' => true,
            ],
            'ABDUL_HAKIM' => [
                'name' => 'Abdul Hakim, S.T., M.T.',
                'base_name' => 'Abdul Hakim',
                'email' => 'abdul.hakim@smkn2garut.sch.id',
                'nip' => '197904122006041008',
                'gender' => 'L',
                'phone' => '081223450013',
                'address' => 'Tarogong Kidul, Kabupaten Garut',
                'attach_gp_major' => true,
            ],
            'HERDI' => [
                'name' => 'Herdi Febriyana, S.T.',
                'base_name' => 'Herdi Febriyana',
                'email' => 'guru@smkn2garut.sch.id',
                'nip' => '198702142011011003',
                'gender' => 'L',
                'phone' => '081223344552',
                'address' => 'Jl. Pembangunan No. 42, Garut Kota',
                'attach_gp_major' => true,
            ],
            'CAHYA' => [
                'name' => 'Cahya Nugraha, S.T., M.T.',
                'base_name' => 'Cahya Nugraha',
                'email' => 'cahya.nugraha@smkn2garut.sch.id',
                'nip' => '198411032009021003',
                'gender' => 'L',
                'phone' => '081223450014',
                'address' => 'Samarang, Kabupaten Garut',
                'attach_gp_major' => true,
            ],
            'ADBEL' => [
                'name' => 'Adbel Yuki Edwar, S.T.',
                'base_name' => 'Adbel Yuki Edwar',
                'email' => 'adbel.yuki@smkn2garut.sch.id',
                'nip' => '198607192011011007',
                'gender' => 'L',
                'phone' => '081223450015',
                'address' => 'Garut Kota, Kabupaten Garut',
                'attach_gp_major' => true,
            ],
            'ERIKA' => [
                'name' => 'Erika Silviani, S.T.',
                'base_name' => 'Erika Silviani',
                'email' => 'erika.silviani@smkn2garut.sch.id',
                'nip' => null,
                'gender' => 'P',
                'phone' => null,
                'address' => 'Garut, Jawa Barat',
                'attach_gp_major' => true,
            ],
            'NISA' => [
                'name' => 'Nisa Dwi Nuranti, S.Pd.',
                'base_name' => 'Nisa Dwi Nuranti',
                'email' => 'nisa.dwi.nuranti@smkn2garut.sch.id',
                'nip' => null,
                'gender' => 'P',
                'phone' => null,
                'address' => 'Garut, Jawa Barat',
                'attach_gp_major' => true,
            ],
            'YAYU' => [
                'name' => 'Yayu Sri Oktaviani, S.Pd.',
                'base_name' => 'Yayu Sri Oktaviani',
                'email' => 'yayu.sri.oktaviani@smkn2garut.sch.id',
                'nip' => null,
                'gender' => 'P',
                'phone' => null,
                'address' => 'Garut, Jawa Barat',
                'attach_gp_major' => false,
            ],
            'EMA' => [
                'name' => 'Ema Rosilawati Nasution, S.Pd.',
                'base_name' => 'Ema Rosilawati Nasution',
                'email' => 'ema.rosilawati.nasution@smkn2garut.sch.id',
                'nip' => null,
                'gender' => 'P',
                'phone' => null,
                'address' => 'Garut, Jawa Barat',
                'attach_gp_major' => false,
            ],
            'ANGGARA' => [
                'name' => 'Anggara Agung Budiman, S.Pd.',
                'base_name' => 'Anggara Agung Budiman',
                'email' => 'anggara.agung.budiman@smkn2garut.sch.id',
                'nip' => null,
                'gender' => 'L',
                'phone' => null,
                'address' => 'Garut, Jawa Barat',
                'attach_gp_major' => false,
            ],
            'SITI_BUDIARTI' => [
                'name' => 'Siti Budiarti, S.H.',
                'base_name' => 'Siti Budiarti',
                'email' => 'siti.budiarti@smkn2garut.sch.id',
                'nip' => null,
                'gender' => 'P',
                'phone' => null,
                'address' => 'Garut, Jawa Barat',
                'attach_gp_major' => false,
            ],
            'NENI' => [
                'name' => 'Neni Setiani, S.Pd.',
                'base_name' => 'Neni Setiani',
                'email' => 'neni.setiani@smkn2garut.sch.id',
                'nip' => null,
                'gender' => 'P',
                'phone' => null,
                'address' => 'Garut, Jawa Barat',
                'attach_gp_major' => false,
            ],
            'MUSTOFA' => [
                'name' => 'Mustofa, M.Pd.',
                'base_name' => 'Mustofa',
                'email' => 'mustofa@smkn2garut.sch.id',
                'nip' => null,
                'gender' => 'L',
                'phone' => null,
                'address' => 'Garut, Jawa Barat',
                'attach_gp_major' => false,
            ],
            'AAP' => [
                'name' => 'Aap Solehudin, S.Pd.',
                'base_name' => 'Aap Solehudin',
                'email' => 'aap.solehudin@smkn2garut.sch.id',
                'nip' => null,
                'gender' => 'L',
                'phone' => null,
                'address' => 'Garut, Jawa Barat',
                'attach_gp_major' => false,
            ],
            'AIDA' => [
                'name' => 'Hj. Aida Maryani S, S.Pd., M.Pd.',
                'base_name' => 'Hj. Aida Maryani S',
                'email' => 'aida.maryani@smkn2garut.sch.id',
                'nip' => null,
                'gender' => 'P',
                'phone' => null,
                'address' => 'Garut, Jawa Barat',
                'attach_gp_major' => false,
            ],
        ];

        $seededTeachers = [];
        foreach ($teachersData as $key => $td) {
            $user = $this->findExistingTeacherUser($school->id, $td['email'], $td['base_name']);

            if (!$user) {
                $user = User::create([
                    'school_id' => $school->id,
                    'name' => $td['name'],
                    'email' => $td['email'],
                    'password' => $passwordHash,
                    'role' => 'guru-pembimbing',
                    'is_active' => true,
                ]);
            }

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'gender' => $td['gender'],
                    'phone' => $td['phone'],
                    'address' => $td['address'],
                ]
            );

            $teacher = Teacher::firstOrCreate(
                ['user_id' => $user->id],
                ['nip' => $td['nip']]
            );

            if ($td['attach_gp_major']) {
                $teacher->majors()->syncWithoutDetaching([$majorGp->id]);
            }

            $seededTeachers[$key] = $teacher;
        }

        // Tetapkan Kepala Jurusan (Kajur) GP jika belum ditetapkan
        if (!$majorGp->head_teacher_id && isset($seededTeachers['HERDI'])) {
            $majorGp->update([
                'head_teacher_id' => $seededTeachers['HERDI']->id,
            ]);
        }

        // 3. Kelas XII GP 1 & XII GP 2 beserta Wali Kelas sesuai data live
        $classGp1 = SchoolClass::updateOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'XII GP 1',
            ],
            [
                'major_id' => $majorGp->id,
                'homeroom_teacher_id' => $seededTeachers['ERIKA']->id,
                'grade_level' => 'XII',
                'academic_year' => '2026/2027',
            ]
        );

        $classGp2 = SchoolClass::updateOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'XII GP 2',
            ],
            [
                'major_id' => $majorGp->id,
                'homeroom_teacher_id' => $seededTeachers['NISA']->id,
                'grade_level' => 'XII',
                'academic_year' => '2026/2027',
            ]
        );

        // 4. Periode PKL aktif
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

        // 5. Data 11 Instansi / DU/DI GP (disamakan dengan nama instansi live)
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
            'PT. Timah Persero, Tbk.' => [
                'name' => 'PT. Timah Persero, Tbk.',
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
            'Pertamina Geothermal Energy' => [
                'name' => 'Pertamina Geothermal Energy',
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
            'ATR BPN' => [
                'name' => 'ATR BPN',
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
            'PT. GEOSERVICE' => [
                'name' => 'PT. GEOSERVICE',
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
            'PT. ARYA SWASCIPTA REKAYASA/PT. MAHATMA' => [
                'name' => 'PT. ARYA SWASCIPTA REKAYASA/PT. MAHATMA',
                'address' => 'Jl. Pahlawan No. 95, Sukaluyu, Kec. Cibeunying, Kota Bandung, Jawa Barat 40123',
                'contact_person' => 'Firman Maulana, S.T.',
                'phone' => '022-2503411',
                'latitude' => -6.89740000,
                'longitude' => 107.63210000,
                'supervisor_name' => 'Firman Maulana, S.T.',
                'supervisor_email' => 'spv.aryaswascipta@asrekayasa.co.id',
                'supervisor_gender' => 'L',
            ],
        ];

        $startedCompanies = [
            'PT. DATEM',
            'PT. SANGGA BUANA NUSANTARA',
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
                    'is_pkl_started' => in_array($cd['name'], $startedCompanies, true),
                ]
            );

            $company->majors()->syncWithoutDetaching([$majorGp->id]);

            $supervisorUser = User::firstOrCreate(
                ['email' => $cd['supervisor_email']],
                [
                    'school_id' => $school->id,
                    'name' => $cd['supervisor_name'],
                    'password' => $passwordHash,
                    'role' => 'pembimbing-dudi',
                    'is_active' => true,
                ]
            );

            Profile::firstOrCreate(
                ['user_id' => $supervisorUser->id],
                [
                    'gender' => $cd['supervisor_gender'],
                    'phone' => $cd['phone'],
                    'address' => $cd['address'],
                ]
            );

            $supervisor = Supervisor::firstOrCreate(
                ['user_id' => $supervisorUser->id],
                [
                    'company_id' => $company->id,
                    'position' => 'Pembimbing Lapangan Industri DU/DI',
                ]
            );

            $seededCompanies[$key] = $company;
            $seededSupervisors[$key] = $supervisor;
        }

        // 6. Data 68 Siswa Jurusan Geologi Pertambangan & Plotting Penempatan sesuai data live
        $studentsRaw = [
            // XII GP 1 (34 siswa)
            ['nis' => '242510467', 'nisn' => '0092253991', 'name' => 'Anugrah Ilman Nurhadi', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ABDUL_HAKIM', 'gender' => 'L', 'phone' => '082120836755'],
            ['nis' => '242510468', 'nisn' => '0098499926', 'name' => 'Azril Muhamad Assyuro', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ABDUL_HAKIM', 'gender' => 'L', 'phone' => '082120836755'],
            ['nis' => '242510469', 'nisn' => '0083191727', 'name' => 'Carrel Munjid Al Rasyid', 'class' => 'XII GP 1', 'company' => 'Pertamina Geothermal Energy', 'teacher' => 'ABDUL_HAKIM', 'gender' => 'L', 'phone' => '0895701092083'],
            ['nis' => '242510470', 'nisn' => '0089330696', 'name' => 'Dika Hermawan', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'DANANG', 'gender' => 'L', 'phone' => '087834362181'],
            ['nis' => '242510471', 'nisn' => '0085711691', 'name' => 'Fadli Yudha Prasetia Ramdani', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'DANANG', 'gender' => 'L', 'phone' => '082130463982'],
            ['nis' => '242510472', 'nisn' => '0081495350', 'name' => 'Faisal Fahri Ramadhan', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'DANANG', 'gender' => 'L', 'phone' => '081223858781'],
            ['nis' => '242510473', 'nisn' => '0097324725', 'name' => 'Farel Elsandi', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ADBEL', 'gender' => 'L', 'phone' => '0895357280899'],
            ['nis' => '242510474', 'nisn' => '0105262820', 'name' => 'Faruk Firmansyah Aziz', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ADBEL', 'gender' => 'L', 'phone' => '085222532531'],
            ['nis' => '242510475', 'nisn' => '0094512375', 'name' => 'Ghassan Fatih Rayyan', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ADBEL', 'gender' => 'L', 'phone' => '083150070444'],
            ['nis' => '242510476', 'nisn' => '3082524222', 'name' => 'Haura Annisa Awwaliyah', 'class' => 'XII GP 1', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'CAHYA', 'gender' => 'P', 'phone' => '08812055703'],
            ['nis' => '242510477', 'nisn' => '0082580068', 'name' => 'Ihsan Tamyiz', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'CAHYA', 'gender' => 'L', 'phone' => '08815726580'],
            ['nis' => '242510478', 'nisn' => '0085330049', 'name' => 'Ikmal Lathif Darajat', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ERIKA', 'gender' => 'L', 'phone' => '085213573992'],
            ['nis' => '242510479', 'nisn' => '3093095601', 'name' => 'Kennino Farras', 'class' => 'XII GP 1', 'company' => 'Pertamina Geothermal Energy', 'teacher' => 'ERIKA', 'gender' => 'L', 'phone' => '089510196636'],
            ['nis' => '242510480', 'nisn' => '0094170221', 'name' => 'Kevin Oktavian Revano', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'HERDI', 'gender' => 'L', 'phone' => '0895418798833'],
            ['nis' => '242510481', 'nisn' => '0082682201', 'name' => 'Langit Kresna Harzadi', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'HERDI', 'gender' => 'L', 'phone' => '0882001779457'],
            ['nis' => '242510482', 'nisn' => '0098284999', 'name' => 'Moh. Tristan Maulana Ibrahim', 'class' => 'XII GP 1', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'MUSTOFA', 'gender' => 'L', 'phone' => '089601850101'],
            ['nis' => '242510483', 'nisn' => '0081677595', 'name' => 'Muhamad Fathur Rohman', 'class' => 'XII GP 1', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'MUSTOFA', 'gender' => 'L', 'phone' => '0895329813878'],
            ['nis' => '242510484', 'nisn' => '0093723103', 'name' => 'Muhammad Akbar Zaelani', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'SITI_BUDIARTI', 'gender' => 'L', 'phone' => '083181379089'],
            ['nis' => '242510485', 'nisn' => '0095191695', 'name' => 'Muhammad Fauzan', 'class' => 'XII GP 1', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'SITI_BUDIARTI', 'gender' => 'L', 'phone' => '089509107970'],
            ['nis' => '242510486', 'nisn' => '0096979330', 'name' => 'Muhammad Hawari Maolana', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'EMA', 'gender' => 'L', 'phone' => '085134917274'],
            ['nis' => '242510487', 'nisn' => '0099412440', 'name' => 'Muhammad Kahfi', 'class' => 'XII GP 1', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'EMA', 'gender' => 'L', 'phone' => '089677080848'],
            ['nis' => '242510488', 'nisn' => '0097114236', 'name' => 'Nadhif Musyaffa Al Ghani', 'class' => 'XII GP 1', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'AIDA', 'gender' => 'L', 'phone' => '08132207207'],
            ['nis' => '242510489', 'nisn' => '0093490485', 'name' => 'Nazmy Hudzaifah Fazra Nurhikmat', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'AIDA', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510490', 'nisn' => '3087103824', 'name' => 'Novaldio', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'YAYU', 'gender' => 'L', 'phone' => '085129917927'],
            ['nis' => '242510491', 'nisn' => '0089786866', 'name' => 'Pasha Kaisar Firmansyah', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'YAYU', 'gender' => 'L', 'phone' => '081225545067'],
            ['nis' => '242510492', 'nisn' => '0095192399', 'name' => 'Piki Pahrurrazi', 'class' => 'XII GP 1', 'company' => 'PT. Solusi Bangun Indonesia', 'teacher' => 'YAYU', 'gender' => 'L', 'phone' => '083130090906'],
            ['nis' => '242510493', 'nisn' => '0092359360', 'name' => 'Randy Putra Andika', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'NENI', 'gender' => 'L', 'phone' => '085351598504'],
            ['nis' => '242510495', 'nisn' => '0093127445', 'name' => 'Reihan Mahaputra Adinatha', 'class' => 'XII GP 1', 'company' => 'PT. GEOSERVICE', 'teacher' => 'NENI', 'gender' => 'L', 'phone' => '085245808200'],
            ['nis' => '242510496', 'nisn' => '0092167743', 'name' => 'Revan Januar', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ANGGARA', 'gender' => 'L', 'phone' => '088218588481'],
            ['nis' => '242510497', 'nisn' => '0095993883', 'name' => 'Ridho Ahmad Pamungkas', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'ANGGARA', 'gender' => 'L', 'phone' => '08812249340'],
            ['nis' => '242510498', 'nisn' => '0083482124', 'name' => 'Rival Kartono', 'class' => 'XII GP 1', 'company' => 'Pertamina Geothermal Energy', 'teacher' => 'NISA', 'gender' => 'L', 'phone' => '0895361428214'],
            ['nis' => '242510499', 'nisn' => '0096266478', 'name' => 'Syahdan Tri Atala', 'class' => 'XII GP 1', 'company' => 'PT. DATEM', 'teacher' => 'NISA', 'gender' => 'L', 'phone' => '083867016036'],
            ['nis' => '242510500', 'nisn' => '0081888616', 'name' => 'Vincent Achmad Fauzi', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'CAHYA', 'gender' => 'L', 'phone' => '089670364500'],
            ['nis' => '242510501', 'nisn' => '0094267185', 'name' => 'Zahran Haikal Gursida', 'class' => 'XII GP 1', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ERIKA', 'gender' => 'L', 'phone' => '0895365783300'],

            // XII GP 2 (34 siswa)
            ['nis' => '242510502', 'nisn' => '0086089719', 'name' => 'Affandi Danuningrat', 'class' => 'XII GP 2', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'ABDUL_HAKIM', 'gender' => 'L', 'phone' => '082295216348'],
            ['nis' => '242510503', 'nisn' => '0094286823', 'name' => 'Andika Satria Saputra', 'class' => 'XII GP 2', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'ABDUL_HAKIM', 'gender' => 'L', 'phone' => '0895428870900', 'demo_alias' => 'siswa@smkn2garut.sch.id'],
            ['nis' => '242510504', 'nisn' => '0094394213', 'name' => 'Azfar Shazwan Malazi', 'class' => 'XII GP 2', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ABDUL_HAKIM', 'gender' => 'L', 'phone' => '081998294746'],
            ['nis' => '242510505', 'nisn' => '0096583456', 'name' => 'Bintang Malik As Shidiq', 'class' => 'XII GP 2', 'company' => 'PT. DATEM', 'teacher' => 'DANANG', 'gender' => 'L', 'phone' => '089637393455'],
            ['nis' => '242510507', 'nisn' => '0095772575', 'name' => 'Fani Sofianti', 'class' => 'XII GP 2', 'company' => 'ATR BPN', 'teacher' => 'DANANG', 'gender' => 'P', 'phone' => '08886008478'],
            ['nis' => '242510508', 'nisn' => '0097323758', 'name' => 'Farhan Naif Rayhana', 'class' => 'XII GP 2', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'DANANG', 'gender' => 'L', 'phone' => '087766337114'],
            ['nis' => '242510509', 'nisn' => '0098648868', 'name' => 'Fitri Ramdani', 'class' => 'XII GP 2', 'company' => 'BBPMB tekMIRA', 'teacher' => 'ADBEL', 'gender' => 'P', 'phone' => '0895619882728'],
            ['nis' => '242510510', 'nisn' => '0092511104', 'name' => 'Fitria Khoerunnisa', 'class' => 'XII GP 2', 'company' => 'PT. GEOSERVICE', 'teacher' => 'ADBEL', 'gender' => 'P', 'phone' => '088806303767'],
            ['nis' => '242510511', 'nisn' => '0089755605', 'name' => 'Hadi Wijaya', 'class' => 'XII GP 2', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ADBEL', 'gender' => 'L', 'phone' => '08317097554'],
            ['nis' => '242510512', 'nisn' => '0099880763', 'name' => 'Harlan Julian Abdulah', 'class' => 'XII GP 2', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'CAHYA', 'gender' => 'L', 'phone' => '083179280589'],
            ['nis' => '242510513', 'nisn' => '3070590887', 'name' => 'Ibnu Ali Hajj', 'class' => 'XII GP 2', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'CAHYA', 'gender' => 'L', 'phone' => '081779173659'],
            ['nis' => '242510514', 'nisn' => '3097495595', 'name' => 'Ikhsan Ahmad Rifai', 'class' => 'XII GP 2', 'company' => 'PT. GEOSERVICE', 'teacher' => 'ERIKA', 'gender' => 'L', 'phone' => '0881023702719'],
            ['nis' => '242510515', 'nisn' => '0081794543', 'name' => 'Ilham Bahir Aditya', 'class' => 'XII GP 2', 'company' => 'BBPMB tekMIRA', 'teacher' => 'ERIKA', 'gender' => 'L', 'phone' => '088806313688'],
            ['nis' => '242510516', 'nisn' => '0091514548', 'name' => 'Kesyha Apriliani', 'class' => 'XII GP 2', 'company' => 'PT. GEOSERVICE', 'teacher' => 'HERDI', 'gender' => 'P', 'phone' => '083878683481'],
            ['nis' => '242510517', 'nisn' => '0098722997', 'name' => 'Mohamad Fathan Ramadani', 'class' => 'XII GP 2', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'HERDI', 'gender' => 'L', 'phone' => '0895400968743'],
            ['nis' => '242510518', 'nisn' => '0093287983', 'name' => 'Muhamad Diaz Fadlurohman', 'class' => 'XII GP 2', 'company' => 'PT. BHADRA PINGGALA SEJAHTERA MINING SERVICE', 'teacher' => 'MUSTOFA', 'gender' => 'L', 'phone' => '0895428923232'],
            ['nis' => '242510519', 'nisn' => '0095567392', 'name' => 'Muhamad Irfan Ardiansyah', 'class' => 'XII GP 2', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'MUSTOFA', 'gender' => 'L', 'phone' => '089643296805'],
            ['nis' => '242510520', 'nisn' => '0079405198', 'name' => 'Muhamad Syafik Al Akbar', 'class' => 'XII GP 2', 'company' => 'PT. DATEM', 'teacher' => 'SITI_BUDIARTI', 'gender' => 'L', 'phone' => '088971830807'],
            ['nis' => '242510521', 'nisn' => '0098003728', 'name' => 'Muhammad Adzka Alindra', 'class' => 'XII GP 2', 'company' => 'Pertamina Geothermal Energy', 'teacher' => 'SITI_BUDIARTI', 'gender' => 'L', 'phone' => '0882002818333'],
            ['nis' => '242510522', 'nisn' => '0096900374', 'name' => 'Muhammad Azka Alawi', 'class' => 'XII GP 2', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'EMA', 'gender' => 'L', 'phone' => '087830862644'],
            ['nis' => '242510523', 'nisn' => '3094845475', 'name' => 'Muhammad Rafa Syamla Imani', 'class' => 'XII GP 2', 'company' => 'CV. GUNUNG BATUJAJAR', 'teacher' => 'EMA', 'gender' => 'L', 'phone' => '083128674306'],
            ['nis' => '242510524', 'nisn' => '0098666825', 'name' => 'Muhammad Rizki Saputra', 'class' => 'XII GP 2', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'AAP', 'gender' => 'L', 'phone' => '089654187694'],
            ['nis' => '242510525', 'nisn' => '0098569417', 'name' => 'Nadhif Fathoni', 'class' => 'XII GP 2', 'company' => 'PT. BHADRA PINGGALA SEJAHTERA MINING SERVICE', 'teacher' => 'AAP', 'gender' => 'L', 'phone' => '0895604010805'],
            ['nis' => '242510526', 'nisn' => '0097486431', 'name' => 'Nazil Ahkam Musabbih', 'class' => 'XII GP 2', 'company' => 'PT. ARYA SWASCIPTA REKAYASA/PT. MAHATMA', 'teacher' => 'YAYU', 'gender' => 'L', 'phone' => '0895385347578'],
            ['nis' => '242510527', 'nisn' => '0093063341', 'name' => 'Nofa Adithya Dwi Heryanto', 'class' => 'XII GP 2', 'company' => 'CV. GUNUNG BATUJAJAR', 'teacher' => 'YAYU', 'gender' => 'L', 'phone' => '085962871630'],
            ['nis' => '242510528', 'nisn' => '0087934205', 'name' => 'Panji Putra Ramadhan', 'class' => 'XII GP 2', 'company' => 'BBPMB tekMIRA', 'teacher' => 'YAYU', 'gender' => 'L', 'phone' => '083117368236'],
            ['nis' => '242510529', 'nisn' => '0091140603', 'name' => 'Radinan Azka Najmur Rasyad', 'class' => 'XII GP 2', 'company' => 'PT. ARYA SWASCIPTA REKAYASA/PT. MAHATMA', 'teacher' => 'NENI', 'gender' => 'L', 'phone' => '0895385283945'],
            ['nis' => '242510530', 'nisn' => '0095029468', 'name' => 'Refie Wibawa Saputra', 'class' => 'XII GP 2', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'NENI', 'gender' => 'L', 'phone' => '085794787104'],
            ['nis' => '242510531', 'nisn' => '0094397515', 'name' => 'Renza Taufik Yugaswara', 'class' => 'XII GP 2', 'company' => 'PT. SANGGA BUANA NUSANTARA', 'teacher' => 'ANGGARA', 'gender' => 'L', 'phone' => '083852210483'],
            ['nis' => '242510532', 'nisn' => '0096056982', 'name' => 'Reza Setya Roland Pratama', 'class' => 'XII GP 2', 'company' => 'CV. GUNUNG BATUJAJAR', 'teacher' => 'ANGGARA', 'gender' => 'L', 'phone' => '083163813694'],
            ['nis' => '242510533', 'nisn' => '0098587030', 'name' => 'Ridho Juliansyah', 'class' => 'XII GP 2', 'company' => 'PT. DATEM', 'teacher' => 'NISA', 'gender' => 'L', 'phone' => '0895336550744'],
            ['nis' => '242510534', 'nisn' => '0092109182', 'name' => 'Ridho Rizki Habibi', 'class' => 'XII GP 2', 'company' => 'CV. GUNUNG BATUJAJAR', 'teacher' => 'NISA', 'gender' => 'L', 'phone' => '0895371941040'],
            ['nis' => '242510535', 'nisn' => '0091098552', 'name' => 'Satrio Raizal Abdullah', 'class' => 'XII GP 2', 'company' => 'BBPMB tekMIRA', 'teacher' => 'CAHYA', 'gender' => 'L', 'phone' => '0895353584200'],
            ['nis' => '242510536', 'nisn' => '0097348577', 'name' => 'Triyoseph Marcheliano', 'class' => 'XII GP 2', 'company' => 'PT. Timah Persero, Tbk.', 'teacher' => 'ERIKA', 'gender' => 'L', 'phone' => '081292892652'],
        ];

        $studentCounter = 0;

        foreach ($studentsRaw as $st) {
            $nis = $st['nis'];
            $nisn = $st['nisn'];

            $targetClass = ($st['class'] === 'XII GP 1') ? $classGp1 : $classGp2;
            $company = $seededCompanies[$st['company']] ?? null;
            $supervisor = $seededSupervisors[$st['company']] ?? null;
            $teacher = $seededTeachers[$st['teacher']] ?? null;

            if (!$company || !$supervisor || !$teacher) {
                continue;
            }

            $email = $st['demo_alias'] ?? "siswa.{$nis}@smkn2garut.sch.id";

            // Cocokkan siswa existing berdasarkan NIS atau NISN (aman untuk data live tanpa menghapus relasi)
            $existingStudent = Student::whereHas('user', fn ($q) => $q->where('school_id', $school->id))
                ->where(function ($q) use ($nis, $nisn) {
                    $q->where('nis', $nis)->orWhere('nisn', $nisn);
                })
                ->with('user')
                ->first();

            $studentUser = $existingStudent?->user;
            if ($studentUser) {
                $studentUser->update([
                    'name' => $st['name'],
                    'wa_number' => $st['phone'],
                    'is_active' => true,
                ]);
            } else {
                $studentUser = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'school_id' => $school->id,
                        'name' => $st['name'],
                        'wa_number' => $st['phone'],
                        'password' => $passwordHash,
                        'role' => 'siswa',
                        'is_active' => true,
                    ]
                );
            }

            Profile::updateOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'gender' => $st['gender'],
                    'phone' => $st['phone'],
                    'address' => 'Kabupaten Garut, Jawa Barat',
                ]
            );

            $student = $existingStudent ?? Student::where('user_id', $studentUser->id)->first();
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
            $this->command->info("✅ [GeologiPertambanganPklSeeder] Berhasil menyinkronkan data GP SMKN 2 Garut:");
            $this->command->info("   - Jurusan: {$majorGp->name} ({$majorGp->code})");
            $this->command->info("   - Kepala Jurusan: " . ($majorGp->headTeacher?->user?->name ?? '-'));
            $this->command->info("   - Kelas: XII GP 1 (Wali: Erika Silviani, S.T.) & XII GP 2 (Wali: Nisa Dwi Nuranti, S.Pd.)");
            $this->command->info("   - Guru Pembimbing: " . count($seededTeachers) . " guru");
            $this->command->info("   - Total Instansi / DU/DI: " . count($seededCompanies) . " perusahaan");
            $this->command->info("   - Total Siswa & Plotting PKL: {$studentCounter} siswa aktif");
        }
    }

    private function findExistingTeacherUser(int $schoolId, string $email, string $baseName): ?User
    {
        $byEmail = User::where('email', $email)->first();
        if ($byEmail) {
            return $byEmail;
        }

        $targetClean = $this->normalizeBaseName($baseName);

        return User::where('school_id', $schoolId)
            ->where('role', 'guru-pembimbing')
            ->get()
            ->first(function (User $u) use ($targetClean) {
                $existingClean = $this->normalizeBaseName(explode(',', $u->name)[0]);

                return $existingClean === $targetClean;
            });
    }

    private function normalizeBaseName(string $name): string
    {
        $clean = Str::lower(trim($name));
        $clean = preg_replace('/^(hj\.?|h\.?|drs\.?|dra\.?|ir\.?|dr\.?|apt\.?)\s+/i', '', $clean) ?? $clean;

        return trim($clean);
    }
}
