<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Journal;
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

class StudentAttendanceTicketJournalTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $studentUser;
    protected Student $student;
    protected StudentPkl $spkl;
    protected Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20209203',
            'name' => 'SMK Negeri 2 Garut',
            'address' => 'Jl. Suherman No. 90',
            'phone' => '0262-233131',
            'email' => 'info@smkn2garut.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $this->school->id, 'code' => 'GP', 'name' => 'Teknik Geologi']);
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII GP 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Pertamina Geothermal',
            'address' => 'Kamojang',
            'contact_person' => 'PIC Pertamina',
            'phone' => '0812345678',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'nip' => '19850101']);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mohamad Siswa',
            'email' => 'mohamad@siswa.smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $class->id,
            'nis' => '12055',
            'nisn' => '0087654321',
        ]);

        $this->spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
    }

    public function test_isi_jurnal_button_is_visible_when_student_has_not_filled_today_journal(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 14:15:00'));

        // Student has checked in today, but has not filled journal
        $this->attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-11-18',
            'checkin_time' => '14:12:00',
            'status' => 'terlambat',
            'is_within_radius' => true,
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('student.attendance'));

        $response->assertStatus(200);
        $response->assertSee('Isi Jurnal Hari Ini');
        $response->assertDontSee('Jurnal hari ini sudah terisi');
    }

    public function test_isi_jurnal_button_is_hidden_when_student_has_already_filled_today_journal(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 16:30:00'));

        // Student has checked in and checked out
        $this->attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-11-18',
            'checkin_time' => '14:12:00',
            'checkout_time' => '16:13:00',
            'status' => 'terlambat',
            'is_within_radius' => true,
            'is_checkout_within_radius' => false,
        ]);

        // Student has filled journal for today
        Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-11-18',
            'activity_description' => 'Melakukan pemetaan koordinat sampel sumur geothermal Kamojang.',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('student.attendance'));

        $response->assertStatus(200);
        // The button "Isi Jurnal Hari Ini" MUST NOT appear
        $response->assertDontSee('Isi Jurnal Hari Ini');
        // Instead, the confirmation badge and link must appear
        $response->assertSee('Jurnal hari ini sudah terisi');
        $response->assertSee('Lihat Jurnal');
        // And checkout reminder should not appear after checkout
        $response->assertDontSee('Pengingat: presensi pulang aktif pukul 16.00 WIB');
    }
}
