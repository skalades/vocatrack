<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAttendanceOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected User $otherTeacherUser;
    protected Teacher $otherTeacher;
    protected Company $company;
    protected PklPeriod $period;
    protected StudentPkl $spkl;
    protected StudentPkl $otherSpkl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '77889900',
            'name' => 'SMKN 1 Garut',
            'address' => 'Jl. Cimanuk',
            'phone' => '0262-111222',
            'email' => 'info@smkn1garut.sch.id',
            'is_active' => true,
        ]);

        // Teacher 1
        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Dra. Siti Aminah',
            'email' => 'siti@smkn1garut.sch.id',
            'password' => 'password123',
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '198501012010012001',
        ]);

        // Teacher 2 (Other teacher)
        $this->otherTeacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Drs. Hendra',
            'email' => 'hendra@smkn1garut.sch.id',
            'password' => 'password123',
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->otherTeacher = Teacher::create([
            'user_id' => $this->otherTeacherUser->id,
            'nip' => '198501012010011002',
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

        $this->company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Telekomunikasi Indonesia',
            'address' => 'Jl. Pahlawan No. 10',
            'contact_person' => 'Budi Santoso',
            'phone' => '081234567890',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Tahun Ajaran 2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        // Student 1 (assigned to Teacher 1)
        $userS1 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@siswa.smkn1garut.sch.id',
            'password' => 'password123',
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student1 = Student::create([
            'user_id' => $userS1->id,
            'class_id' => $class->id,
            'nis' => '12345',
            'nisn' => '0055112233',
        ]);
        $this->spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student1->id,
            'company_id' => $this->company->id,
            'teacher_id' => $this->teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        // Student 2 (assigned to Teacher 2)
        $userS2 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Bunga Citra',
            'email' => 'bunga@siswa.smkn1garut.sch.id',
            'password' => 'password123',
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student2 = Student::create([
            'user_id' => $userS2->id,
            'class_id' => $class->id,
            'nis' => '12346',
            'nisn' => '0055112244',
        ]);
        $this->otherSpkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student2->id,
            'company_id' => $this->company->id,
            'teacher_id' => $this->otherTeacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);
    }

    public function test_teacher_can_override_attendance_when_student_phone_is_broken(): void
    {
        $today = Carbon::today()->toDateString();

        $response = $this->actingAs($this->teacherUser)
            ->post(route('guru.attendance.override'), [
                'student_pkl_id' => $this->spkl->id,
                'date' => $today,
                'status' => 'hadir',
                'checkin_time' => '07:35',
                'checkout_time' => '16:05',
                'reason' => 'HP siswa rusak / mati total. Konfirmasi fisik hadir di industri dari mentor lapangan.',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $attendance = Attendance::where('student_pkl_id', $this->spkl->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals($today, $attendance->date->toDateString());
        $this->assertEquals('hadir', $attendance->status);
        $this->assertEquals('07:35:00', $attendance->checkin_time);
        $this->assertEquals('16:05:00', $attendance->checkout_time);
        $this->assertTrue((bool) $attendance->is_within_radius);
        $this->assertStringContainsString('[OVERRIDE GURU: Dra. Siti Aminah]', $attendance->checkout_notes);
        $this->assertStringContainsString('HP siswa rusak', $attendance->checkout_notes);

        // Verify Audit Log
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'action' => 'attendance_override',
            'module' => 'attendance',
        ]);
    }

    public function test_teacher_can_override_and_correct_existing_alfa_attendance(): void
    {
        $today = Carbon::today()->toDateString();

        // Previously recorded as 'alfa'
        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => $today,
            'status' => 'alfa',
            'checkout_notes' => 'Tidak hadir tanpa keterangan',
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->post(route('guru.attendance.override'), [
                'student_pkl_id' => $this->spkl->id,
                'date' => $today,
                'status' => 'hadir',
                'checkin_time' => '07:30',
                'reason' => 'Siswa hadir di kantor, HP layar pecah tidak dapat selfie.',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $attendance = Attendance::where('student_pkl_id', $this->spkl->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('hadir', $attendance->status);
        $this->assertStringContainsString('HP layar pecah', $attendance->checkout_notes);
    }

    public function test_teacher_cannot_override_student_assigned_to_another_teacher(): void
    {
        $today = Carbon::today()->toDateString();

        $response = $this->actingAs($this->teacherUser)
            ->post(route('guru.attendance.override'), [
                'student_pkl_id' => $this->otherSpkl->id, // Belongs to other teacher
                'date' => $today,
                'status' => 'hadir',
                'reason' => 'Mencoba override siswa guru lain.',
            ]);

        $response->assertStatus(404);
    }

    public function test_teacher_cannot_override_attendance_with_future_date(): void
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $response = $this->actingAs($this->teacherUser)
            ->post(route('guru.attendance.override'), [
                'student_pkl_id' => $this->spkl->id,
                'date' => $tomorrow,
                'status' => 'hadir',
                'reason' => 'Presensi besok.',
            ]);

        $response->assertSessionHasErrors(['date']);
    }

    public function test_teacher_cannot_override_attendance_with_insufficient_reason(): void
    {
        $today = Carbon::today()->toDateString();

        $response = $this->actingAs($this->teacherUser)
            ->post(route('guru.attendance.override'), [
                'student_pkl_id' => $this->spkl->id,
                'date' => $today,
                'status' => 'hadir',
                'reason' => 'hp', // Too short (< 5 chars)
            ]);

        $response->assertSessionHasErrors(['reason']);
    }
}
