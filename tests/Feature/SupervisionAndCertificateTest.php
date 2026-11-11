<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\SupervisionVisit;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\SupervisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisionAndCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected User $studentUser;
    protected Student $student;
    protected Company $company;
    protected PklPeriod $period;
    protected StudentPkl $studentPkl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'SMK Negeri 1 Surabaya',
            'npsn' => '20500123',
            'email' => 'smkn1@example.com',
            'address' => 'Jl. Pendidikan No. 1 Surabaya',
            'phone' => '031-1234567',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Budi Santoso, S.Pd',
            'email' => 'budi.guru@example.com',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '198501012010011005',
        ]);

        $major = Major::create([
            'school_id' => $this->school->id,
            'name' => 'Teknik Komputer dan Jaringan',
            'code' => 'TKJ',
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII TKJ 1',
            'academic_year' => '2026/2027',
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Rizky Pratama',
            'email' => 'rizky@example.com',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $class->id,
            'nis' => '102938',
            'nisn' => '0061234567',
        ]);

        $this->company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Teknologi Kreasi Mandiri',
            'address' => 'Jl. Industri Kreatif Kav 4',
            'contact_person' => 'Agus Subekti (Manager HRD)',
            'phone' => '021-99887766',
            'latitude' => -7.2575,
            'longitude' => 112.7521,
            'radius_meters' => 100,
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'PKL Semester Ganjil 2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->studentPkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->company->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);
    }

    public function test_teacher_can_record_supervision_visit_and_download_berita_acara_pdf(): void
    {
        $this->actingAs($this->teacherUser);

        // 1. Catat logbook kunjungan melalui endpoint HTTP
        $response = $this->post(route('guru.supervisions.store'), [
            'company_id' => $this->company->id,
            'pkl_period_id' => $this->period->id,
            'stage' => 'monitoring_1',
            'visit_date' => '2026-08-15',
            'student_progress_summary' => 'Siswa telah beradaptasi dengan baik pada divisi perakitan jaringan fiber optik.',
            'company_feedback' => 'Disiplin dan kehadiran sangat baik, penguasaan materi teknis dasar memuaskan.',
            'follow_up_agreement' => 'Akan dilanjutkan dengan rotasi ke divisi pemeliharaan server.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan tersimpan di database
        $this->assertDatabaseHas('supervision_visits', [
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'company_id' => $this->company->id,
            'stage' => 'monitoring_1',
            'student_progress_summary' => 'Siswa telah beradaptasi dengan baik pada divisi perakitan jaringan fiber optik.',
        ]);

        $visit = SupervisionVisit::first();
        $this->assertNotNull($visit);
        $this->assertEquals('Monitoring 1 (Bulan Ke-1)', $visit->stage_label);

        // 2. Unduh PDF Berita Acara
        $pdfResponse = $this->get(route('guru.supervisions.pdf', $visit->id));
        $pdfResponse->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfResponse->headers->get('Content-Type'));
    }

    public function test_certificate_generation_and_score_calculation(): void
    {
        // 1. Buat penilaian akhir dari DU/DI dan Guru
        $assessment = Assessment::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->studentPkl->id,
            'score_dudi_discipline' => 90.0,
            'score_dudi_technical' => 88.0,
            'score_dudi_initiative' => 86.0,
            'score_teacher_report' => 85.0,
            'final_score' => 87.5,
            'notes_dudi' => 'Peserta didik sangat kompeten dan berdedikasi tinggi.',
            'notes_teacher' => 'Laporan disusun rapi sesuai pedoman.',
        ]);

        $service = app(CertificateService::class);
        $certificate = $service->generateForStudentPkl($this->studentPkl);

        $this->assertNotNull($certificate);
        $this->assertDatabaseHas('certificates', [
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->studentPkl->id,
            'final_predicate' => 'Sangat Baik',
        ]);

        $this->assertNotEmpty($certificate->uuid);
        $this->assertNotEmpty($certificate->certificate_number);
        $this->assertEquals(87.5, $certificate->final_score);
        $this->assertEquals('Sangat Baik', $certificate->final_predicate);

        // Uji render PDF 2-halaman
        $pdf = $service->renderPdf($certificate);
        $this->assertNotNull($pdf);
        $output = $pdf->output();
        $this->assertNotEmpty($output);
    }

    public function test_public_certificate_verification_by_uuid(): void
    {
        // 1. Generate sertifikat terlebih dahulu
        $service = app(CertificateService::class);
        $certificate = $service->generateForStudentPkl($this->studentPkl, [
            'final_score' => 92.00,
            'final_predicate' => 'Sangat Baik',
            'issued_date' => '2026-11-10',
        ]);

        // 2. Verifikasi publik (Guest / tanpa login)
        $verifyResponse = $this->get(route('public.cert.verify', $certificate->uuid));
        $verifyResponse->assertOk();
        $verifyResponse->assertSee('Sertifikat Resmi Praktik Kerja Lapangan');
        $verifyResponse->assertSee($certificate->certificate_number);
        $verifyResponse->assertSee('Rizky Pratama');
        $verifyResponse->assertSee('PT Teknologi Kreasi Mandiri');

        // 3. Unduh salinan PDF publik (Guest / tanpa login)
        $downloadResponse = $this->get(route('public.cert.download', $certificate->uuid));
        $downloadResponse->assertOk();
        $this->assertStringContainsString('application/pdf', $downloadResponse->headers->get('Content-Type'));

        // 4. Verifikasi UUID tidak dikenal / invalid
        $invalidResponse = $this->get(route('public.cert.verify', '00000000-0000-0000-0000-000000000000'));
        $invalidResponse->assertOk();
        $invalidResponse->assertSee('Verifikasi Dokumen Gagal');
        $invalidResponse->assertSee('Sertifikat Tidak Ditemukan / Tidak Valid');
    }
}
