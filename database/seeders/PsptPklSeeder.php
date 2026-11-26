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

class PsptPklSeeder extends Seeder
{
    /**
     * Seeder modular Penempatan Siswa PKL Jurusan PSPT SMKN 2 Garut (disinkronkan dengan data live):
     * 1. Jurusan & Kelas PSPT (XII PSPT 1 & XII PSPT 2) beserta Wali Kelas masing-masing
     * 2. 17 Guru Pembimbing PSPT + Wali Kelas
     * 3. 18 Instansi / DU/DI & Pembimbing Industri
     * 4. 68 Siswa PSPT (65 terplotting aktif + 3 belum terplotting, lengkap dengan No. WhatsApp)
     */
    public function run(): void
    {
        $school = School::where('npsn', '20209203')->first() ?? School::firstOrFail();

        $majorPspt = Major::firstOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'PSPT',
            ],
            [
                'name' => 'Produksi dan Siaran Program Televisi',
            ]
        );

        // 1. Capaian Pembelajaran (CP) Kurikulum Merdeka Jurusan PSPT
        $psptLearningObjectives = [
            [
                'code' => 'PSPT-CP01',
                'title' => 'Pra-Produksi & Riset Manajemen Program Televisi',
                'element_name' => 'Manajemen Pra-Produksi',
                'description' => 'Menyusun treatment naskah, breakdown teknis produksi, storyboard, rundown program, dan koordinasi departemen.',
                'target_hours' => 80,
                'sort_order' => 1,
            ],
            [
                'code' => 'PSPT-CP02',
                'title' => 'Tata Kamera & Pencahayaan Studio Multi-Camera System',
                'element_name' => 'Kamera & Pencahayaan',
                'description' => 'Mengoperasikan kamera studio/ENG, komposisi shot broadcast, setup sistem 3-point lighting dan tata cahaya panggung.',
                'target_hours' => 140,
                'sort_order' => 2,
            ],
            [
                'code' => 'PSPT-CP03',
                'title' => 'Tata Suara & Audio Mixing Siaran Langsung (Live Broadcast)',
                'element_name' => 'Audio & Tata Suara',
                'description' => 'Instalasi mikrofon wireless/lavalier, mixing audio broadcast multi-track, monitoring level audio, dan reduksi noise.',
                'target_hours' => 100,
                'sort_order' => 3,
            ],
            [
                'code' => 'PSPT-CP04',
                'title' => 'Master Control Room (MCR) & Video Switching Multi-Channel',
                'element_name' => 'Master Control & Switching',
                'description' => 'Mengoperasikan switcher video siaran langsung, routing sinyal broadcast, integrasi CG, video server, dan teleprompter.',
                'target_hours' => 120,
                'sort_order' => 4,
            ],
            [
                'code' => 'PSPT-CP05',
                'title' => 'Pascaproduksi, Video Editing Digital & Mastering Transmisi',
                'element_name' => 'Editing & Pascaproduksi',
                'description' => 'Melakukan editing offline/online, audio sweetener, color grading standar broadcast EBU/ITU, dan export master DCP/Web.',
                'target_hours' => 120,
                'sort_order' => 5,
            ],
        ];

        foreach ($psptLearningObjectives as $cp) {
            LearningObjective::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'major_id' => $majorPspt->id,
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

        // 2. Guru Pembimbing & Wali Kelas Jurusan PSPT
        $passwordHash = Hash::make('password');
        $teachersCatalog = [
            'FITRIES' => ['name' => 'Fitries Erinda Hapsari, S.ST', 'email' => 'fitries.erinda@smkn2garut.sch.id', 'gender' => 'P'],
            'RIDWAN_SD' => ['name' => 'Ridwan SD, S.Sos., M.I.Kom', 'email' => 'ridwan.sd@smkn2garut.sch.id', 'gender' => 'L'],
            'LUTHFI' => ['name' => 'M. Luthfi Nur Kamal Abd, S.Tr.Sn', 'email' => 'luthfi.nurkamal@smkn2garut.sch.id', 'gender' => 'L'],
            'ALIN' => ['name' => 'Alin Rosalina, S.Pd', 'email' => 'alin.rosalina@smkn2garut.sch.id', 'gender' => 'P'],
            'IKBAL' => ['name' => 'Ikbal Rindi Fauzi, S.Pd', 'email' => 'ikbal.rindi@smkn2garut.sch.id', 'gender' => 'L'],
            'ANA' => ['name' => 'Ana Nursyifa, S.I.Kom', 'email' => 'ana.nursyifa@smkn2garut.sch.id', 'gender' => 'P'],
            'RINI' => ['name' => 'Rini Anggraini, S.Pd.I', 'email' => 'rini.anggraini@smkn2garut.sch.id', 'gender' => 'P'],
            'CITRA' => ['name' => 'Citra Islamiati Sartika, S.Pd.', 'email' => 'citra.islamiati@smkn2garut.sch.id', 'gender' => 'P'],
            'UTAMI' => ['name' => 'Utami Kaniawati, S.Pd.', 'email' => 'utami.kaniawati@smkn2garut.sch.id', 'gender' => 'P'],
            'GATI' => ['name' => 'Gati Intan Tamara, S.Pd.', 'email' => 'gati.intan@smkn2garut.sch.id', 'gender' => 'P'],
            'ERWIN' => ['name' => 'Erwin Sehabudin, S.Pd.', 'email' => 'erwin.sehabudin@smkn2garut.sch.id', 'gender' => 'L'],
            'AAN' => ['name' => 'Aan Anggraeni, S.Pd.', 'email' => 'aan.anggraeni@smkn2garut.sch.id', 'gender' => 'P'],
            'ASEP' => ['name' => 'Asep Suhendra, S.Pd., M.Pd.', 'email' => 'asep.suhendra@smkn2garut.sch.id', 'gender' => 'L'],
            'NITA' => ['name' => 'Nita Sumiartini, S.Pd.', 'email' => 'nita.sumiartini@smkn2garut.sch.id', 'gender' => 'P'],
            'MIRA' => ['name' => 'Mira Asmilawati, S.Pd.', 'email' => 'mira.asmilawati@smkn2garut.sch.id', 'gender' => 'P'],
            'NURWITA' => ['name' => 'Nurwita Daini Fatah, S.Pd', 'email' => 'nurwita.daini@smkn2garut.sch.id', 'gender' => 'P'],
            'AHMAD_YANI' => ['name' => 'Ahmad Yani, S.Pd., M.M.', 'email' => 'ahmad.yani@smkn2garut.sch.id', 'gender' => 'L'],
            'RIDWAN_WALI' => ['name' => 'Ridwan Setiawan Darajat, S.Sos., M.I.Kom', 'email' => 'ridwan.setiawan@smkn2garut.sch.id', 'gender' => 'L'],
        ];

        $seededTeachers = [];
        foreach ($teachersCatalog as $key => $tData) {
            $tUser = User::where('email', $tData['email'])->first();
            if (!$tUser) {
                $tUser = User::create([
                    'school_id' => $school->id,
                    'name' => $tData['name'],
                    'email' => $tData['email'],
                    'password' => $passwordHash,
                    'role' => 'guru-pembimbing',
                    'is_active' => true,
                ]);
            }

            Profile::firstOrCreate(
                ['user_id' => $tUser->id],
                [
                    'gender' => $tData['gender'],
                    'address' => 'Kabupaten Garut, Jawa Barat',
                ]
            );

            $teacher = Teacher::firstOrCreate(
                ['user_id' => $tUser->id],
                ['nip' => null]
            );
            $teacher->majors()->syncWithoutDetaching([$majorPspt->id]);
            $seededTeachers[$key] = $teacher;
        }

        // 3. Kelas XII PSPT 1 & XII PSPT 2 beserta Wali Kelas sesuai data live
        $classPspt1 = SchoolClass::updateOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'XII PSPT 1',
            ],
            [
                'major_id' => $majorPspt->id,
                'homeroom_teacher_id' => $seededTeachers['ASEP']->id,
                'grade_level' => 'XII',
                'academic_year' => '2026/2027',
            ]
        );

        $classPspt2 = SchoolClass::updateOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'XII PSPT 2',
            ],
            [
                'major_id' => $majorPspt->id,
                'homeroom_teacher_id' => $seededTeachers['RIDWAN_WALI']->id,
                'grade_level' => 'XII',
                'academic_year' => '2026/2027',
            ]
        );

        // 4. Periode PKL aktif
        $period = PklPeriod::where('school_id', $school->id)->where('is_active', true)->first();
        if (!$period) {
            $period = PklPeriod::create([
                'school_id' => $school->id,
                'name' => 'PKL Industri Penyiaran & Media Televisi TP 2026/2027',
                'start_date' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->addMonths(4)->endOfMonth()->toDateString(),
                'is_active' => true,
            ]);
        }

        // 5. Data 18 DUDI / Instansi Penempatan PKL PSPT (disamakan dengan nama instansi live)
        $companiesData = [
            'HARIAN GARUT NEWS' => [
                'name' => 'HARIAN GARUT NEWS',
                'address' => 'Komp. Perum Villa Lembah Asri, Tarogong Kidul, Kabupaten Garut',
                'contact_person' => 'Pimpinan Redaksi Harian Garut News',
                'phone' => '081220011001',
                'latitude' => -7.21550000,
                'longitude' => 107.89520000,
                'supervisor_name' => 'H. Dedi Haryadi, S.Sos.',
                'supervisor_email' => 'spv.hgn@garutnews.id',
                'supervisor_gender' => 'L',
            ],
            'GARUT 60 DETIK' => [
                'name' => 'GARUT 60 DETIK',
                'address' => 'Jl. Patriot No.2, Sukagalih, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Kepala Redaksi Garut 60 Detik',
                'phone' => '081220011002',
                'latitude' => -7.20850000,
                'longitude' => 107.89250000,
                'supervisor_name' => 'Asep Saepuloh, S.I.Kom.',
                'supervisor_email' => 'spv.garut60detik@media.id',
                'supervisor_gender' => 'L',
            ],
            'RADAR GARUT' => [
                'name' => 'RADAR GARUT',
                'address' => 'Jl. Raya Samarang No.129, Sukagalih, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'General Manager Radar Garut',
                'phone' => '0262-231129',
                'latitude' => -7.21800000,
                'longitude' => 107.88600000,
                'supervisor_name' => 'Agus Ramdani, S.E.',
                'supervisor_email' => 'spv.radargarut@radartasik.id',
                'supervisor_gender' => 'L',
            ],
            'INFO GARUT' => [
                'name' => 'INFO GARUT',
                'address' => 'Griya Intan Nusa Indah - Jalan Nusa Indah, Tarogong Kidul, Garut',
                'contact_person' => 'Head of Production Info Garut',
                'phone' => '081220011004',
                'latitude' => -7.21200000,
                'longitude' => 107.89800000,
                'supervisor_name' => 'Fikri Pratama, S.Sn.',
                'supervisor_email' => 'spv.infogarut@infogarut.co.id',
                'supervisor_gender' => 'L',
            ],
            'DISKOMINFO KAB. GARUT' => [
                'name' => 'DISKOMINFO KAB. GARUT',
                'address' => 'Jl. Pembangunan No. 181, Kelurahan Sukagalih, Kecamatan Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Kabid Informasi & Komunikasi Publik Diskominfo',
                'phone' => '0262-232181',
                'latitude' => -7.21650000,
                'longitude' => 107.89550000,
                'supervisor_name' => 'Encep Supriatna, S.T., M.Si.',
                'supervisor_email' => 'spv.diskominfo@garutkab.go.id',
                'supervisor_gender' => 'L',
            ],
            'WARTA MUDA' => [
                'name' => 'WARTA MUDA',
                'address' => 'PERPUSTAKAAN GARUT Jl. Rsu DR. Slamet No.7, Sukakarya, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Redaktur Pelaksana Warta Muda',
                'phone' => '081220011006',
                'latitude' => -7.22100000,
                'longitude' => 107.90200000,
                'supervisor_name' => 'Dini Anggraeni, S.I.Kom.',
                'supervisor_email' => 'spv.wartamuda@wartamuda.id',
                'supervisor_gender' => 'P',
            ],
            'HUMAS POLRES GARUT' => [
                'name' => 'HUMAS POLRES GARUT',
                'address' => 'Jl. Jendral Sudirman No. 204, Suci Kaler, Kec. Karangpawitan, Kabupaten Garut',
                'contact_person' => 'Kasi Humas Polres Garut',
                'phone' => '0262-233110',
                'latitude' => -7.20500000,
                'longitude' => 107.92500000,
                'supervisor_name' => 'Ipda Susanto, S.H.',
                'supervisor_email' => 'spv.humaspolres@polri.go.id',
                'supervisor_gender' => 'L',
            ],
            'PT. AKSA GROUP CORP' => [
                'name' => 'PT. AKSA GROUP CORP',
                'address' => 'Perum Bintang Residence Blok C7 - Karangpawitan, Kabupaten Garut',
                'contact_person' => 'Managing Director PT Aksa Group Corp',
                'phone' => '081220011008',
                'latitude' => -7.20700000,
                'longitude' => 107.93000000,
                'supervisor_name' => 'Rangga Wijaya, S.E.',
                'supervisor_email' => 'spv.aksagroup@aksacorp.com',
                'supervisor_gender' => 'L',
            ],
            'ABUL YATAMA' => [
                'name' => 'ABUL YATAMA',
                'address' => 'Pesantren Yatim Abulyatama Garut, Jl. Jati No.93, Jati, Kec. Tarogong Kaler, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Pengurus Yayasan & Media Dakwah Abulyatama',
                'phone' => '081220011009',
                'latitude' => -7.19900000,
                'longitude' => 107.89700000,
                'supervisor_name' => 'Ust. Asep Nurzaman, S.Pd.I.',
                'supervisor_email' => 'spv.abulyatama@abulyatama.or.id',
                'supervisor_gender' => 'L',
            ],
            'DISDAMKAR KAB GARUT' => [
                'name' => 'DISDAMKAR KAB GARUT',
                'address' => 'Sukagalih, Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Kasi Publikasi & Dokumentasi Operasional',
                'phone' => '0262-232113',
                'latitude' => -7.21400000,
                'longitude' => 107.89400000,
                'supervisor_name' => 'Iwan Ridwan, S.Sos.',
                'supervisor_email' => 'spv.damkar@garutkab.go.id',
                'supervisor_gender' => 'L',
            ],
            'PENGADILAN AGAMA KAB GARUT' => [
                'name' => 'PENGADILAN AGAMA KAB GARUT',
                'address' => 'Jl. Suherman No.215, Tarogong Kaler, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Bagian Umum & Publikasi Pengadilan Agama Kab. Garut',
                'phone' => '0262-231222',
                'latitude' => -7.20450000,
                'longitude' => 107.89300000,
                'supervisor_name' => 'Bayu Wicaksono, S.H.',
                'supervisor_email' => 'spv.pagarut@mahkamahagung.go.id',
                'supervisor_gender' => 'L',
            ],
            'MEDIA ISTIQOMAH' => [
                'name' => 'MEDIA ISTIQOMAH',
                'address' => 'Jl. Taman Citarum, Citarum, Kec. Bandung Wetan, Kota Bandung',
                'contact_person' => 'Studio Director Media Istiqomah',
                'phone' => '022-4201234',
                'latitude' => -6.90380000,
                'longitude' => 107.61860000,
                'supervisor_name' => 'M. Ridwan Fauzi, S.Sn.',
                'supervisor_email' => 'spv.istiqomah@mediaistiqomah.id',
                'supervisor_gender' => 'L',
            ],
            'TVRI JAWA BARAT' => [
                'name' => 'TVRI JAWA BARAT',
                'address' => 'Jl. Cibaduyut Raya No. 269, Cibaduyut Wetan, Kecamatan Bojongloa Kidul, Kota Bandung, Jawa Barat 40236',
                'contact_person' => 'Koordinator Bidang Program & Siaran TVRI Jabar',
                'phone' => '022-5402269',
                'latitude' => -6.95350000,
                'longitude' => 107.59250000,
                'supervisor_name' => 'Dra. Endang Sulastri',
                'supervisor_email' => 'spv.tvrijabar@tvri.go.id',
                'supervisor_gender' => 'P',
            ],
            'ZBX PRODUCTION' => [
                'name' => 'ZBX PRODUCTION',
                'address' => 'Jl. Gegerkalong Girang No.57, Gegerkalong, Kec. Sukasari, Kota Bandung, Jawa Barat 40153',
                'contact_person' => 'Head of Creative ZBX Production',
                'phone' => '022-2005701',
                'latitude' => -6.86250000,
                'longitude' => 107.58900000,
                'supervisor_name' => 'Zulfikar Bachtiar, M.Sn.',
                'supervisor_email' => 'spv.zbx@zbxpro.com',
                'supervisor_gender' => 'L',
            ],
            'AKTV' => [
                'name' => 'AKTV',
                'address' => 'Ruko Cimahi Square, Jl. Pabrik Aci Blok B No.3-5, Cimahi Tengah, Kota Cimahi, Jawa Barat 40525',
                'contact_person' => 'Station Manager AKTV Cimahi',
                'phone' => '022-6651234',
                'latitude' => -6.87700000,
                'longitude' => 107.54350000,
                'supervisor_name' => 'Yuda Pratama, S.I.Kom.',
                'supervisor_email' => 'spv.aktv@aktv.co.id',
                'supervisor_gender' => 'L',
            ],
            'PT. ATAP PROMOTIONS' => [
                'name' => 'PT. ATAP PROMOTIONS',
                'address' => 'Jl. Reog No. 57, Turangga, Kec. Lengkong, Kota Bandung, Jawa Barat 40264',
                'contact_person' => 'Executive Producer Atap Promotions',
                'phone' => '022-7305757',
                'latitude' => -6.93600000,
                'longitude' => 107.62500000,
                'supervisor_name' => 'Gilang Purnama, S.Sn.',
                'supervisor_email' => 'spv.atappromotions@atappro.id',
                'supervisor_gender' => 'L',
            ],
            'GARUDA TV' => [
                'name' => 'GARUDA TV',
                'address' => 'Ruko M-Square, Jalan Cibaduyut Nomor 142, Kecamatan Bojongloa Kidul, Kota Bandung, Jawa Barat',
                'contact_person' => 'Biro Penyiaran Garuda TV Jawa Barat',
                'phone' => '022-5401142',
                'latitude' => -6.95000000,
                'longitude' => 107.59300000,
                'supervisor_name' => 'Taufik Hidayat, S.I.Kom.',
                'supervisor_email' => 'spv.garudatv@garudatv.co.id',
                'supervisor_gender' => 'L',
            ],
            'LEMHANNAS RI' => [
                'name' => 'LEMHANNAS RI',
                'address' => 'Jl. Medan Merdeka Sel. No.10, RT.11/RW.2, Gambir, Kota Jakarta Pusat, DKI Jakarta 10110',
                'contact_person' => 'Biro Humas & Publikasi Media Kedeputian Lemhannas RI',
                'phone' => '021-3832100',
                'latitude' => -6.18100000,
                'longitude' => 106.82800000,
                'supervisor_name' => 'Kolonel Inf. Wahyu Wibowo, M.Han.',
                'supervisor_email' => 'spv.lemhannas@lemhannas.go.id',
                'supervisor_gender' => 'L',
            ],
        ];

        $seededCompanies = [];
        $seededSupervisors = [];

        foreach ($companiesData as $key => $data) {
            $company = Company::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $data['name'],
                ],
                [
                    'address' => $data['address'],
                    'contact_person' => $data['contact_person'],
                    'phone' => $data['phone'],
                    'gps_enabled' => true,
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'radius_meters' => 150,
                    'override_hours' => false,
                    'work_days' => '5_days',
                ]
            );

            $company->majors()->syncWithoutDetaching([$majorPspt->id]);

            $supervisorUser = User::firstOrCreate(
                ['email' => $data['supervisor_email']],
                [
                    'school_id' => $school->id,
                    'name' => $data['supervisor_name'],
                    'password' => $passwordHash,
                    'role' => 'pembimbing-dudi',
                    'is_active' => true,
                ]
            );

            Profile::firstOrCreate(
                ['user_id' => $supervisorUser->id],
                [
                    'gender' => $data['supervisor_gender'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                ]
            );

            $supervisor = Supervisor::firstOrCreate(
                ['user_id' => $supervisorUser->id],
                [
                    'company_id' => $company->id,
                    'position' => 'Pembimbing Industri Lapangan',
                ]
            );

            $seededCompanies[$key] = $company;
            $seededSupervisors[$key] = $supervisor;
        }

        // 6. Data 68 Siswa Jurusan PSPT (65 Terplotting + 3 Belum Terplotting) sesuai data live
        $studentsData = [
            // XII PSPT 1 (36 siswa)
            ['nis' => '242510610', 'nisn' => '0097422833', 'name' => 'Abrar Jatiprawira', 'class' => 'XII PSPT 1', 'company' => 'TVRI JAWA BARAT', 'teacher' => 'FITRIES', 'gender' => 'L', 'phone' => '083840023155'],
            ['nis' => '242510611', 'nisn' => '0076605074', 'name' => 'Ade Alma', 'class' => 'XII PSPT 1', 'company' => 'MEDIA ISTIQOMAH', 'teacher' => 'FITRIES', 'gender' => 'P', 'phone' => '081395510801'],
            ['nis' => '242510612', 'nisn' => '0085780230', 'name' => 'Agni Sri Nur Aulia', 'class' => 'XII PSPT 1', 'company' => 'WARTA MUDA', 'teacher' => 'FITRIES', 'gender' => 'P', 'phone' => '0881023628735'],
            ['nis' => '242510613', 'nisn' => '3085164291', 'name' => 'Alif Nur Qolbi Ramdani', 'class' => 'XII PSPT 1', 'company' => 'MEDIA ISTIQOMAH', 'teacher' => 'RIDWAN_SD', 'gender' => 'L', 'phone' => '083143048941'],
            ['nis' => '242510614', 'nisn' => '0095767692', 'name' => 'Bunga Dwi Rahayu', 'class' => 'XII PSPT 1', 'company' => 'MEDIA ISTIQOMAH', 'teacher' => 'RIDWAN_SD', 'gender' => 'P', 'phone' => '085865100263'],
            ['nis' => '242510615', 'nisn' => '0085983495', 'name' => 'Cecep Supriadi', 'class' => 'XII PSPT 1', 'company' => 'ZBX PRODUCTION', 'teacher' => 'RIDWAN_SD', 'gender' => 'L', 'phone' => '089524289245'],
            ['nis' => '242510616', 'nisn' => '0093429168', 'name' => 'Dila Agustina', 'class' => 'XII PSPT 1', 'company' => 'HARIAN GARUT NEWS', 'teacher' => 'LUTHFI', 'gender' => 'P', 'phone' => '0895704295656'],
            ['nis' => '242510617', 'nisn' => '0088031303', 'name' => 'Faisal', 'class' => 'XII PSPT 1', 'company' => 'HARIAN GARUT NEWS', 'teacher' => 'LUTHFI', 'gender' => 'L', 'phone' => '083184013893'],
            ['nis' => '242510618', 'nisn' => '0094683796', 'name' => "Firda Syifaush Sa'diyah", 'class' => 'XII PSPT 1', 'company' => 'RADAR GARUT', 'teacher' => 'LUTHFI', 'gender' => 'P', 'phone' => '081386654304'],
            ['nis' => '242510619', 'nisn' => '0095850603', 'name' => 'Haira Rahadatul Aisy', 'class' => 'XII PSPT 1', 'company' => 'GARUT 60 DETIK', 'teacher' => 'ALIN', 'gender' => 'P', 'phone' => '083840630385'],
            ['nis' => '242510620', 'nisn' => '0099435982', 'name' => 'Herlina', 'class' => 'XII PSPT 1', 'company' => 'RADAR GARUT', 'teacher' => 'ALIN', 'gender' => 'P', 'phone' => '085624847731'],
            ['nis' => '242510621', 'nisn' => '0091357518', 'name' => 'Inekeu Juliana', 'class' => 'XII PSPT 1', 'company' => 'RADAR GARUT', 'teacher' => 'ALIN', 'gender' => 'P', 'phone' => '087758328830'],
            ['nis' => '242510622', 'nisn' => '0083171393', 'name' => 'Jeanet Treyda', 'class' => 'XII PSPT 1', 'company' => 'WARTA MUDA', 'teacher' => 'IKBAL', 'gender' => 'P', 'phone' => null],
            ['nis' => '242510623', 'nisn' => '0095346595', 'name' => 'Keysa Nur Holifah', 'class' => 'XII PSPT 1', 'company' => 'WARTA MUDA', 'teacher' => 'IKBAL', 'gender' => 'P', 'phone' => null],
            ['nis' => '242510624', 'nisn' => '0083341383', 'name' => 'M. Ridho Mauludin', 'class' => 'XII PSPT 1', 'company' => 'INFO GARUT', 'teacher' => 'IKBAL', 'gender' => 'L', 'phone' => '089674755909'],
            ['nis' => '242510625', 'nisn' => '0084368487', 'name' => 'Mochammad Nabil Dwi Pranata', 'class' => 'XII PSPT 1', 'company' => 'PT. ATAP PROMOTIONS', 'teacher' => 'ANA', 'gender' => 'L', 'phone' => '089674755909'],
            ['nis' => '242510626', 'nisn' => '0086700597', 'name' => 'Muhamad Dika Kurnia', 'class' => 'XII PSPT 1', 'company' => 'TVRI JAWA BARAT', 'teacher' => 'ANA', 'gender' => 'L', 'phone' => '088286267795'],
            ['nis' => '242510627', 'nisn' => '0095587499', 'name' => 'Muhamad Rafi Azzura', 'class' => 'XII PSPT 1', 'company' => 'INFO GARUT', 'teacher' => 'ANA', 'gender' => 'L', 'phone' => '083153988502'],
            ['nis' => '242510628', 'nisn' => '3098098366', 'name' => 'Muhamad Zildan Juliano Garnalis', 'class' => 'XII PSPT 1', 'company' => 'ZBX PRODUCTION', 'teacher' => 'RINI', 'gender' => 'L', 'phone' => '089531655670'],
            ['nis' => '242510630', 'nisn' => '0093438316', 'name' => 'Muhammad Ikbal Munfarid', 'class' => 'XII PSPT 1', 'company' => 'ZBX PRODUCTION', 'teacher' => 'RINI', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510629', 'nisn' => '0084700252', 'name' => 'Muhammad Rizki Subagja', 'class' => 'XII PSPT 1', 'company' => 'HARIAN GARUT NEWS', 'teacher' => 'CITRA', 'gender' => 'L', 'phone' => '0895428870709'],
            ['nis' => '242510631', 'nisn' => '0087009215', 'name' => 'Neng Sukma Dewi Ramadhani', 'class' => 'XII PSPT 1', 'company' => 'WARTA MUDA', 'teacher' => 'CITRA', 'gender' => 'P', 'phone' => null],
            ['nis' => '242510632', 'nisn' => '0098681026', 'name' => 'Putri Mardiyati Sofiyanti', 'class' => 'XII PSPT 1', 'company' => 'MEDIA ISTIQOMAH', 'teacher' => 'UTAMI', 'gender' => 'P', 'phone' => '082122747467'],
            ['nis' => '242510633', 'nisn' => '0098879250', 'name' => 'Rangga Amirudin Firdaus', 'class' => 'XII PSPT 1', 'company' => 'PT. ATAP PROMOTIONS', 'teacher' => 'UTAMI', 'gender' => 'L', 'phone' => '087812689879'],
            ['nis' => '242510634', 'nisn' => '0082776874', 'name' => 'Revan Rizqi Ramadhan', 'class' => 'XII PSPT 1', 'company' => 'ZBX PRODUCTION', 'teacher' => 'GATI', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510635', 'nisn' => '0091780452', 'name' => 'Revina Herdiani', 'class' => 'XII PSPT 1', 'company' => 'HARIAN GARUT NEWS', 'teacher' => 'GATI', 'gender' => 'P', 'phone' => '0895412049031'],
            ['nis' => '242510636', 'nisn' => '0095292556', 'name' => 'Rezky Adithya Saputra', 'class' => 'XII PSPT 1', 'company' => 'ZBX PRODUCTION', 'teacher' => 'ERWIN', 'gender' => 'L', 'phone' => '08997706126'],
            ['nis' => '242510637', 'nisn' => '0092459833', 'name' => 'Ridho Satria', 'class' => 'XII PSPT 1', 'company' => 'PT. ATAP PROMOTIONS', 'teacher' => 'ERWIN', 'gender' => 'L', 'phone' => '088222379176'],
            ['nis' => '242510638', 'nisn' => '0083625004', 'name' => 'Rifan Pratama', 'class' => 'XII PSPT 1', 'company' => 'ZBX PRODUCTION', 'teacher' => 'AAN', 'gender' => 'L', 'phone' => '089515083957'],
            ['nis' => '242510639', 'nisn' => '0097001560', 'name' => 'Rizky Nugraha', 'class' => 'XII PSPT 1', 'company' => 'DISDAMKAR KAB GARUT', 'teacher' => 'AAN', 'gender' => 'L', 'phone' => '085188443539'],
            ['nis' => '242510640', 'nisn' => '0096984771', 'name' => 'Santi', 'class' => 'XII PSPT 1', 'company' => 'ABUL YATAMA', 'teacher' => 'ASEP', 'gender' => 'P', 'phone' => '088902356237'],
            ['nis' => '242510641', 'nisn' => '0081413770', 'name' => 'Satria Ari Putra', 'class' => 'XII PSPT 1', 'company' => 'ZBX PRODUCTION', 'teacher' => 'ASEP', 'gender' => 'L', 'phone' => '08988157639'],
            ['nis' => '242510642', 'nisn' => '0097220915', 'name' => 'Sintia Ramadani', 'class' => 'XII PSPT 1', 'company' => 'RADAR GARUT', 'teacher' => 'ASEP', 'gender' => 'P', 'phone' => '083897738512'],
            ['nis' => '242510643', 'nisn' => null, 'name' => 'Syahrul Maulana Firdaus', 'class' => 'XII PSPT 1', 'company' => null, 'teacher' => 'NITA', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510644', 'nisn' => null, 'name' => 'Tiara Nur Annisa', 'class' => 'XII PSPT 1', 'company' => null, 'teacher' => 'NITA', 'gender' => 'P', 'phone' => null],
            ['nis' => '242510645', 'nisn' => '0086089803', 'name' => 'Zazkia Dwiyanti', 'class' => 'XII PSPT 1', 'company' => 'WARTA MUDA', 'teacher' => 'NITA', 'gender' => 'P', 'phone' => null],

            // XII PSPT 2 (32 siswa)
            ['nis' => '242510646', 'nisn' => '0084876255', 'name' => 'Alfizar Moch', 'class' => 'XII PSPT 2', 'company' => 'PT. AKSA GROUP CORP', 'teacher' => 'FITRIES', 'gender' => 'L', 'phone' => '083141750325'],
            ['nis' => '242510647', 'nisn' => '0089322266', 'name' => 'Amira Dwi Lestari', 'class' => 'XII PSPT 2', 'company' => 'AKTV', 'teacher' => 'FITRIES', 'gender' => 'P', 'phone' => null],
            ['nis' => '242510648', 'nisn' => '0098775207', 'name' => 'Arya Daffa Nugraha', 'class' => 'XII PSPT 2', 'company' => 'RADAR GARUT', 'teacher' => 'RIDWAN_SD', 'gender' => 'L', 'phone' => '083151008092'],
            ['nis' => '242510649', 'nisn' => '0086382589', 'name' => 'Azka Rahman', 'class' => 'XII PSPT 2', 'company' => 'HUMAS POLRES GARUT', 'teacher' => 'RIDWAN_SD', 'gender' => 'L', 'phone' => '08888241284'],
            ['nis' => '242510650', 'nisn' => '0099286043', 'name' => 'Azkia Sella Agustiani', 'class' => 'XII PSPT 2', 'company' => 'PT. AKSA GROUP CORP', 'teacher' => 'LUTHFI', 'gender' => 'P', 'phone' => '089531624846'],
            ['nis' => '242510651', 'nisn' => '0091303498', 'name' => 'Azkya M Khoerunissa', 'class' => 'XII PSPT 2', 'company' => 'MEDIA ISTIQOMAH', 'teacher' => 'LUTHFI', 'gender' => 'P', 'phone' => '085724018373'],
            ['nis' => '242510652', 'nisn' => '0081938487', 'name' => 'Azmi Maulana', 'class' => 'XII PSPT 2', 'company' => 'GARUT 60 DETIK', 'teacher' => 'ALIN', 'gender' => 'L', 'phone' => '081387166893'],
            ['nis' => '242510653', 'nisn' => '0096954573', 'name' => 'Danela Sri Rahayu', 'class' => 'XII PSPT 2', 'company' => 'DISKOMINFO KAB. GARUT', 'teacher' => 'ALIN', 'gender' => 'P', 'phone' => '082219192992'],
            ['nis' => '242510654', 'nisn' => '0084059744', 'name' => 'Delia Nugraha Putri', 'class' => 'XII PSPT 2', 'company' => 'DISKOMINFO KAB. GARUT', 'teacher' => 'IKBAL', 'gender' => 'P', 'phone' => null],
            ['nis' => '242510655', 'nisn' => '0098822719', 'name' => 'Devan Huzaini Akbar', 'class' => 'XII PSPT 2', 'company' => 'HUMAS POLRES GARUT', 'teacher' => 'IKBAL', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510656', 'nisn' => '0095830249', 'name' => 'Faris Rashif Fatan', 'class' => 'XII PSPT 2', 'company' => 'GARUDA TV', 'teacher' => 'ANA', 'gender' => 'L', 'phone' => '089510326980'],
            ['nis' => '242510657', 'nisn' => '0095172259', 'name' => 'Gizka Aprillia Putri', 'class' => 'XII PSPT 2', 'company' => 'INFO GARUT', 'teacher' => 'ANA', 'gender' => 'P', 'phone' => null],
            ['nis' => '242510658', 'nisn' => '0096948835', 'name' => 'Hasna Ayu Alifa', 'class' => 'XII PSPT 2', 'company' => 'PENGADILAN AGAMA KAB GARUT', 'teacher' => 'RINI', 'gender' => 'P', 'phone' => '083831135202'],
            ['nis' => '242510659', 'nisn' => '0098631443', 'name' => 'Kania Putri R', 'class' => 'XII PSPT 2', 'company' => 'RADAR GARUT', 'teacher' => 'RINI', 'gender' => 'P', 'phone' => null],
            ['nis' => '242510660', 'nisn' => '0092568949', 'name' => 'Keysa Nuraqilah', 'class' => 'XII PSPT 2', 'company' => 'GARUT 60 DETIK', 'teacher' => 'CITRA', 'gender' => 'P', 'phone' => null],
            ['nis' => '242510661', 'nisn' => null, 'name' => 'M Revan Rizdan Rindzani', 'class' => 'XII PSPT 2', 'company' => null, 'teacher' => 'CITRA', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510663', 'nisn' => '3088869669', 'name' => 'Mohammad Irpan', 'class' => 'XII PSPT 2', 'company' => 'GARUT 60 DETIK', 'teacher' => 'UTAMI', 'gender' => 'L', 'phone' => '083865085847'],
            ['nis' => '242510664', 'nisn' => '0086881573', 'name' => 'Muhamad Janwar', 'class' => 'XII PSPT 2', 'company' => 'RADAR GARUT', 'teacher' => 'UTAMI', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510665', 'nisn' => '0095228931', 'name' => "Muhamad Radiansyah Nur'hakim", 'class' => 'XII PSPT 2', 'company' => 'HUMAS POLRES GARUT', 'teacher' => 'MIRA', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510666', 'nisn' => '0095740388', 'name' => 'Muhammad Bintang Muharram', 'class' => 'XII PSPT 2', 'company' => 'HUMAS POLRES GARUT', 'teacher' => 'MIRA', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510667', 'nisn' => '0092258515', 'name' => 'Muhammad Ramdhan', 'class' => 'XII PSPT 2', 'company' => 'GARUDA TV', 'teacher' => 'NITA', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510668', 'nisn' => '0088772323', 'name' => 'Nabila Nurfitriah Rahmah', 'class' => 'XII PSPT 2', 'company' => 'MEDIA ISTIQOMAH', 'teacher' => 'NITA', 'gender' => 'P', 'phone' => '089510612984'],
            ['nis' => '242510671', 'nisn' => '0082462499', 'name' => 'Rahel Amanda', 'class' => 'XII PSPT 2', 'company' => 'PT. AKSA GROUP CORP', 'teacher' => 'NITA', 'gender' => 'P', 'phone' => '085772414361'],
            ['nis' => '242510672', 'nisn' => '0092609961', 'name' => 'Restu Latif Saeful Islam', 'class' => 'XII PSPT 2', 'company' => 'GARUT 60 DETIK', 'teacher' => 'NURWITA', 'gender' => 'L', 'phone' => '085721773548'],
            ['nis' => '242510673', 'nisn' => '0096575432', 'name' => 'Revan Rinaldi Firdaus', 'class' => 'XII PSPT 2', 'company' => 'GARUDA TV', 'teacher' => 'NURWITA', 'gender' => 'L', 'phone' => null],
            ['nis' => '242510674', 'nisn' => '0089186116', 'name' => 'Risti Aprilianti', 'class' => 'XII PSPT 2', 'company' => 'DISKOMINFO KAB. GARUT', 'teacher' => 'GATI', 'gender' => 'P', 'phone' => '08986914646'],
            ['nis' => '242510675', 'nisn' => '0086894608', 'name' => 'Riza Bustan Fauzi', 'class' => 'XII PSPT 2', 'company' => 'LEMHANNAS RI', 'teacher' => 'GATI', 'gender' => 'L', 'phone' => '087831911164'],
            ['nis' => '242510676', 'nisn' => '0081030031', 'name' => 'Shela Amelia', 'class' => 'XII PSPT 2', 'company' => 'GARUT 60 DETIK', 'teacher' => 'ASEP', 'gender' => 'P', 'phone' => null],
            ['nis' => '242510677', 'nisn' => '0095575494', 'name' => 'Sinta', 'class' => 'XII PSPT 2', 'company' => 'ABUL YATAMA', 'teacher' => 'ASEP', 'gender' => 'P', 'phone' => '081315777981'],
            ['nis' => '242510678', 'nisn' => '0086492262', 'name' => 'Steny Oktavano Perkasa', 'class' => 'XII PSPT 2', 'company' => 'RADAR GARUT', 'teacher' => 'ASEP', 'gender' => 'L', 'phone' => '082119449123'],
            ['nis' => '242510679', 'nisn' => '0087591539', 'name' => 'Tala Muhammad Wafid', 'class' => 'XII PSPT 2', 'company' => 'INFO GARUT', 'teacher' => 'AHMAD_YANI', 'gender' => 'L', 'phone' => '082247741758'],
            ['nis' => '242510681', 'nisn' => '0091105374', 'name' => 'Zakiyah Wanisa Al Mubarok', 'class' => 'XII PSPT 2', 'company' => 'AKTV', 'teacher' => 'AHMAD_YANI', 'gender' => 'P', 'phone' => '085938384139'],
        ];

        $studentCount = 0;
        $plottedCount = 0;

        foreach ($studentsData as $st) {
            $class = ($st['class'] === 'XII PSPT 1') ? $classPspt1 : $classPspt2;
            $assignedTeacher = $seededTeachers[$st['teacher']] ?? null;
            $email = 'siswa.' . $st['nis'] . '@smkn2garut.sch.id';

            // Cocokkan siswa yang mungkin sudah ada di DB live (berdasarkan NIS, NISN, atau email)
            $existingStudent = Student::whereHas('user', fn ($q) => $q->where('school_id', $school->id))
                ->where(function ($q) use ($st) {
                    $q->where('nis', $st['nis']);
                    if (!empty($st['nisn'])) {
                        $q->orWhere('nisn', $st['nisn']);
                    }
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
            $effectiveNisn = $st['nisn'] ?: ($student?->nisn ?: $st['nis']);

            if ($student) {
                $student->update([
                    'class_id' => $class->id,
                    'nis' => $st['nis'],
                    'nisn' => $effectiveNisn,
                    'is_parent_portal_active' => true,
                ]);
            } else {
                $student = Student::create([
                    'user_id' => $studentUser->id,
                    'class_id' => $class->id,
                    'nis' => $st['nis'],
                    'nisn' => $effectiveNisn,
                    'parent_token' => Str::random(32),
                    'is_parent_portal_active' => true,
                ]);
            }

            $studentCount++;

            if (!empty($st['company']) && $assignedTeacher) {
                $company = $seededCompanies[$st['company']] ?? null;
                $supervisor = $seededSupervisors[$st['company']] ?? null;

                if ($company && $supervisor) {
                    StudentPkl::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'pkl_period_id' => $period->id,
                        ],
                        [
                            'school_id' => $school->id,
                            'company_id' => $company->id,
                            'teacher_id' => $assignedTeacher->id,
                            'supervisor_id' => $supervisor->id,
                            'status' => 'active',
                        ]
                    );
                    $plottedCount++;
                }
            }
        }

        if (isset($this->command)) {
            $this->command->info("✅ [PsptPklSeeder] Berhasil menyinkronkan data PSPT SMKN 2 Garut:");
            $this->command->info("   - Jurusan: {$majorPspt->name} ({$majorPspt->code})");
            $this->command->info("   - Kelas: XII PSPT 1 (Wali: Asep Suhendra) & XII PSPT 2 (Wali: Ridwan Setiawan Darajat)");
            $this->command->info("   - Guru Pembimbing & Wali Kelas: " . count($seededTeachers) . " guru");
            $this->command->info("   - Total Instansi / DU/DI: " . count($seededCompanies) . " perusahaan");
            $this->command->info("   - Total Siswa PSPT: {$studentCount} siswa ({$plottedCount} terplotting aktif)");
        }
    }
}
