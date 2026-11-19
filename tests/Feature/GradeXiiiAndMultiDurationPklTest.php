<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ClassPromotionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeXiiiAndMultiDurationPklTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20199887',
            'name' => 'SMK Negeri 1 Geologi Test',
            'address' => 'Jl. Pendidikan Vokasi No. 1',
            'phone' => '021-12345678',
            'email' => 'smkn1geologi@test.sch.id',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Vokasi',
            'email' => 'admin@vokatest.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        Profile::create([
            'user_id' => $this->adminUser->id,
            'gender' => 'L',
        ]);
    }

    public function test_major_four_year_program_helper_and_duration(): void
    {
        $reguler = Major::create([
            'school_id' => $this->school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'program_duration_years' => 3,
            'default_pkl_duration_months' => 6,
        ]);

        $geologi = Major::create([
            'school_id' => $this->school->id,
            'code' => 'GP',
            'name' => 'Geologi Pertambangan',
            'program_duration_years' => 4,
            'default_pkl_duration_months' => 8,
        ]);

        $this->assertFalse($reguler->isFourYearProgram());
        $this->assertTrue($geologi->isFourYearProgram());
        $this->assertEquals(6, $reguler->default_pkl_duration_months);
        $this->assertEquals(8, $geologi->default_pkl_duration_months);
    }

    public function test_school_class_next_grade_and_auto_naming_patterns(): void
    {
        $geologi = Major::create([
            'school_id' => $this->school->id,
            'code' => 'GP',
            'name' => 'Geologi Pertambangan',
            'program_duration_years' => 4,
            'default_pkl_duration_months' => 8,
        ]);

        $reguler = Major::create([
            'school_id' => $this->school->id,
            'code' => 'TKJ',
            'name' => 'Teknik Komputer Jaringan',
            'program_duration_years' => 3,
            'default_pkl_duration_months' => 6,
        ]);

        // 1. Geologi Kelas XII -> Naik ke XIII
        $classGeologiXII = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $geologi->id,
            'name' => 'XII GP 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->assertEquals('XIII', $classGeologiXII->getNextGradeLevel());
        $this->assertEquals('XIII GP 1', $classGeologiXII->generateNextClassName());

        // 2. Reguler Kelas XII -> Lulus (null next grade)
        $classRegulerXII = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $reguler->id,
            'name' => 'XII TKJ 2',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->assertNull($classRegulerXII->getNextGradeLevel());
        $this->assertNull($classRegulerXII->generateNextClassName());

        // 3. Tingkat X -> Naik ke XI
        $classX = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $geologi->id,
            'name' => 'X GP 1',
            'grade_level' => 'X',
            'academic_year' => '2026/2027',
        ]);

        $this->assertEquals('XI', $classX->getNextGradeLevel());
        $this->assertEquals('XI GP 1', $classX->generateNextClassName());
    }

    public function test_automatic_promotion_service_promotes_geologi_to_xiii_and_graduates_reguler(): void
    {
        $geologi = Major::create([
            'school_id' => $this->school->id,
            'code' => 'GP',
            'name' => 'Geologi Pertambangan',
            'program_duration_years' => 4,
        ]);

        $reguler = Major::create([
            'school_id' => $this->school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'program_duration_years' => 3,
        ]);

        $classGeologi = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $geologi->id,
            'name' => 'XII GP 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $classReguler = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $reguler->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        // Buat 1 siswa Geologi dan 1 siswa Reguler
        $uGeologi = User::create([
            'school_id' => $this->school->id,
            'name' => 'Budi Geologi',
            'email' => 'budi@geologi.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
        ]);
        $studentGeologi = Student::create([
            'user_id' => $uGeologi->id,
            'class_id' => $classGeologi->id,
            'nis' => '1001',
            'nisn' => '9900112233',
        ]);

        $uReguler = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siti RPL',
            'email' => 'siti@rpl.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
        ]);
        $studentReguler = Student::create([
            'user_id' => $uReguler->id,
            'class_id' => $classReguler->id,
            'nis' => '1002',
            'nisn' => '9900112244',
        ]);

        $service = new ClassPromotionService();

        // 1. Preview
        $preview = $service->previewPromotion($this->school->id, '2026/2027', '2027/2028');
        $this->assertCount(2, $preview);

        $geologiPreview = collect($preview)->firstWhere('class_id', $classGeologi->id);
        $this->assertEquals('promote', $geologiPreview['action_type']);
        $this->assertEquals('XIII GP 1', $geologiPreview['target_class_name']);
        $this->assertEquals('XIII', $geologiPreview['target_grade']);

        $regulerPreview = collect($preview)->firstWhere('class_id', $classReguler->id);
        $this->assertEquals('graduate', $regulerPreview['action_type']);

        // 2. Eksekusi
        $stats = $service->executePromotion($this->school->id, '2026/2027', '2027/2028');

        $this->assertEquals(1, $stats['promoted_students_count']);
        $this->assertEquals(1, $stats['graduated_students_count']);
        $this->assertEquals(1, $stats['new_classes_created']);

        // Cek hasil update di database
        $studentGeologi->refresh();
        $studentReguler->refresh();

        // Siswa Geologi naik ke Kelas XIII
        $this->assertEquals('active', $studentGeologi->academic_status);
        $this->assertEquals('XIII GP 1', $studentGeologi->class->name);
        $this->assertEquals('XIII', $studentGeologi->class->grade_level);
        $this->assertEquals('2027/2028', $studentGeologi->class->academic_year);

        // Siswa Reguler dinyatakan lulus (alumni)
        $this->assertEquals('graduated', $studentReguler->academic_status);
        $this->assertEquals('2027', $studentReguler->graduation_year);
        $this->assertTrue($studentReguler->isGraduated());
    }

    public function test_student_pkl_effective_dates_and_company_differentiation(): void
    {
        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode PKL Utama 2026/2027',
            'start_date' => '2026-11-01',
            'end_date' => '2027-04-30', // 6 bulan
            'is_active' => true,
        ]);

        $companyTambang = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Freeport Mining Industry',
            'address' => 'Timika, Papua',
            'contact_person' => 'HRD Tambang',
            'phone' => '081234567890',
            'work_days' => '6_days',
            'default_start_date' => '2026-11-01',
            'default_end_date' => '2027-08-31', // 10 bulan khusus tambang
        ]);

        $uSiswa = User::create([
            'school_id' => $this->school->id,
            'name' => 'Arif Tambang',
            'email' => 'arif@tambang.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
        ]);
        $student = Student::create([
            'user_id' => $uSiswa->id,
            'class_id' => SchoolClass::create([
                'school_id' => $this->school->id,
                'major_id' => Major::create(['school_id' => $this->school->id, 'code' => 'GP', 'name' => 'Geologi', 'program_duration_years' => 4])->id,
                'name' => 'XII GP 1',
                'grade_level' => 'XII',
                'academic_year' => '2026/2027',
            ])->id,
            'nis' => '1003',
            'nisn' => '9900112255',
        ]);

        $uGuru = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Geologi',
            'email' => 'guru@geologi.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
        ]);
        $teacher = Teacher::create([
            'user_id' => $uGuru->id,
            'nip' => '198001012010011001',
        ]);

        // Student PKL dengan tanggal spesifik industri
        $spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $companyTambang->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'start_date' => '2026-11-01',
            'end_date' => '2027-08-31',
            'status' => 'active',
        ]);

        $this->assertEquals('2026-11-01', $spkl->effective_start_date->toDateString());
        $this->assertEquals('2027-08-31', $spkl->effective_end_date->toDateString());

        // Simulasi pengujian tanggal di bulan Juli 2027 (saat jurusan 3 tahun sudah lulus)
        Carbon::setTestNow('2027-07-15');
        $this->assertTrue($spkl->isPklActiveToday(), 'Siswa Geologi harus tetap aktif di bulan Juli 2027');

        // Simulasi pengujian setelah masa kontrak selesai (September 2027)
        Carbon::setTestNow('2027-09-01');
        $this->assertFalse($spkl->isPklActiveToday(), 'Siswa Geologi harus nonaktif setelah 31 Agustus 2027');

        Carbon::setTestNow(null); // Reset mock waktu
    }
}
