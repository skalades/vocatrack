<?php

namespace Database\Seeders;

use App\Models\Company;
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
     * Jalankan seeder modular untuk data Penempatan Siswa PKL Jurusan PSPT.
     * Mencakup:
     * 1. Jurusan & Kelas PSPT (XII PSPT 1 & XII PSPT 2)
     * 2. Guru Pembimbing Kejuruan PSPT
     * 3. 18 Instansi / DUDI (Garut, Bandung, Cimahi, DKI Jakarta) beserta Pembimbing DUDI
     * 4. 65 Siswa Lengkap (User, Profile, Student NIS/NISN, & Plotting PKL Aktif)
     */
    public function run(): void
    {
        // 1. Dapatkan referensi Sekolah SMKN 2 Garut
        $school = School::where('npsn', '20209203')->first() ?? School::firstOrFail();

        // 2. Buat atau dapatkan Jurusan PSPT (Produksi dan Siaran Program Televisi)
        $majorPspt = Major::firstOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'PSPT',
            ],
            [
                'name' => 'Produksi dan Siaran Program Televisi',
            ]
        );

        // 3. Buat atau dapatkan Kelas XII PSPT 1 & XII PSPT 2
        $classPspt1 = SchoolClass::firstOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'XII PSPT 1',
            ],
            [
                'major_id' => $majorPspt->id,
                'grade_level' => 'XII',
                'academic_year' => '2026/2027',
            ]
        );

        $classPspt2 = SchoolClass::firstOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'XII PSPT 2',
            ],
            [
                'major_id' => $majorPspt->id,
                'grade_level' => 'XII',
                'academic_year' => '2026/2027',
            ]
        );

        // 4. Guru Pembimbing Kejuruan PSPT
        $teacherUser1 = User::firstOrCreate(
            ['email' => 'guru.pspt1@smkn2garut.sch.id'],
            [
                'school_id' => $school->id,
                'name' => 'Drs. Dadang Sudrajat, M.M.',
                'password' => Hash::make('password'),
                'role' => 'guru-pembimbing',
                'is_active' => true,
            ]
        );
        Profile::firstOrCreate(
            ['user_id' => $teacherUser1->id],
            [
                'gender' => 'L',
                'phone' => '081223450001',
                'address' => 'Tarogong Kaler, Kabupaten Garut',
            ]
        );
        $teacher1 = Teacher::firstOrCreate(
            ['user_id' => $teacherUser1->id],
            ['nip' => '198504122009021004']
        );

        $teacherUser2 = User::firstOrCreate(
            ['email' => 'guru.pspt2@smkn2garut.sch.id'],
            [
                'school_id' => $school->id,
                'name' => 'Rina Marlina, S.Sn.',
                'password' => Hash::make('password'),
                'role' => 'guru-pembimbing',
                'is_active' => true,
            ]
        );
        Profile::firstOrCreate(
            ['user_id' => $teacherUser2->id],
            [
                'gender' => 'P',
                'phone' => '081223450002',
                'address' => 'Tarogong Kidul, Kabupaten Garut',
            ]
        );
        $teacher2 = Teacher::firstOrCreate(
            ['user_id' => $teacherUser2->id],
            ['nip' => '198906232014032002']
        );

        // 5. Dapatkan atau buat Periode PKL aktif
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

        // 6. Data 18 DUDI / Instansi Penempatan PKL PSPT
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
            'DISKOMINFO GARUT' => [
                'name' => 'DISKOMINFO GARUT',
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
            'KEJAKSAAN AGUNG GARUT' => [
                'name' => 'KEJAKSAAN AGUNG GARUT',
                'address' => 'Jl. Merdeka No.222, Haurpanggung, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Kasi Intelijen & Penerangan Hukum Kejaksaan',
                'phone' => '0262-231222',
                'latitude' => -7.21350000,
                'longitude' => 107.90450000,
                'supervisor_name' => 'Bayu Wicaksono, S.H.',
                'supervisor_email' => 'spv.kejari@kejaksaan.go.id',
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
            'ZBX PRODUCTIONS' => [
                'name' => 'ZBX PRODUCTIONS',
                'address' => 'Jl. Gegerkalong Girang No.57, Gegerkalong, Kec. Sukasari, Kota Bandung, Jawa Barat 40153',
                'contact_person' => 'Head of Creative ZBX Productions',
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
            'ATAP PROMOTIONS' => [
                'name' => 'ATAP PROMOTIONS',
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

            // Kaitkan relasi company dengan Major PSPT
            $company->majors()->syncWithoutDetaching([$majorPspt->id]);

            // Buat akun User untuk Pembimbing DUDI
            $supervisorUser = User::firstOrCreate(
                ['email' => $data['supervisor_email']],
                [
                    'school_id' => $school->id,
                    'name' => $data['supervisor_name'],
                    'password' => Hash::make('password'),
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

        // 7. Data 65 Siswa Jurusan PSPT & Penempatan PKL
        $studentsData = [
            // HARIAN GARUT NEWS (4 siswa)
            ['company' => 'HARIAN GARUT NEWS', 'nis' => '242510616', 'nisn' => '0093429168', 'name' => 'Dila Agustina', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'HARIAN GARUT NEWS', 'nis' => '242510617', 'nisn' => '0088031303', 'name' => 'Faisal', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'HARIAN GARUT NEWS', 'nis' => '242510629', 'nisn' => '0084700252', 'name' => 'Muhammad Rizki Subagja', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'HARIAN GARUT NEWS', 'nis' => '242510635', 'nisn' => '0091780452', 'name' => 'Revina Herdiani', 'class' => 'XII PSPT 1', 'gender' => 'P'],

            // GARUT 60 DETIK (6 siswa)
            ['company' => 'GARUT 60 DETIK', 'nis' => '242510619', 'nisn' => '0095850603', 'name' => 'Haira Rahadatul Aisy', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'GARUT 60 DETIK', 'nis' => '242510652', 'nisn' => '0081938487', 'name' => 'Azmi Maulana', 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'GARUT 60 DETIK', 'nis' => '242510660', 'nisn' => '0092568949', 'name' => 'Keysa Nuraqilah', 'class' => 'XII PSPT 2', 'gender' => 'P'],
            ['company' => 'GARUT 60 DETIK', 'nis' => '242510663', 'nisn' => '3088869669', 'name' => 'Mohammad Irpan', 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'GARUT 60 DETIK', 'nis' => '242510672', 'nisn' => '0092609961', 'name' => 'Restu Latif Saeful Islam', 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'GARUT 60 DETIK', 'nis' => '242510676', 'nisn' => '0081030031', 'name' => 'Shela Amelia', 'class' => 'XII PSPT 2', 'gender' => 'P'],

            // RADAR GARUT (8 siswa)
            ['company' => 'RADAR GARUT', 'nis' => '242510618', 'nisn' => '0094683796', 'name' => "Firda Syifaush Sa'diyah", 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'RADAR GARUT', 'nis' => '242510620', 'nisn' => '0099435982', 'name' => 'Herlina', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'RADAR GARUT', 'nis' => '242510621', 'nisn' => '0091357518', 'name' => 'Inekeu Juliana', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'RADAR GARUT', 'nis' => '242510642', 'nisn' => '0097220915', 'name' => 'Sintia Ramadani', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'RADAR GARUT', 'nis' => '242510648', 'nisn' => '0098775207', 'name' => 'Arya Daffa Nugraha', 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'RADAR GARUT', 'nis' => '242510659', 'nisn' => '0098631443', 'name' => 'Kania Putri R', 'class' => 'XII PSPT 2', 'gender' => 'P'],
            ['company' => 'RADAR GARUT', 'nis' => '242510664', 'nisn' => '0086881573', 'name' => 'Muhamad Janwar', 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'RADAR GARUT', 'nis' => '242510678', 'nisn' => '0086492262', 'name' => 'Steny Oktavano Perkasa', 'class' => 'XII PSPT 2', 'gender' => 'L'],

            // INFO GARUT (4 siswa)
            ['company' => 'INFO GARUT', 'nis' => '242510627', 'nisn' => '0095587499', 'name' => 'Muhamad Rafi Azzura', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'INFO GARUT', 'nis' => '242510624', 'nisn' => '0083341383', 'name' => 'M. Ridho Mauludin', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'INFO GARUT', 'nis' => '242510657', 'nisn' => '0095172259', 'name' => 'Gizka Aprillia Putri', 'class' => 'XII PSPT 2', 'gender' => 'P'],
            ['company' => 'INFO GARUT', 'nis' => '242510679', 'nisn' => '0087591539', 'name' => 'Tala Muhammad Wafid', 'class' => 'XII PSPT 2', 'gender' => 'L'],

            // DISKOMINFO GARUT (3 siswa)
            ['company' => 'DISKOMINFO GARUT', 'nis' => '242510653', 'nisn' => '0096954573', 'name' => 'Danela Sri Rahayu', 'class' => 'XII PSPT 2', 'gender' => 'P'],
            ['company' => 'DISKOMINFO GARUT', 'nis' => '242510654', 'nisn' => '0084059744', 'name' => 'Delia Nugraha Putri', 'class' => 'XII PSPT 2', 'gender' => 'P'],
            ['company' => 'DISKOMINFO GARUT', 'nis' => '242510674', 'nisn' => '0089186116', 'name' => 'Risti Aprilianti', 'class' => 'XII PSPT 2', 'gender' => 'P'],

            // WARTA MUDA (5 siswa)
            ['company' => 'WARTA MUDA', 'nis' => '242510612', 'nisn' => '0085780230', 'name' => 'Agni Sri Nur Aulia', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'WARTA MUDA', 'nis' => '242510622', 'nisn' => '0083171393', 'name' => 'Jeanet Treyda', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'WARTA MUDA', 'nis' => '242510631', 'nisn' => '0087009215', 'name' => 'Neng Sukma Dewi Ramadhani', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'WARTA MUDA', 'nis' => '242510645', 'nisn' => '0086089803', 'name' => 'Zazkia Dwiyanti', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'WARTA MUDA', 'nis' => '242510623', 'nisn' => '0095346595', 'name' => 'Keysa Nur Holifah', 'class' => 'XII PSPT 1', 'gender' => 'P'],

            // HUMAS POLRES GARUT (4 siswa)
            ['company' => 'HUMAS POLRES GARUT', 'nis' => '242510649', 'nisn' => '0086382589', 'name' => 'Azka Rahman', 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'HUMAS POLRES GARUT', 'nis' => '242510666', 'nisn' => '0095740388', 'name' => 'Muhammad Bintang Muharram', 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'HUMAS POLRES GARUT', 'nis' => '242510665', 'nisn' => '0095228931', 'name' => "Muhamad Radiansyah Nur'hakim", 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'HUMAS POLRES GARUT', 'nis' => '242510655', 'nisn' => '0098822719', 'name' => 'Devan Huzaini Akbar', 'class' => 'XII PSPT 2', 'gender' => 'L'],

            // PT. AKSA GROUP CORP (3 siswa)
            ['company' => 'PT. AKSA GROUP CORP', 'nis' => '242510646', 'nisn' => '0084876255', 'name' => 'Alfizar Moch', 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'PT. AKSA GROUP CORP', 'nis' => '242510650', 'nisn' => '0099286043', 'name' => 'Azkia Sella Agustiani', 'class' => 'XII PSPT 2', 'gender' => 'P'],
            ['company' => 'PT. AKSA GROUP CORP', 'nis' => '242510671', 'nisn' => '0082462499', 'name' => 'Rahel Amanda', 'class' => 'XII PSPT 2', 'gender' => 'P'],

            // ABUL YATAMA (2 siswa)
            ['company' => 'ABUL YATAMA', 'nis' => '242510640', 'nisn' => '0096984771', 'name' => 'Santi', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'ABUL YATAMA', 'nis' => '242510677', 'nisn' => '0095575494', 'name' => 'Sinta', 'class' => 'XII PSPT 2', 'gender' => 'P'],

            // DISDAMKAR KAB GARUT (1 siswa)
            ['company' => 'DISDAMKAR KAB GARUT', 'nis' => '242510639', 'nisn' => '0097001560', 'name' => 'Rizky Nugraha', 'class' => 'XII PSPT 1', 'gender' => 'L'],

            // KEJAKSAAN AGUNG GARUT (1 siswa)
            ['company' => 'KEJAKSAAN AGUNG GARUT', 'nis' => '242510658', 'nisn' => '0096948835', 'name' => 'Hasna Ayu Alifa', 'class' => 'XII PSPT 2', 'gender' => 'P'],

            // MEDIA ISTIQOMAH (6 siswa)
            ['company' => 'MEDIA ISTIQOMAH', 'nis' => '242510614', 'nisn' => '0095767692', 'name' => 'Bunga Dwi Rahayu', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'MEDIA ISTIQOMAH', 'nis' => '242510613', 'nisn' => '3085164291', 'name' => 'Alif Nur Qolbi Ramdani', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'MEDIA ISTIQOMAH', 'nis' => '242510611', 'nisn' => '0076605074', 'name' => 'Ade Alma', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'MEDIA ISTIQOMAH', 'nis' => '242510632', 'nisn' => '0098681026', 'name' => 'Putri Mardiyati Sofiyanti', 'class' => 'XII PSPT 1', 'gender' => 'P'],
            ['company' => 'MEDIA ISTIQOMAH', 'nis' => '242510651', 'nisn' => '0091303498', 'name' => 'Azkya M Khoerunissa', 'class' => 'XII PSPT 2', 'gender' => 'P'],
            ['company' => 'MEDIA ISTIQOMAH', 'nis' => '242510668', 'nisn' => '0088772323', 'name' => 'Nabila Nurfitriah Rahmah', 'class' => 'XII PSPT 2', 'gender' => 'P'],

            // TVRI JAWA BARAT (2 siswa)
            ['company' => 'TVRI JAWA BARAT', 'nis' => '242510626', 'nisn' => '0086700597', 'name' => 'Muhamad Dika Kurnia', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'TVRI JAWA BARAT', 'nis' => '242510610', 'nisn' => '0097422833', 'name' => 'Abrar Jatiprawira', 'class' => 'XII PSPT 1', 'gender' => 'L'],

            // ZBX PRODUCTIONS (7 siswa)
            ['company' => 'ZBX PRODUCTIONS', 'nis' => '242510638', 'nisn' => '0083625004', 'name' => 'Rifan Pratama', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'ZBX PRODUCTIONS', 'nis' => '242510615', 'nisn' => '0085983495', 'name' => 'Cecep Supriadi', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'ZBX PRODUCTIONS', 'nis' => '242510634', 'nisn' => '0082776874', 'name' => 'Revan Rizqi Ramadhan', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'ZBX PRODUCTIONS', 'nis' => '242510636', 'nisn' => '0095292556', 'name' => 'Rezky Adithya Saputra', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'ZBX PRODUCTIONS', 'nis' => '242510630', 'nisn' => '0093438316', 'name' => 'Muhammad Ikbal Munfarid', 'class' => 'XII PSPT 1', 'gender' => 'L'], // NIS diisi sesuai urutan nomor registrasi
            ['company' => 'ZBX PRODUCTIONS', 'nis' => '242510628', 'nisn' => '3098098366', 'name' => 'Muhamad Zildan Juliano Garnalis', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'ZBX PRODUCTIONS', 'nis' => '242510641', 'nisn' => '0081413770', 'name' => 'Satria Ari Putra', 'class' => 'XII PSPT 1', 'gender' => 'L'],

            // AKTV (2 siswa)
            ['company' => 'AKTV', 'nis' => '242510647', 'nisn' => '0089322266', 'name' => 'Amira Dwi Lestari', 'class' => 'XII PSPT 2', 'gender' => 'P'],
            ['company' => 'AKTV', 'nis' => '242510681', 'nisn' => '0091105374', 'name' => 'Zakiyah Wanisa Al Mubarok', 'class' => 'XII PSPT 2', 'gender' => 'P'],

            // ATAP PROMOTIONS (3 siswa)
            ['company' => 'ATAP PROMOTIONS', 'nis' => '242510625', 'nisn' => '0084368487', 'name' => 'Mochammad Nabil Dwi Pranata', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'ATAP PROMOTIONS', 'nis' => '242510637', 'nisn' => '0092459833', 'name' => 'Ridho Satria', 'class' => 'XII PSPT 1', 'gender' => 'L'],
            ['company' => 'ATAP PROMOTIONS', 'nis' => '242510633', 'nisn' => '0098879250', 'name' => 'Rangga Amirudin Firdaus', 'class' => 'XII PSPT 1', 'gender' => 'L'],

            // GARUDA TV (3 siswa)
            ['company' => 'GARUDA TV', 'nis' => '242510667', 'nisn' => '0092258515', 'name' => 'Muhammad Ramdhan', 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'GARUDA TV', 'nis' => '242510673', 'nisn' => '0096575432', 'name' => 'Revan Rinaldi Firdaus', 'class' => 'XII PSPT 2', 'gender' => 'L'],
            ['company' => 'GARUDA TV', 'nis' => '242510656', 'nisn' => '0095830249', 'name' => 'Faris Rashif Fatan', 'class' => 'XII PSPT 2', 'gender' => 'L'],

            // LEMHANNAS RI (1 siswa)
            ['company' => 'LEMHANNAS RI', 'nis' => '242510675', 'nisn' => '0086894608', 'name' => 'Riza Bustan Fauzi', 'class' => 'XII PSPT 2', 'gender' => 'L'],
        ];

        $studentCount = 0;

        foreach ($studentsData as $st) {
            $company = $seededCompanies[$st['company']] ?? null;
            $supervisor = $seededSupervisors[$st['company']] ?? null;

            if (!$company || !$supervisor) {
                continue;
            }

            // Tentukan kelas dan guru pembimbing
            $isClass1 = ($st['class'] === 'XII PSPT 1');
            $class = $isClass1 ? $classPspt1 : $classPspt2;
            $assignedTeacher = $isClass1 ? $teacher1 : $teacher2;

            // Generate email terstandar: siswa.<nis>@smkn2garut.sch.id
            $email = 'siswa.' . $st['nis'] . '@smkn2garut.sch.id';

            // 1. Akun User Siswa
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

            // 2. Profil Siswa
            Profile::updateOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'gender' => $st['gender'],
                    'phone' => '08' . rand(11, 99) . rand(1000000, 9999999),
                    'address' => 'Kabupaten Garut, Jawa Barat',
                ]
            );

            // 3. Rekord Siswa (Student)
            $student = Student::updateOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'class_id' => $class->id,
                    'nis' => $st['nis'],
                    'nisn' => $st['nisn'],
                    'parent_token' => Str::random(32),
                    'is_parent_portal_active' => true,
                ]
            );

            // 4. Plotting PKL Siswa (StudentPkl)
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

            $studentCount++;
        }

        if (isset($this->command)) {
            $this->command->info("✅ [PsptPklSeeder] Berhasil men-seed data PSPT SMKN 2 Garut:");
            $this->command->info("   - Jurusan: {$majorPspt->name} ({$majorPspt->code})");
            $this->command->info("   - Kelas: XII PSPT 1 & XII PSPT 2");
            $this->command->info("   - Guru Pembimbing: {$teacherUser1->name} & {$teacherUser2->name}");
            $this->command->info("   - Total Instansi / DUDI: " . count($seededCompanies) . " perusahaan");
            $this->command->info("   - Total Siswa & Plotting PKL: {$studentCount} siswa aktif");
        }
    }
}
