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
use Database\Seeders\DesainKomunikasiVisualPklSeeder;
use Database\Seeders\MajorAndClassSeeder;
use Database\Seeders\SchoolSeeder;
use Database\Seeders\Smkn2GarutMasterStudentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesainKomunikasiVisualAndMasterSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_dkv_seeder_imports_csv_fixes_coordinates_phones_work_hours_empty_dudi_supervisor_and_teacher_only_approval(): void
    {
        $this->seed([
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            DesainKomunikasiVisualPklSeeder::class,
            Smkn2GarutMasterStudentSeeder::class,
        ]);

        // Idempotensi
        $this->seed([
            DesainKomunikasiVisualPklSeeder::class,
            Smkn2GarutMasterStudentSeeder::class,
        ]);

        $school = School::where('npsn', '20209203')->firstOrFail();
        $dkvMajor = Major::where('school_id', $school->id)->where('code', 'DKV')->firstOrFail();

        // 1. Kelas & Wali Kelas DKV
        $dkv1 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII DKV 1')->firstOrFail();
        $dkv2 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII DKV 2')->firstOrFail();
        $this->assertSame('Andri Riswanto, S.T., M.Pd.', $dkv1->homeroomTeacher?->user?->name);
        $this->assertSame('Eneng Syamsiyah, S.Pd., M.Pd.', $dkv2->homeroomTeacher?->user?->name);

        // 2. Total siswa (73 incl. ALGA belum plotting), guru (12), dan DUDI DKV (22)
        $dkvStudentIds = Student::whereIn('class_id', [$dkv1->id, $dkv2->id])->pluck('id');
        $this->assertCount(73, $dkvStudentIds);
        $this->assertSame(12, $dkvMajor->teachers()->count());

        $dkvCompanies = Company::where('school_id', $school->id)
            ->whereHas('majors', fn ($q) => $q->where('majors.id', $dkvMajor->id))
            ->get();
        $this->assertCount(22, $dkvCompanies);
        $this->assertNull($dkvCompanies->firstWhere('name', '-'));
        $this->assertNull($dkvCompanies->firstWhere('name', 'PT-003'));
        $this->assertSame(0, Supervisor::whereIn('company_id', $dkvCompanies->pluck('id'))->count());

        foreach ($dkvCompanies as $company) {
            $this->assertFalse($company->requires_industry_approval);
            $this->assertTrue($company->gps_enabled);
            $this->assertSame(300, $company->radius_meters);
            $this->assertTrue($company->override_hours);
            $this->assertSame([
                'checkin_start' => '07:30:00',
                'checkin_late' => '08:00:00',
                'checkin_alfa' => '11:00:00',
                'checkout_start' => '15:30:00',
            ], $company->custom_work_hours);
        }

        // 3. Normalisasi koordinat tanpa titik & mode koordinat Pengadilan Agama
        $joejoe = $dkvCompanies->firstWhere('name', 'JOEJOE CREATIVE');
        $this->assertNotNull($joejoe);
        $this->assertEqualsWithDelta(-7.218393, $joejoe->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.927881, $joejoe->longitude, 0.000001);

        $paGarut = $dkvCompanies->firstWhere('name', 'PENGADILAN AGAMA GARUT KELAS 1A');
        $this->assertNotNull($paGarut);
        $this->assertEqualsWithDelta(-7.190132, $paGarut->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.896393, $paGarut->longitude, 0.000001);

        // 4. Normalisasi no WA yang kehilangan angka 0 di awal & sinkronisasi ke wa_number + siswa belum plotting
        $adriansyah = Student::with('user.profile')->where('nis', '242510538')->firstOrFail();
        $this->assertSame('08212765812', $adriansyah->user->profile->phone);
        $this->assertSame('08212765812', $adriansyah->user->wa_number);

        $alvidia = Student::with('user.profile')->where('nis', '242510539')->firstOrFail();
        $this->assertSame('08212765812', $alvidia->user->profile->phone);
        $this->assertSame('08212765812', $alvidia->user->wa_number);

        $alga = Student::with('user')->where('nis', '242510575')->firstOrFail();
        $this->assertSame('ALGA', $alga->user->name);
        $this->assertSame(0, StudentPkl::where('student_id', $alga->id)->count());

        $this->assertNull(Student::where('nis', '2425107')->first());

        // 5. Plotting DKV tanpa pembimbing DU/DI & guru dapat langsung menyetujui jurnal
        $spkls = StudentPkl::with('teacher.user')->whereIn('student_id', $dkvStudentIds)->get();
        $this->assertCount(72, $spkls);
        $this->assertTrue($spkls->every(fn (StudentPkl $s) => $s->supervisor_id === null));

        $sampleSpkl = $spkls->first();
        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $sampleSpkl->id,
            'date' => now()->toDateString(),
            'activity_description' => 'Desain materi promosi visual dan layout pracetak klien.',
            'status' => 'submitted',
        ]);
        $approved = app(JournalService::class)->approve($journal, $sampleSpkl->teacher->user, 'Disetujui guru pembimbing DKV.');
        $this->assertSame('approved', $approved->status);

        // 6. Verifikasi siswa master yang belum terplotting (TITL, TEI, TKR, TL, TP, XIII GP)
        $gpMajor = Major::where('school_id', $school->id)->where('code', 'GP')->firstOrFail();
        $this->assertSame(4, (int) $gpMajor->program_duration_years);
        $this->assertTrue(SchoolClass::where('school_id', $school->id)->where('name', 'XIII GP 1')->where('grade_level', 'XIII')->exists());
        $this->assertTrue(SchoolClass::where('school_id', $school->id)->where('name', 'XII TITL 3')->exists());
        $this->assertTrue(SchoolClass::where('school_id', $school->id)->where('name', 'XII ELIN 2')->exists());
        $this->assertTrue(SchoolClass::where('school_id', $school->id)->where('name', 'XII TKRO 3')->exists());
        $this->assertTrue(SchoolClass::where('school_id', $school->id)->where('name', 'XII TL 1')->exists());
        $this->assertTrue(SchoolClass::where('school_id', $school->id)->where('name', 'XII TP 1')->exists());

        $unplottedStudent = Student::with('user')->where('nis', '242510109')->firstOrFail();
        $this->assertSame('ALDO MOHAMAD SYAWAL LUKMAN', $unplottedStudent->user->name);
        $this->assertSame(0, StudentPkl::where('student_id', $unplottedStudent->id)->count());
    }
}
