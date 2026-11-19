<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalPhoto;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentPkl;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanTestDataCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_clean_test_data_command_deletes_only_unstarted_company_records(): void
    {
        $school = School::first();
        $today = Carbon::today()->toDateString();

        // 1. Perusahaan Uji Coba (is_pkl_started = false)
        $testCompany = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Uji Coba Stress Test',
            'address' => 'Jl. Testing No. 1',
            'contact_person' => 'Tester',
            'phone' => '081299998888',
            'work_days' => '5_days',
            'is_pkl_started' => false,
        ]);

        // 2. Perusahaan Siswa Asli yang Aktif PKL (is_pkl_started = true)
        $realCompany = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Industri Nyata',
            'address' => 'Jl. Nyata No. 2',
            'contact_person' => 'Real PIC',
            'phone' => '081277776666',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        $spkls = StudentPkl::take(2)->get();
        $this->assertCount(2, $spkls);

        $spklTest = $spkls[0];
        $spklReal = $spkls[1];

        $spklTest->update(['company_id' => $testCompany->id]);
        $spklReal->update(['company_id' => $realCompany->id]);

        // Buat file fake
        $testPhotoPath = 'presensi/test_selfie.jpg';
        Storage::disk('local')->put($testPhotoPath, 'test content');

        $realPhotoPath = 'presensi/real_selfie.jpg';
        Storage::disk('local')->put($realPhotoPath, 'real content');

        // Buat data presensi untuk siswa test dan siswa real
        $attTest = Attendance::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spklTest->id,
            'date' => $today,
            'status' => 'hadir',
            'checkin_time' => '07:00:00',
            'checkin_photo_path' => $testPhotoPath,
        ]);

        $attReal = Attendance::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spklReal->id,
            'date' => $today,
            'status' => 'hadir',
            'checkin_time' => '07:05:00',
            'checkin_photo_path' => $realPhotoPath,
        ]);

        // Buat data jurnal untuk siswa test dan siswa real
        $jTest = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spklTest->id,
            'date' => $today,
            'activity_description' => 'Aktivitas uji coba presensi',
            'status' => 'submitted',
        ]);

        $jReal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spklReal->id,
            'date' => $today,
            'activity_description' => 'Aktivitas nyata di industri',
            'status' => 'approved',
        ]);

        // Test mode dry-run: tidak menghapus apa pun
        $this->artisan('vokatrack:clean-test-data', [
            '--date' => $today,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('attendances', ['id' => $attTest->id]);
        $this->assertDatabaseHas('attendances', ['id' => $attReal->id]);

        // Eksekusi pembersihan nyata dengan --force
        $this->artisan('vokatrack:clean-test-data', [
            '--date' => $today,
            '--force' => true,
        ])->assertSuccessful();

        // Data test TERHAPUS
        $this->assertDatabaseMissing('attendances', ['id' => $attTest->id]);
        $this->assertDatabaseMissing('journals', ['id' => $jTest->id]);
        Storage::disk('local')->assertMissing($testPhotoPath);

        // Data siswa nyata yang is_pkl_started = true AMAN 100%!
        $this->assertDatabaseHas('attendances', ['id' => $attReal->id]);
        $this->assertDatabaseHas('journals', ['id' => $jReal->id]);
        Storage::disk('local')->assertExists($realPhotoPath);
    }
}
