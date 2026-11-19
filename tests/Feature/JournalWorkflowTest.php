<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_two_tier_approval_lifecycle(): void
    {
        $school = School::create([
            'npsn' => '88888888',
            'name' => 'SMK Journal Test',
            'address' => 'Jakarta',
            'phone' => '021-888',
            'email' => 'smk@journal.sch.id',
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
            'name' => 'PT Journal Partner',
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

        $dudiUser = User::create([
            'school_id' => $school->id,
            'name' => 'Pembimbing DU/DI',
            'email' => 'dudi@partner.com',
            'password' => bcrypt('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $supervisor = Supervisor::create(['user_id' => $dudiUser->id, 'company_id' => $company->id]);

        $guruUser = User::create([
            'school_id' => $school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@sekolah.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $guruUser->id, 'nip' => '112233']);

        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa Jurnal',
            'email' => 'siswa@sekolah.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'nis' => '501', 'nisn' => '5556667778']);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'supervisor_id' => $supervisor->id,
            'status' => 'active',
        ]);

        // 1. Siswa creates journal and submits
        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spkl->id,
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Mengerjakan instalasi database dan konfigurasi backend API server sesuai arahan.',
            'status' => 'submitted',
        ]);

        $service = new JournalService();

        // 2. Approval Tier 1 by DU/DI: submitted -> review_guru
        $service->approve($journal, $dudiUser, 'Pekerjaan sesuai dengan kebutuhan sprint.');
        $journal->refresh();
        $this->assertEquals('review_guru', $journal->status);
        $this->assertCount(1, $journal->approvals);

        // 3. Approval Tier 2 by Guru: review_guru -> approved
        $service->approve($journal, $guruUser, 'Kompetensi kurikulum tercapai.');
        $journal->refresh();
        $this->assertEquals('approved', $journal->status);
        $this->assertCount(2, $journal->approvals);
    }

    public function test_student_can_store_journal_with_client_compressed_base64_photo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $school = School::create([
            'npsn' => '77777777',
            'name' => 'SMK Journal Compress Test',
            'address' => 'Bandung',
            'phone' => '022-777',
            'email' => 'smk7@journal.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $school->id, 'code' => 'TKJ', 'name' => 'TKJ']);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'name' => 'XII TKJ 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Jaringan Solusi',
            'address' => 'Bandung',
            'contact_person' => 'PIC',
            'phone' => '0812',
            'work_days' => '5_days',
            'is_pkl_started' => true,
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
            'name' => 'Guru Pembimbing TKJ',
            'email' => 'guru.tkj@sekolah.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $guruUser->id, 'nip' => '998877']);

        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa Kompresi',
            'email' => 'siswa.kompres@sekolah.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'nis' => '502', 'nisn' => '5556667779']);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($studentUser)->post(route('student.journal.store'), [
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Melakukan pengujian konfigurasi router mikrotik dan setting bandwidth manajemen jaringan.',
            'compressed_photos' => [$base64Png],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $journal = Journal::where('student_pkl_id', $spkl->id)->first();
        $this->assertNotNull($journal);
        $this->assertCount(1, $journal->photos);

        $photo = $journal->photos->first();
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($photo->photo_path);
    }

    public function test_student_journal_store_ignores_empty_photos_payload_when_compressed_is_present(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $school = School::create([
            'npsn' => '77777778',
            'name' => 'SMK Journal Empty File Test',
            'address' => 'Bandung',
            'phone' => '022-778',
            'email' => 'smk8@journal.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $school->id, 'code' => 'TKJ', 'name' => 'TKJ']);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'name' => 'XII TKJ 2',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Jaringan Sukses',
            'address' => 'Bandung',
            'contact_person' => 'PIC',
            'phone' => '0813',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'Periode 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa Empty Payload',
            'email' => 'siswa.empty@sekolah.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'nis' => '503', 'nisn' => '5556667780']);

        $guruUser = User::create([
            'school_id' => $school->id,
            'name' => 'Guru Pembimbing Empty',
            'email' => 'guru.empty@sekolah.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $guruUser->id, 'nip' => '998878']);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Kirim request dengan compressed_photos dan photos berisi array string kosong / non-file dari browser
        $response = $this->actingAs($studentUser)->post(route('student.journal.store'), [
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Mengerjakan instalasi kabel jaringan LAN dan pengujian koneksi server lokal di lab sekolah.',
            'compressed_photos' => [$base64Png],
            'photos' => [''],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $journal = Journal::where('student_pkl_id', $spkl->id)->first();
        $this->assertNotNull($journal);
        $this->assertCount(1, $journal->photos);
    }

    public function test_student_journal_store_supports_heic_iphone_photos(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $school = School::create([
            'npsn' => '77777780',
            'name' => 'SMK iPhone Photo Test',
            'address' => 'Bandung',
            'phone' => '022-780',
            'email' => 'smk80@journal.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $school->id, 'code' => 'RPL', 'name' => 'RPL']);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 2',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Apple Tech',
            'address' => 'Bandung',
            'contact_person' => 'PIC',
            'phone' => '0812',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'Periode 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $guruUser = User::create([
            'school_id' => $school->id,
            'name' => 'Guru',
            'email' => 'guru80@sekolah.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $guruUser->id, 'nip' => '998877']);

        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa iPhone',
            'email' => 'iphone@sekolah.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'nis' => '901', 'nisn' => '9988776655']);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        // 1. Uji unggah berkas HEIC mentah dari kamera iPhone
        $heicFile = \Illuminate\Http\UploadedFile::fake()->create('IMG_8234.HEIC', 350, 'image/heic');
        $response1 = $this->actingAs($studentUser)->post(route('student.journal.store'), [
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Melakukan pengujian konfigurasi jaringan LAN dan mendokumentasikan topologi switch.',
            'photos' => [$heicFile],
        ]);

        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        $journal = Journal::where('student_pkl_id', $spkl->id)->first();
        $this->assertNotNull($journal);
        $this->assertCount(1, $journal->photos);
        $this->assertStringEndsWith('.heic', strtolower($journal->photos->first()->photo_path));

        // 2. Uji unggah berkas HEIC ketika browser/server membaca MIME sebagai application/octet-stream
        $yesterday = Carbon::yesterday()->toDateString();
        $octetHeic = \Illuminate\Http\UploadedFile::fake()->create('IMG_9999.heic', 500, 'application/octet-stream');
        $response2 = $this->actingAs($studentUser)->post(route('student.journal.store'), [
            'date' => $yesterday,
            'activity_description' => 'Mengerjakan instalasi kabel jaringan LAN dan pengujian koneksi server lokal di lab sekolah.',
            'photos' => [$octetHeic],
        ]);

        $response2->assertRedirect();
        $response2->assertSessionHas('success');

        $journalYesterday = Journal::where('student_pkl_id', $spkl->id)->whereDate('date', $yesterday)->first();
        $this->assertNotNull($journalYesterday);
        $this->assertCount(1, $journalYesterday->photos);
    }

    public function test_teacher_process_journal_preserves_active_tab(): void
    {
        $school = School::create([
            'npsn' => '77777779',
            'name' => 'SMK Journal Tab Test',
            'address' => 'Bandung',
            'phone' => '022-779',
            'email' => 'smk9@journal.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $school->id, 'code' => 'GP', 'name' => 'Geologi']);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'name' => 'XII GP 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Geothermal',
            'address' => 'Kamojang',
            'contact_person' => 'PIC',
            'phone' => '0814',
            'work_days' => '5_days',
            'is_pkl_started' => true,
            'requires_industry_approval' => false,
        ]);

        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'Periode 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Guru Pembimbing Tab',
            'email' => 'guru.tab@journal.sch.id',
            'password' => 'secret123',
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '19876543210',
        ]);

        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa Tab Test',
            'email' => 'siswa.tab@journal.sch.id',
            'password' => 'secret123',
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => '88801',
            'nisn' => '8880000001',
        ]);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spkl->id,
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Preparasi sampel batuan untuk XRF dan XRD.',
            'status' => 'submitted',
        ]);

        // Simulasikan approve dari Antrean Butuh Validasi (tab=pending)
        $response = $this->actingAs($teacherUser)
            ->from(route('guru.journals', ['tab' => 'pending']))
            ->post(route('guru.journals.process', $journal), [
                'action' => 'approve',
                'notes' => 'Kerja bagus.',
                'tab' => 'pending',
            ]);

        $response->assertRedirect(route('guru.journals', ['tab' => 'pending']));
        $response->assertSessionHas('active_tab', 'pending');
        $this->assertEquals('approved', $journal->fresh()->status);

        // Kunjungi kembali halaman guru.journals dengan session active_tab=pending
        $pageResponse = $this->actingAs($teacherUser)
            ->withSession(['active_tab' => 'pending'])
            ->get(route('guru.journals'));

        $pageResponse->assertStatus(200);
        $content = $pageResponse->getContent();

        // Verifikasi view-pending tidak memiliki class hidden, sedangkan view-students hidden
        $this->assertStringContainsString('id="view-pending" class="space-y-4"', $content);
        $this->assertStringContainsString('id="view-students" class="hidden space-y-4"', $content);
    }
}
