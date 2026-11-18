<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Journal;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningObjectiveWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $kajurUser;
    protected Teacher $kajurTeacher;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected Major $majorGp;
    protected Major $majorPspt;
    protected SchoolClass $classGp;
    protected User $studentUser;
    protected Student $student;
    protected StudentPkl $studentPkl;
    protected Company $company;
    protected User $supervisorUser;
    protected Supervisor $supervisor;
    protected PklPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20209203',
            'name' => 'SMK Negeri 2 Garut',
            'address' => 'Jl. Suherman No. 90, Tarogong Kaler, Kabupaten Garut',
            'phone' => '0262-233141',
            'email' => 'smkn2garut@sch.id',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@smkn2garut.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->kajurUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Herdi Pebryana Putra, S.T.',
            'email' => 'herdi.kajur@smkn2garut.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->kajurTeacher = Teacher::create([
            'user_id' => $this->kajurUser->id,
            'nip' => '198501012010011005',
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing GP',
            'email' => 'guru.gp@smkn2garut.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '198802022012011002',
        ]);

        $this->majorGp = Major::create([
            'school_id' => $this->school->id,
            'code' => 'GP',
            'name' => 'Teknik Geologi Pertambangan',
            'head_teacher_id' => $this->kajurTeacher->id,
        ]);

        $this->majorPspt = Major::create([
            'school_id' => $this->school->id,
            'code' => 'PSPT',
            'name' => 'Produksi dan Siaran Program Televisi',
        ]);

        $this->classGp = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorGp->id,
            'name' => 'XII GP 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Antam Tbk UBPE Pongkor',
            'contact_person' => 'Bpk. Hendra Gunawan',
            'phone' => '021-12345678',
            'address' => 'Nanggung, Bogor',
            'is_active' => true,
            'is_pkl_started' => true,
        ]);
        $this->majorGp->companies()->attach($this->company->id);

        $this->supervisorUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Pembimbing Industri Tambang',
            'email' => 'mentor.antam@antam.com',
            'password' => bcrypt('password123'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $this->supervisor = Supervisor::create([
            'user_id' => $this->supervisorUser->id,
            'company_id' => $this->company->id,
            'position' => 'Senior Mine Geologist',
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'PKL Semester Ganjil 2026/2027',
            'start_date' => Carbon::now()->subMonths(1),
            'end_date' => Carbon::now()->addMonths(5),
            'is_active' => true,
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Riza Bustan Fauzi',
            'email' => 'riza@smkn2garut.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $this->studentUser->id, 'gender' => 'L']);
        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $this->classGp->id,
            'nis' => '242510001',
            'nisn' => '0086894608',
            'parent_token' => 'testparenttoken1234567890abcdef',
            'is_parent_portal_active' => true,
        ]);

        $this->studentPkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'pkl_period_id' => $this->period->id,
            'company_id' => $this->company->id,
            'teacher_id' => $this->teacher->id,
            'supervisor_id' => $this->supervisor->id,
            'status' => 'active',
        ]);
    }

    public function test_kajur_can_view_competencies_index_page(): void
    {
        LearningObjective::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorGp->id,
            'code' => 'GP-CP01',
            'title' => 'Penerapan K3LH Pertambangan',
            'element_name' => 'K3LH Industri',
            'target_hours' => 60,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->kajurUser)->get(route('kajur.competencies'));

        $response->assertStatus(200);
        $response->assertSee('GP-CP01');
        $response->assertSee('Penerapan K3LH Pertambangan');
        $response->assertSee('Teknik Geologi Pertambangan');
    }

    public function test_kajur_can_create_learning_objective(): void
    {
        $payload = [
            'code' => 'gp-cp02',
            'title' => 'Pemetaan Geologi Lapangan & Navigasi',
            'element_name' => 'Geologi Lapangan',
            'description' => 'Orientasi medan menggunakan kompas dan GPS handheld.',
            'target_hours' => 120,
            'sort_order' => 2,
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->kajurUser)
            ->post(route('kajur.competencies.store'), $payload);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('learning_objectives', [
            'school_id' => $this->school->id,
            'major_id' => $this->majorGp->id,
            'code' => 'GP-CP02', // automatically uppercased
            'title' => 'Pemetaan Geologi Lapangan & Navigasi',
            'target_hours' => 120,
        ]);
    }

    public function test_kajur_can_update_learning_objective(): void
    {
        $lo = LearningObjective::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorGp->id,
            'code' => 'GP-CP03',
            'title' => 'Core Logging Awal',
            'element_name' => 'Eksplorasi',
            'target_hours' => 80,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->kajurUser)
            ->put(route('kajur.competencies.update', $lo), [
                'code' => 'GP-CP03',
                'title' => 'Pemboran Eksplorasi & Logging Inti Bor (Core Logging)',
                'element_name' => 'Eksplorasi & Pengeboran',
                'description' => 'Deskripsi litologi core batuan dan penghitungan RQD.',
                'target_hours' => 140,
                'sort_order' => 3,
                'is_active' => 1,
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('learning_objectives', [
            'id' => $lo->id,
            'title' => 'Pemboran Eksplorasi & Logging Inti Bor (Core Logging)',
            'target_hours' => 140,
        ]);
    }

    public function test_kajur_cannot_update_or_delete_lo_of_another_major(): void
    {
        $psptLo = LearningObjective::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorPspt->id,
            'code' => 'PSPT-CP01',
            'title' => 'Tata Kamera Studio',
            'element_name' => 'Kamera',
            'target_hours' => 100,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $updateResponse = $this->actingAs($this->kajurUser)
            ->put(route('kajur.competencies.update', $psptLo), [
                'code' => 'PSPT-CP01',
                'title' => 'Hacked Title',
            ]);
        $updateResponse->assertStatus(403);

        $deleteResponse = $this->actingAs($this->kajurUser)
            ->delete(route('kajur.competencies.destroy', $psptLo));
        $deleteResponse->assertStatus(403);

        $this->assertDatabaseHas('learning_objectives', ['id' => $psptLo->id, 'title' => 'Tata Kamera Studio']);
    }

    public function test_kajur_can_delete_learning_objective(): void
    {
        $lo = LearningObjective::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorGp->id,
            'code' => 'GP-TEMP',
            'title' => 'Temporary CP',
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->kajurUser)
            ->delete(route('kajur.competencies.destroy', $lo));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('learning_objectives', ['id' => $lo->id]);
    }

    public function test_admin_sekolah_can_create_competency_for_any_major(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('adminsekolah.majors.competencies.store', $this->majorPspt), [
                'code' => 'pspt-cp02',
                'title' => 'Tata Suara & Audio Mixing Siaran Live',
                'element_name' => 'Audio Broadcast',
                'target_hours' => 100,
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('learning_objectives', [
            'school_id' => $this->school->id,
            'major_id' => $this->majorPspt->id,
            'code' => 'PSPT-CP02',
            'title' => 'Tata Suara & Audio Mixing Siaran Live',
        ]);
    }

    public function test_student_journal_attaches_learning_objectives(): void
    {
        $cp1 = LearningObjective::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorGp->id,
            'code' => 'GP-CP01',
            'title' => 'K3LH Pertambangan',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $cp2 = LearningObjective::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorGp->id,
            'code' => 'GP-CP02',
            'title' => 'Pemetaan Geologi Lapangan',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $today = Carbon::today()->toDateString();

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.journal.store'), [
                'date' => $today,
                'activity_description' => 'Melakukan pemetaan geologi singkapan batuan kuarsit menggunakan kompas geologi dan GPS di pit 2.',
                'learning_objective_ids' => [$cp1->id, $cp2->id],
            ]);

        $response->assertSessionHas('success');

        $journal = Journal::where('student_pkl_id', $this->studentPkl->id)
            ->whereDate('date', $today)
            ->first();

        $this->assertNotNull($journal, 'Journal should have been created in database');
        $this->assertEquals(2, $journal->learningObjectives()->count());
        $this->assertTrue($journal->learningObjectives->contains('id', $cp1->id));
        $this->assertTrue($journal->learningObjectives->contains('id', $cp2->id));

        // Test Student Journals page renders with CP
        $studentPage = $this->actingAs($this->studentUser)->get(route('student.journals'));
        $studentPage->assertStatus(200);
        $studentPage->assertSee('GP-CP01');
        $studentPage->assertSee('GP-CP02');

        // Test Reviewer (Teacher & DUDI) sees journal with CP
        $teacherPage = $this->actingAs($this->teacherUser)->get(route('guru.journals'));
        $teacherPage->assertStatus(200);
        $teacherPage->assertSee('GP-CP01');

        $dudiPage = $this->actingAs($this->supervisorUser)->get(route('dudi.journals'));
        $dudiPage->assertStatus(200);
        $dudiPage->assertSee('GP-CP01');
    }
}
