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
use App\Services\JournalService;
use Database\Seeders\MajorAndClassSeeder;
use Database\Seeders\SchoolSeeder;
use Database\Seeders\TeknikInstalasiTenagaListrikPklSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeknikInstalasiTenagaListrikPklSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_titl_seeder_merges_datasets_seeds_unplaced_students_coords_and_teacher_only_approval(): void
    {
        $this->seed([
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            TeknikInstalasiTenagaListrikPklSeeder::class,
        ]);

        // Idempotensi
        $this->seed(TeknikInstalasiTenagaListrikPklSeeder::class);

        $school = School::where('npsn', '20209203')->firstOrFail();
        $major = Major::where('school_id', $school->id)->where('code', 'TITL')->firstOrFail();

        // 1. Kelas & Wali Kelas (termasuk penyatuan Iman Abdul Fatah S.ST / S.T.)
        $titl1 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII TITL 1')->firstOrFail();
        $titl2 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII TITL 2')->firstOrFail();
        $titl3 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII TITL 3')->firstOrFail();

        $this->assertSame('Iman Abdul Fatah, S.ST., S.T.', $titl1->homeroomTeacher?->user?->name);
        $this->assertSame('Gin Gin Jeni Purnama, S.Pd.', $titl2->homeroomTeacher?->user?->name);
        $this->assertSame('Chandra Maulidya, S.Pd.', $titl3->homeroomTeacher?->user?->name);

        // 2. Total siswa (100: 96 terplotting + 4 belum terplotting), 19 guru, dan 22 DUDI
        $students = Student::with(['user.profile', 'activePkl'])
            ->whereIn('class_id', [$titl1->id, $titl2->id, $titl3->id])
            ->get();
        $this->assertCount(100, $students);
        $this->assertSame(19, $major->teachers()->count());

        // Dummy/template row (2425104 Ihwan XI TITL 1) tidak boleh masuk
        $this->assertNull(Student::where('nis', '2425104')->first());

        // Siswa belum ditempatkan ("-") tetap dibuat akun & profil siswanya tanpa StudentPkl aktif
        $fajril = $students->firstWhere('nis', '242510118');
        $this->assertNotNull($fajril);
        $this->assertNull($fajril->activePkl);

        // NISN riil & gender siswi perempuan
        $intan = $students->firstWhere('nis', '242510156');
        $this->assertNotNull($intan);
        $this->assertSame('89764817', $intan->nisn);
        $this->assertSame('P', $intan->user?->profile?->gender);

        // 3. 22 DUDI dengan GPS aktif, tanpa Pembimbing DU/DI, dan verifikasi jurnal oleh guru
        $companies = Company::where('school_id', $school->id)
            ->whereHas('majors', fn ($q) => $q->where('majors.id', $major->id))
            ->get();
        $this->assertCount(22, $companies);
        $this->assertSame(0, Supervisor::whereIn('company_id', $companies->pluck('id'))->count());

        foreach ($companies as $company) {
            $this->assertFalse($company->requires_industry_approval, "{$company->name} must use teacher-only verification");
            $this->assertTrue($company->gps_enabled, "{$company->name} must have GPS coordinates enabled");
            $this->assertFalse($company->override_hours);
        }

        // Cek propagasi alamat & koordinat dari grup DUDI baris ke-1 ke siswa berikutnya dalam grup yang sama
        $aston = $companies->firstWhere('name', 'Hotel Aston Pasteur');
        $this->assertNotNull($aston);
        $this->assertEqualsWithDelta(-6.89365725, $aston->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.58720722, $aston->longitude, 0.000001);

        $fajar = $students->firstWhere('nis', '242510188');
        $this->assertSame($aston->id, $fajar?->activePkl?->company_id);

        // 4. 96 plotting PKL aktif memiliki supervisor_id = null & guru dapat langsung menyetujui jurnal
        $spkls = StudentPkl::with('teacher.user')
            ->whereIn('student_id', $students->pluck('id'))
            ->get();
        $this->assertCount(96, $spkls);
        $this->assertTrue($spkls->every(fn (StudentPkl $s) => $s->supervisor_id === null));

        $sampleSpkl = $spkls->first();
        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $sampleSpkl->id,
            'date' => now()->toDateString(),
            'activity_description' => 'Pemeriksaan panel distribusi 3 fasa dan pengujian tahanan isolasi motor.',
            'status' => 'submitted',
        ]);
        $approved = app(JournalService::class)->approve($journal, $sampleSpkl->teacher->user, 'Terverifikasi langsung oleh guru pembimbing TITL.');
        $this->assertSame('approved', $approved->status);
    }
}
