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
use App\Models\Teacher;
use App\Models\User;
use App\Services\SecureFileService;
use App\Services\StorageQuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JournalPhotoCompressionAuditTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $studentUser;
    protected StudentPkl $spkl;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(SecureFileService::DISK);

        $this->school = School::create([
            'npsn' => '99881122',
            'name' => 'SMK Kompresi Audit',
            'address' => 'Jl. Pendidikan',
            'phone' => '021-9988',
            'email' => 'audit@smk.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $this->school->id, 'code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Audit Partner',
            'address' => 'Jakarta',
            'contact_person' => 'PIC',
            'phone' => '0811',
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
            'name' => 'Guru Audit',
            'email' => 'guru@audit.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'school_id' => $this->school->id, 'nip' => '8881']);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Audit',
            'email' => 'siswa@audit.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $class->id,
            'nis' => '1001',
            'nisn' => '1001100110',
        ]);

        $this->spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
    }

    public function test_raw_large_uploaded_photo_is_compressed_and_resized_on_server(): void
    {
        // Buat gambar besar 2400 x 1600 px (khas kamera smartphone 12MP/48MP)
        $rawImage = imagecreatetruecolor(2400, 1600);
        $color = imagecolorallocate($rawImage, 50, 150, 220);
        imagefilledrectangle($rawImage, 0, 0, 2400, 1600, $color);

        // Tambahkan beberapa pola agar tidak terlalu seragam
        for ($i = 0; $i < 20; $i++) {
            $lineColor = imagecolorallocate($rawImage, rand(0, 255), rand(0, 255), rand(0, 255));
            imageline($rawImage, rand(0, 2400), rand(0, 1600), rand(0, 2400), rand(0, 1600), $lineColor);
        }

        ob_start();
        imagejpeg($rawImage, null, 95);
        $binary = ob_get_clean();
        imagedestroy($rawImage);

        $tempPath = tempnam(sys_get_temp_dir(), 'audit_raw_');
        file_put_contents($tempPath, $binary);
        $originalSize = strlen($binary);

        $uploadedFile = new UploadedFile($tempPath, 'kamera_smartphone_besar.jpg', 'image/jpeg', null, true);

        $response = $this->actingAs($this->studentUser)->post(route('student.journal.store'), [
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Melakukan pemeliharaan server database dan mencatat log transaksi secara berkala sepanjang hari.',
            'photos' => [$uploadedFile],
        ]);

        @unlink($tempPath);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $journal = Journal::where('student_pkl_id', $this->spkl->id)->first();
        $this->assertNotNull($journal);
        $this->assertCount(1, $journal->photos);

        $photo = $journal->photos->first();
        $savedPath = $photo->photo_path;

        // Pastikan berkas tersimpan di disk privat
        Storage::disk(SecureFileService::DISK)->assertExists($savedPath);

        $savedContent = Storage::disk(SecureFileService::DISK)->get($savedPath);
        $savedSize = strlen($savedContent);

        // Pastikan ukuran file hasil kompresi jauh lebih hemat dibanding aslinya
        $this->assertLessThan($originalSize, $savedSize);

        // Cek dimensi gambar hasil kompresi di disk: maksimal 1280px
        $imageInfo = @getimagesizefromstring($savedContent);
        $this->assertNotFalse($imageInfo);
        $this->assertLessThanOrEqual(1280, $imageInfo[0], 'Lebar foto tidak boleh lebih dari 1280px');
        $this->assertLessThanOrEqual(1280, $imageInfo[1], 'Tinggi foto tidak boleh lebih dari 1280px');

        // Pastikan StorageQuotaService merekam ukuran terkompresi, bukan ukuran mentah
        $this->school->refresh();
        $this->assertEquals($savedSize, $this->school->storage_used_bytes);
    }

    public function test_client_compressed_base64_photo_is_also_safely_saved_and_quota_recorded(): void
    {
        $im = imagecreatetruecolor(800, 600);
        $color = imagecolorallocate($im, 100, 200, 100);
        imagefilledrectangle($im, 0, 0, 800, 600, $color);
        ob_start();
        imagejpeg($im, null, 70);
        $jpg = ob_get_clean();
        imagedestroy($im);

        $base64 = 'data:image/jpeg;base64,' . base64_encode($jpg);

        $response = $this->actingAs($this->studentUser)->post(route('student.journal.store'), [
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Melakukan pengujian fitur kompresi foto jurnal pada perangkat mobile dan verifikasi ukuran berkas.',
            'compressed_photos' => [$base64],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $journal = Journal::where('student_pkl_id', $this->spkl->id)->first();
        $this->assertNotNull($journal);
        $this->assertCount(1, $journal->photos);

        $photo = $journal->photos->first();
        Storage::disk(SecureFileService::DISK)->assertExists($photo->photo_path);

        $savedContent = Storage::disk(SecureFileService::DISK)->get($photo->photo_path);
        $savedSize = strlen($savedContent);

        $this->school->refresh();
        $this->assertEquals($savedSize, $this->school->storage_used_bytes);
    }
}
