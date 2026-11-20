<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalPhoto;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SecureFileService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompressJournalPhotosCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_command_compresses_large_photos_and_updates_quota(): void
    {
        Storage::fake(SecureFileService::DISK);

        $school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Kompresi Test',
            'email' => 'kompresi@test.sch.id',
            'address' => 'Jl. Uji',
            'phone' => '021',
            'is_active' => true,
            'storage_used_bytes' => 500000,
        ]);

        $major = Major::create(['school_id' => $school->id, 'code' => 'TKJ', 'name' => 'TKJ']);
        $class = SchoolClass::create(['school_id' => $school->id, 'major_id' => $major->id, 'name' => 'X TKJ 1', 'grade_level' => 'X', 'academic_year' => '2026/2027']);
        $company = Company::create(['school_id' => $school->id, 'name' => 'PT Uji', 'address' => 'Jl. Uji', 'contact_person' => 'Uji', 'phone' => '08', 'work_days' => '5_days']);
        $period = PklPeriod::create(['school_id' => $school->id, 'name' => 'P1', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $teacherUser = User::create(['school_id' => $school->id, 'name' => 'Guru', 'email' => 'g@t.id', 'password' => bcrypt('x'), 'role' => 'guru-pembimbing', 'is_active' => true]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'nip' => '1']);
        $studentUser = User::create(['school_id' => $school->id, 'name' => 'Siswa', 'email' => 's@t.id', 'password' => bcrypt('x'), 'role' => 'siswa', 'is_active' => true]);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'nis' => '1', 'nisn' => '1']);
        $spkl = StudentPkl::create(['school_id' => $school->id, 'student_id' => $student->id, 'company_id' => $company->id, 'pkl_period_id' => $period->id, 'teacher_id' => $teacher->id, 'status' => 'active']);

        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spkl->id,
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Aktivitas pengujian kompresi foto jurnal lama.',
            'status' => 'draft',
        ]);

        // Buat foto buatan resolusi besar
        $im = imagecreatetruecolor(2000, 1500);
        $red = imagecolorallocate($im, 200, 30, 30);
        imagefilledrectangle($im, 0, 0, 2000, 1500, $red);
        ob_start();
        imagejpeg($im, null, 100);
        $rawBytes = ob_get_clean();
        imagedestroy($im);

        $path = 'schools/' . $school->id . '/journals/2026/test_oversized.jpg';
        Storage::disk(SecureFileService::DISK)->put($path, $rawBytes);
        $originalSize = strlen($rawBytes);

        $photo = JournalPhoto::create([
            'journal_id' => $journal->id,
            'photo_path' => $path,
        ]);

        // Jalankan perintah artisan dengan threshold 10 KB
        $this->artisan('vokatrack:compress-journal-photos', ['--threshold' => 10])
            ->expectsOutputToContain('Audit foto jurnal selesai.')
            ->assertSuccessful();

        $compressedContent = Storage::disk(SecureFileService::DISK)->get($path);
        $compressedSize = strlen($compressedContent);

        $this->assertLessThan($originalSize, $compressedSize);
        $this->assertLessThan(500000, $school->fresh()->storage_used_bytes);
    }
}
