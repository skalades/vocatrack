<?php

namespace Tests\Feature;

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
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setupEnvironment(): array
    {
        $school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Uji Presensi',
            'address' => 'Jakarta',
            'phone' => '021-123456',
            'email' => 'smk@uji.sch.id',
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
            'name' => 'PT Test Geofencing',
            'address' => 'Jakarta',
            'contact_person' => 'Budi',
            'phone' => '0812',
            'gps_enabled' => true,
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'radius_meters' => 100,
            'work_days' => '5_days',
        ]);

        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'PKL 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'nip' => '1234']);

        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa Test',
            'email' => 'siswa@test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => '1001',
            'nisn' => '0011223344',
        ]);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        return compact('school', 'company', 'spkl', 'studentUser');
    }

    public function test_haversine_distance_calculation(): void
    {
        $service = new AttendanceService();

        // Distance between Monas (-6.175392, 106.827153) and Bundaran HI (-6.195034, 106.823038) ~ 2.2 km
        $distance = $service->calculateHaversineDistance(-6.175392, 106.827153, -6.195034, 106.823038);

        $this->assertGreaterThan(2100, $distance);
        $this->assertLessThan(2300, $distance);
    }

    public function test_checkin_evaluates_radius_and_records_attendance(): void
    {
        $env = $this->setupEnvironment();
        $service = new AttendanceService();

        // 1. Within radius (same exact coordinates)
        $attendance = $service->recordCheckIn($env['spkl'], [
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'photo_path' => 'schools/test/attendances/photo.jpg',
        ]);

        $this->assertInstanceOf(Attendance::class, $attendance);
        $this->assertTrue($attendance->is_within_radius);
        $this->assertEquals(Carbon::today()->toDateString(), $attendance->date->toDateString());

        // 2. Duplicate check-in on the same day must throw ValidationException
        $this->expectException(ValidationException::class);
        $service->recordCheckIn($env['spkl'], [
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'photo_path' => 'schools/test/attendances/photo2.jpg',
        ]);
    }
}
