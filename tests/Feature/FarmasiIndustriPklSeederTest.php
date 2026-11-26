<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Journal;
use App\Models\Major;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\User;
use App\Services\JournalService;
use Database\Seeders\FarmasiIndustriPklSeeder;
use Database\Seeders\MajorAndClassSeeder;
use Database\Seeders\SchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmasiIndustriPklSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_fi_seeder_imports_csv_with_custom_work_hours_empty_dudi_supervisor_and_teacher_only_journal_verification(): void
    {
        $this->seed([
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            FarmasiIndustriPklSeeder::class,
        ]);

        // Jalankan ulang untuk memastikan idempotensi
        $this->seed(FarmasiIndustriPklSeeder::class);

        $school = School::where('npsn', '20209203')->firstOrFail();
        $major = Major::where('school_id', $school->id)->where('code', 'FI')->firstOrFail();
        $this->assertSame('Farmasi Industri', $major->name);

        // 1. Kelas & Wali Kelas
        $class1 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII FI 1')->firstOrFail();
        $class2 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII FI 2')->firstOrFail();
        $this->assertSame('Eutik Supiati, S.Si.', $class1->homeroomTeacher?->user?->name);
        $this->assertSame('apt. Risma Agustina, S.Farm', $class2->homeroomTeacher?->user?->name);

        // 2. Total siswa, guru, dan DUDI FI
        $fiStudentIds = Student::whereIn('class_id', [$class1->id, $class2->id])->pluck('id');
        $this->assertCount(71, $fiStudentIds);
        $this->assertSame(7, $major->teachers()->count());

        $fiCompanies = Company::where('school_id', $school->id)
            ->whereHas('majors', fn ($q) => $q->where('majors.id', $major->id))
            ->get();
        $this->assertCount(21, $fiCompanies);

        // 3. Pembimbing DU/DI dikosongkan, jam kerja khusus terisi, dan verifikasi jurnal langsung oleh guru
        $this->assertSame(0, Supervisor::whereIn('company_id', $fiCompanies->pluck('id'))->count());
        $this->assertSame(0, User::where('role', 'pembimbing-dudi')->count());

        foreach ($fiCompanies as $company) {
            $this->assertFalse($company->requires_industry_approval, "{$company->name} must use teacher-only verification");
            $this->assertTrue($company->gps_enabled);
            $this->assertSame(300, $company->radius_meters);
            $this->assertTrue($company->override_hours);
            $this->assertSame([
                'checkin_start' => '07:30:00',
                'checkin_late' => '10:00:00',
                'checkin_alfa' => '11:00:00',
                'checkout_start' => '16:00:00',
            ], $company->custom_work_hours);
        }

        // 4. Verifikasi salah satu koordinat perusahaan (trimmed & presisi)
        $berkah = $fiCompanies->firstWhere('name', 'PT. BERKAH ALAM NUSANTARA');
        $this->assertNotNull($berkah);
        $this->assertEqualsWithDelta(-7.16715177, $berkah->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.99597853, $berkah->longitude, 0.000001);

        // 5. Seluruh plotting siswa FI memiliki supervisor_id = null
        $spkls = StudentPkl::with(['teacher.user', 'company'])->whereIn('student_id', $fiStudentIds)->get();
        $this->assertCount(71, $spkls);
        $this->assertTrue($spkls->every(fn (StudentPkl $spkl) => $spkl->supervisor_id === null));

        // 6. Verifikasi gender siswa laki-laki & perempuan
        $fahri = Student::with('user.profile')->where('nis', '242510690')->firstOrFail();
        $this->assertSame('L', $fahri->user->profile->gender);
        $agits = Student::with('user.profile')->where('nis', '242510682')->firstOrFail();
        $this->assertSame('P', $agits->user->profile->gender);

        // 7. Guru pembimbing dapat langsung menyetujui jurnal berstatus 'submitted' tanpa pembimbing DU/DI
        $sampleSpkl = $spkls->first();
        $teacherUser = $sampleSpkl->teacher->user;
        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $sampleSpkl->id,
            'date' => now()->toDateString(),
            'activity_description' => 'Pemeriksaan mutu sediaan tablet dan pencatatan dokumen IPC.',
            'status' => 'submitted',
        ]);

        $approved = app(JournalService::class)->approve($journal, $teacherUser, 'Terverifikasi langsung oleh guru pembimbing FI.');
        $this->assertSame('approved', $approved->status);
    }
}
