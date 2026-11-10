<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalApproval;
use App\Models\JournalPhoto;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        $superAdmin = User::create([
            'name' => 'Super Administrator Platform',
            'email' => 'superadmin@vokatrack.id',
            'password' => Hash::make('password'),
            'role' => 'super-admin',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $superAdmin->id,
            'gender' => 'L',
            'phone' => '081234567890',
            'address' => 'Kantor Pusat VokaTrack Cloud Jakarta',
        ]);

        // 2. Sample School
        $school = School::create([
            'npsn' => '20104523',
            'name' => 'SMK Negeri 1 Teknologi Nusantara',
            'address' => 'Jl. Pendidikan Vokasi No. 12, Jakarta Pusat',
            'phone' => '021-7654321',
            'email' => 'info@smkn1tekno.sch.id',
            'logo_path' => null,
            'stamp_path' => null,
            'is_active' => true,
        ]);

        // School Settings
        $defaultSettings = [
            'checkin_start' => '06:00:00',
            'checkin_late' => '08:00:00',
            'checkin_alfa' => '11:00:00',
            'checkout_start' => '16:00:00',
            'journal_backdate_limit' => '3',
            'weight_dudi' => '60',
            'weight_guru' => '40',
            'kop_header_line1' => 'PEMERINTAH DAERAH KHUSUS IBUKOTA JAKARTA',
            'kop_header_line2' => 'DINAS PENDIDIKAN',
            'kop_header_line3' => 'SEKOLAH MENENGAH KEJURUAN NEGERI 1 TEKNOLOGI NUSANTARA',
            'kepsek_name' => 'Dr. H. Bambang Sudarsono, M.Pd.',
            'kepsek_nip' => '197003121995121001',
        ];

        foreach ($defaultSettings as $key => $val) {
            SchoolSetting::create([
                'school_id' => $school->id,
                'key' => $key,
                'value' => $val,
            ]);
        }

        // 3. Admin Sekolah
        $schoolAdmin = User::create([
            'school_id' => $school->id,
            'name' => 'Admin SMKN 1 Tekno',
            'email' => 'admin@smkn1tekno.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $schoolAdmin->id,
            'gender' => 'L',
            'phone' => '081234567891',
            'address' => 'Jakarta Pusat',
        ]);

        // 4. Kepala Sekolah
        $kepsek = User::create([
            'school_id' => $school->id,
            'name' => 'Dr. H. Bambang Sudarsono, M.Pd.',
            'email' => 'kepsek@smkn1tekno.sch.id',
            'password' => Hash::make('password'),
            'role' => 'kepala-sekolah',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $kepsek->id,
            'gender' => 'L',
            'phone' => '081234567892',
            'address' => 'Jakarta Selatan',
        ]);

        // 5. Guru Pembimbing
        $guru = User::create([
            'school_id' => $school->id,
            'name' => 'Siti Nurhaliza, S.Kom., M.T.',
            'email' => 'guru@smkn1tekno.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $guru->id,
            'gender' => 'P',
            'phone' => '081234567893',
            'address' => 'Jakarta Timur',
        ]);
        Teacher::create([
            'user_id' => $guru->id,
            'nip' => '198501152010012003',
        ]);

        // 6. Majors & Classes
        $majorRpl = Major::create([
            'school_id' => $school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
        ]);

        $majorTkj = Major::create([
            'school_id' => $school->id,
            'code' => 'TKJ',
            'name' => 'Teknik Komputer dan Jaringan',
        ]);

        $classRpl1 = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $majorRpl->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $classTkj1 = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $majorTkj->id,
            'name' => 'XII TKJ 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        // 7. DU/DI Company
        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Digital Inovasi Nusantara',
            'address' => 'Gedung Cyber 2 Lantai 15, Jl. H.R. Rasuna Said, Jakarta Selatan',
            'contact_person' => 'Hendro Wijaya, S.T.',
            'phone' => '021-52901234',
            'gps_enabled' => true,
            'latitude' => -6.22350000,
            'longitude' => 106.83100000,
            'radius_meters' => 150,
            'override_hours' => false,
            'work_days' => '5_days',
        ]);

        // Pembimbing DU/DI
        $pembimbingDudi = User::create([
            'school_id' => $school->id,
            'name' => 'Hendro Wijaya, S.T. (Senior Lead Dev)',
            'email' => 'supervisor@inovasidigital.com',
            'password' => Hash::make('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $pembimbingDudi->id,
            'gender' => 'L',
            'phone' => '081234567894',
            'address' => 'Jakarta Selatan',
        ]);
        $supervisor = Supervisor::create([
            'user_id' => $pembimbingDudi->id,
            'company_id' => $company->id,
            'position' => 'Senior Software Engineering Lead',
        ]);

        // 8. PKL Period
        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'PKL Industri Semester Genap 2026/2027',
            'start_date' => Carbon::now()->subMonths(1)->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->addMonths(4)->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        // 9. Siswa 1
        $siswaUser1 = User::create([
            'school_id' => $school->id,
            'name' => 'Ahmad Fauzi',
            'email' => 'siswa@smkn1tekno.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $siswaUser1->id,
            'gender' => 'L',
            'phone' => '081234567895',
            'address' => 'Jl. Kebon Jeruk No. 10, Jakarta Barat',
        ]);
        $student1 = Student::create([
            'user_id' => $siswaUser1->id,
            'class_id' => $classRpl1->id,
            'nis' => '202401',
            'nisn' => '0061234567',
        ]);

        // Siswa 2
        $siswaUser2 = User::create([
            'school_id' => $school->id,
            'name' => 'Budi Pratama Santoso',
            'email' => 'budi@smkn1tekno.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $siswaUser2->id,
            'gender' => 'L',
            'phone' => '081234567896',
            'address' => 'Jl. Tebet Raya No. 25, Jakarta Selatan',
        ]);
        $student2 = Student::create([
            'user_id' => $siswaUser2->id,
            'class_id' => $classRpl1->id,
            'nis' => '202402',
            'nisn' => '0061234568',
        ]);

        // 10. Student PKL Assignments
        $spkl1 = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student1->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $guru->teacher->id,
            'supervisor_id' => $supervisor->id,
            'status' => 'active',
        ]);

        $spkl2 = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student2->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $guru->teacher->id,
            'supervisor_id' => $supervisor->id,
            'status' => 'active',
        ]);

        // 11. Sample Previous Attendances for Ahmad Fauzi (spkl1)
        for ($i = 5; $i >= 1; $i--) {
            $pastDate = Carbon::today()->subDays($i);
            if ($pastDate->isWeekend()) {
                continue;
            }

            $status = ($i === 3) ? 'terlambat' : 'hadir';
            $checkinTime = ($status === 'terlambat') ? '08:24:15' : '07:35:10';

            Attendance::create([
                'school_id' => $school->id,
                'student_pkl_id' => $spkl1->id,
                'date' => $pastDate->toDateString(),
                'status' => $status,
                'checkin_time' => $checkinTime,
                'checkin_photo_path' => null,
                'checkin_lat' => -6.223512,
                'checkin_lng' => 106.831045,
                'is_within_radius' => true,
                'checkout_time' => '17:05:00',
                'checkout_notes' => 'Menyelesaikan modul frontend layout dashboard dan testing API authentication.',
            ]);

            // Sample Approved Journal for those days
            $journal = Journal::create([
                'school_id' => $school->id,
                'student_pkl_id' => $spkl1->id,
                'date' => $pastDate->toDateString(),
                'activity_description' => "Pada hari ini saya melakukan slicing komponen UI web responsive berbasis Tailwind CSS, implementasi validasi form login, dan pengujian integrasi REST API backend sesuai arahan mentor DU/DI.",
                'status' => 'approved',
            ]);

            JournalApproval::create([
                'journal_id' => $journal->id,
                'approver_id' => $pembimbingDudi->id,
                'role_level' => 'pembimbing-dudi',
                'status' => 'approved',
                'notes' => 'Pekerjaan rapi dan sesuai instruksi sprint.',
                'created_at' => $pastDate->copy()->setTime(17, 30),
            ]);

            JournalApproval::create([
                'journal_id' => $journal->id,
                'approver_id' => $guru->id,
                'role_level' => 'guru-pembimbing',
                'status' => 'approved',
                'notes' => 'Kompetensi kurikulum rekayasa web tercapai dengan baik.',
                'created_at' => $pastDate->copy()->setTime(19, 0),
            ]);
        }

        // Sample pending request for Budi Pratama (spkl2)
        AttendanceRequest::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spkl2->id,
            'type' => 'izin',
            'start_date' => Carbon::today()->addDay()->toDateString(),
            'end_date' => Carbon::today()->addDay()->toDateString(),
            'reason' => 'Mengikuti tes uji sertifikasi kompetensi keahlian nasional di Balai Latihan Vokasi.',
            'status' => 'pending',
        ]);

        // Sample Assessment for Ahmad Fauzi (spkl1)
        Assessment::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spkl1->id,
            'score_dudi_discipline' => 90.00,
            'score_dudi_technical' => 88.00,
            'score_dudi_initiative' => 85.00,
            'score_teacher_report' => 92.00,
            'final_score' => 89.40,
            'notes_dudi' => 'Siswa sangat adaptif terhadap teknologi baru dan berdisiplin tinggi.',
            'notes_teacher' => 'Laporan mingguan dan jurnal tersusun secara sistematis dan rapi.',
        ]);
    }
}
