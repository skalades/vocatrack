<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssessmentAndPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_formula_calculation(): void
    {
        $school = School::create([
            'npsn' => '99999999',
            'name' => 'SMK Penilaian',
            'address' => 'Jakarta',
            'phone' => '021-999',
            'email' => 'smk@penilaian.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $school->id, 'code' => 'RPL', 'name' => 'RPL']);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Mitra Penilaian',
            'address' => 'Jakarta',
            'contact_person' => 'PIC',
            'phone' => '0811',
            'work_days' => '5_days',
        ]);

        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'Periode 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $guruUser = User::create([
            'school_id' => $school->id,
            'name' => 'Guru Penilai',
            'email' => 'guru@penilai.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $guruUser->id, 'nip' => '9988']);

        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa Dinilai',
            'email' => 'siswa@penilai.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'nis' => '901', 'nisn' => '9900112233']);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        // DU/DI scores: 90, 80, 85 -> average = 85
        // Guru score: 90
        // Weight: 60% DU/DI : 40% Guru
        // Expected Final Score: (85 * 0.6) + (90 * 0.4) = 51 + 36 = 87.0
        $assessment = Assessment::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spkl->id,
            'score_dudi_discipline' => 90.00,
            'score_dudi_technical' => 80.00,
            'score_dudi_initiative' => 85.00,
            'score_teacher_report' => 90.00,
            'final_score' => 0.00,
        ]);

        $service = new AssessmentService();
        $finalScore = $service->calculateFinalScore($assessment);

        $this->assertEquals(87.0, $finalScore);

        $assessment->update(['final_score' => $finalScore]);

        // Test PDF Download as Teacher
        $this->actingAs($guruUser);

        $response = $this->get(route('guru.reports.download', $spkl->id));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_admin_can_upload_school_logo_and_it_is_included_in_pdf(): void
    {
        Storage::fake('public');

        $school = School::create([
            'npsn' => '88888888',
            'name' => 'SMK Negeri 1 Garut',
            'address' => 'Jl. Cimanuk No. 309 Garut',
            'phone' => '0262-233315',
            'email' => 'info@smkn1garut.sch.id',
            'is_active' => true,
        ]);

        $adminUser = User::create([
            'school_id' => $school->id,
            'name' => 'Admin SMKN 1 Garut',
            'email' => 'admin@smkn1garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $guruUser = User::create([
            'school_id' => $school->id,
            'name' => 'Guru SMKN 1',
            'email' => 'guru@smkn1garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $guruUser->id, 'nip' => '19800101']);

        $major = Major::create(['school_id' => $school->id, 'code' => 'TKJ', 'name' => 'Teknik Komputer Jaringan']);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'name' => 'XII TKJ 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Telkom Indonesia',
            'address' => 'Garut',
            'contact_person' => 'Budi',
            'phone' => '081234',
            'work_days' => '5_days',
        ]);

        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'Periode 2026/2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Rina Marlina',
            'email' => 'rina@smkn1garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'nis' => '102030', 'nisn' => '0055443322']);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        $fakeLogo = UploadedFile::fake()->image('logo_sekolah.png', 200, 200);

        // 1. Admin uploads school logo via settings
        $response = $this->actingAs($adminUser)->post(route('adminsekolah.settings.update'), [
            'school_name' => 'SMK Negeri 1 Garut',
            'school_phone' => '0262-233315',
            'school_address' => 'Jl. Cimanuk No. 309 Garut',
            'checkin_start' => '06:00:00',
            'checkin_late' => '07:30:00',
            'checkin_alfa' => '10:00:00',
            'checkout_start' => '16:00:00',
            'journal_backdate_limit' => 3,
            'weight_dudi' => 60,
            'weight_guru' => 40,
            'kop_header_line1' => 'PEMERINTAH PROVINSI JAWA BARAT',
            'kop_header_line2' => 'DINAS PENDIDIKAN CABANG DINAS XI',
            'kop_header_line3' => 'SMK NEGERI 1 GARUT',
            'school_logo' => $fakeLogo,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $school->refresh();
        $this->assertNotNull($school->logo_path);
        Storage::disk('public')->assertExists($school->logo_path);

        // 2. Download PDF as teacher and ensure PDF renders successfully with logo
        $pdfResponse = $this->actingAs($guruUser)->get(route('guru.reports.download', $spkl->id));
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));

        // 3. Admin can remove school logo
        $removeResponse = $this->actingAs($adminUser)->post(route('adminsekolah.settings.update'), [
            'school_name' => 'SMK Negeri 1 Garut',
            'school_phone' => '0262-233315',
            'school_address' => 'Jl. Cimanuk No. 309 Garut',
            'checkin_start' => '06:00:00',
            'checkin_late' => '07:30:00',
            'checkin_alfa' => '10:00:00',
            'checkout_start' => '16:00:00',
            'journal_backdate_limit' => 3,
            'weight_dudi' => 60,
            'weight_guru' => 40,
            'remove_logo' => 1,
        ]);

        $removeResponse->assertRedirect();
        $school->refresh();
        $this->assertNull($school->logo_path);
    }
}
