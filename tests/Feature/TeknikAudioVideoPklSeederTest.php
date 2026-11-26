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
use Database\Seeders\MajorAndClassSeeder;
use Database\Seeders\SchoolSeeder;
use Database\Seeders\TeknikAudioVideoPklSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeknikAudioVideoPklSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tav_seeder_imports_csv_normalizes_coordinates_leaves_dudi_supervisor_empty_and_enables_teacher_only_journal_verification(): void
    {
        $this->seed([
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            TeknikAudioVideoPklSeeder::class,
        ]);

        // Jalankan ulang untuk memastikan idempotensi
        $this->seed(TeknikAudioVideoPklSeeder::class);

        $school = School::where('npsn', '20209203')->firstOrFail();
        $major = Major::where('school_id', $school->id)->where('code', 'TAV')->firstOrFail();

        // 1. Kelas & Wali Kelas
        $class1 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII TAV 1')->firstOrFail();
        $class2 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII TAV 2')->firstOrFail();
        $this->assertSame('Agus Setiyarso, S.T.', $class1->homeroomTeacher?->user?->name);
        $this->assertSame('Margo Susilo Widiarto, S.ST', $class2->homeroomTeacher?->user?->name);

        // 2. Total siswa, guru, dan DUDI TAV
        $tavStudentIds = Student::whereIn('class_id', [$class1->id, $class2->id])->pluck('id');
        $this->assertCount(57, $tavStudentIds);
        $this->assertSame(7, $major->teachers()->count());

        $tavCompanies = Company::where('school_id', $school->id)
            ->whereHas('majors', fn ($q) => $q->where('majors.id', $major->id))
            ->get();
        $this->assertCount(18, $tavCompanies);

        // 3. Pembimbing DU/DI dikosongkan & verifikasi jurnal langsung oleh guru
        $this->assertSame(0, Supervisor::whereIn('company_id', $tavCompanies->pluck('id'))->count());
        $this->assertSame(0, User::where('role', 'pembimbing-dudi')->count());

        foreach ($tavCompanies as $company) {
            $this->assertFalse($company->requires_industry_approval, "{$company->name} must use teacher-only verification");
            $this->assertTrue($company->gps_enabled);
            $this->assertNotNull($company->latitude);
            $this->assertNotNull($company->longitude);
        }

        // 4. Verifikasi normalisasi koordinat yang terlanjur rusak di CSV
        $dewaSutratex = $tavCompanies->firstWhere('name', 'PT. Dewa Sutratex II');
        $this->assertNotNull($dewaSutratex);
        $this->assertEqualsWithDelta(-6.918929, $dewaSutratex->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.550769, $dewaSutratex->longitude, 0.000001);
        $this->assertSame(300, $dewaSutratex->radius_meters);

        $anugerah = $tavCompanies->firstWhere('name', 'PT. Anugerah Karya Lautan Berkah');
        $this->assertNotNull($anugerah);
        $this->assertEqualsWithDelta(-7.222980, $anugerah->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.941632, $anugerah->longitude, 0.000001);
        $this->assertSame(300, $anugerah->radius_meters);
        $this->assertStringNotContainsString("\xc2\xa0", $anugerah->address);

        $telkomAkses = $tavCompanies->firstWhere('name', 'PT. Telkom Akses');
        $this->assertNotNull($telkomAkses);
        $this->assertEqualsWithDelta(-7.216538, $telkomAkses->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.904678, $telkomAkses->longitude, 0.000001);

        // 5. Seluruh plotting siswa TAV memiliki supervisor_id = null
        $spkls = StudentPkl::with(['teacher.user', 'company'])->whereIn('student_id', $tavStudentIds)->get();
        $this->assertCount(57, $spkls);
        $this->assertTrue($spkls->every(fn (StudentPkl $spkl) => $spkl->supervisor_id === null));

        // 6. Verifikasi nomor WA yang diawali apostrof tetap bersih
        $rizki = Student::with('user.profile')->where('nis', '242510248')->firstOrFail();
        $this->assertSame('081214744375', $rizki->user->profile->phone);

        // 7. Guru pembimbing dapat langsung menyetujui jurnal berstatus 'submitted' tanpa pembimbing DU/DI
        $sampleSpkl = $spkls->first();
        $teacherUser = $sampleSpkl->teacher->user;
        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $sampleSpkl->id,
            'date' => now()->toDateString(),
            'activity_description' => 'Perawatan amplifier audio dan pengecekan catu daya.',
            'status' => 'submitted',
        ]);

        $approved = app(JournalService::class)->approve($journal, $teacherUser, 'Terverifikasi langsung oleh guru pembimbing.');
        $this->assertSame('approved', $approved->status);
    }
}
