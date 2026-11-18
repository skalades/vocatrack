<?php

namespace Tests\Feature;

use App\Console\Commands\EvaluateDailyAbsence;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPklStartedOptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_company_has_is_pkl_started_defaulting_to_false(): void
    {
        $school = School::first();
        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Mitra Baru',
            'address' => 'Jl. Industri No. 1',
            'contact_person' => 'Budi PIC',
            'phone' => '08123456789',
            'work_days' => '5_days',
        ]);

        $this->assertFalse($company->is_pkl_started);
        $this->assertFalse($company->fresh()->is_pkl_started);
    }

    public function test_admin_sekolah_can_toggle_is_pkl_started(): void
    {
        $admin = User::where('role', 'admin-sekolah')->first();
        $company = Company::where('school_id', $admin->school_id)->first();

        // Default: false
        $company->update(['is_pkl_started' => false]);

        // Admin aktifkan: sudah mulai PKL
        $response = $this->actingAs($admin)->put(route('adminsekolah.companies.update', $company), [
            'name' => $company->name,
            'address' => $company->address,
            'contact_person' => $company->contact_person,
            'phone' => $company->phone,
            'work_days' => '5_days',
            'is_pkl_started' => '1',
            'requires_industry_approval' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($company->fresh()->is_pkl_started);

        // Admin matikan kembali: belum mulai PKL (posisi off)
        $response = $this->actingAs($admin)->put(route('adminsekolah.companies.update', $company), [
            'name' => $company->name,
            'address' => $company->address,
            'contact_person' => $company->contact_person,
            'phone' => $company->phone,
            'work_days' => '5_days',
            // is_pkl_started omitted / unchecked
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertFalse($company->fresh()->is_pkl_started);
    }

    public function test_when_pkl_is_not_started_student_is_exempt_from_alfa_and_attendance(): void
    {
        $studentUser = User::where('role', 'siswa')->first();
        $this->assertNotNull($studentUser);
        $student = $studentUser->student;

        $spkl = StudentPkl::where('student_id', $student->id)->where('status', 'active')->first();
        $this->assertNotNull($spkl);
        $company = $spkl->company;

        // Set perusahaan belum mulai PKL (is_pkl_started = false)
        $company->update(['is_pkl_started' => false]);

        // 1. Kunjungi Beranda Siswa
        $homeResponse = $this->actingAs($studentUser)->get(route('student.home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('PKL Belum Dimulai');
        $homeResponse->assertSee('dianulir dari seluruh kewajiban presensi');

        // 2. Coba Check-In saat PKL belum mulai -> Diblokir
        $checkinResponse = $this->actingAs($studentUser)->postJson(route('student.checkin'), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'accuracy' => 10,
        ]);
        $checkinResponse->assertStatus(422);
        $checkinResponse->assertJsonValidationErrors(['time']);

        // 3. Jalankan evaluator ketidakhadiran (EvaluateDailyAbsence)
        // Siswa tidak boleh dicatat sebagai ALFA!
        $today = Carbon::today()->toDateString();
        $this->artisan('vokatrack:evaluate-absence')->assertSuccessful();

        $alfaRecord = Attendance::where('student_pkl_id', $spkl->id)
            ->where('date', $today)
            ->where('status', 'alfa')
            ->first();

        $this->assertNull($alfaRecord, 'Siswa yang perusahaannya belum mulai PKL tidak boleh dicatat sebagai alfa.');
    }

    public function test_when_pkl_is_started_student_obligations_are_active(): void
    {
        $studentUser = User::where('role', 'siswa')->first();
        $spkl = StudentPkl::where('student_id', $studentUser->student->id)->where('status', 'active')->first();
        $company = $spkl->company;

        // Set perusahaan sudah mulai PKL (is_pkl_started = true)
        $company->update(['is_pkl_started' => true, 'gps_enabled' => false]);

        // Kunjungi Beranda Siswa
        $homeResponse = $this->actingAs($studentUser)->get(route('student.home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertDontSee('PKL Belum Dimulai');
        $homeResponse->assertSee('Presensi Masuk');
    }
}
