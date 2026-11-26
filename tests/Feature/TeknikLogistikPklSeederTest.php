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
use Database\Seeders\BaseCsvMajorPklSeeder;
use Database\Seeders\MajorAndClassSeeder;
use Database\Seeders\SchoolSeeder;
use Database\Seeders\Smkn2GarutMasterStudentSeeder;
use Database\Seeders\TeknikLogistikPklSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeknikLogistikPklSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tl_seeder_imports_csv_merges_log_major_normalizes_coords_and_enables_teacher_only_approval(): void
    {
        $this->seed([
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            TeknikLogistikPklSeeder::class,
            Smkn2GarutMasterStudentSeeder::class,
        ]);

        // Idempotensi
        $this->seed([
            TeknikLogistikPklSeeder::class,
            Smkn2GarutMasterStudentSeeder::class,
        ]);

        $school = School::where('npsn', '20209203')->firstOrFail();

        // Tidak boleh membuat duplikat jurusan Teknik Logistik (MajorAndClassSeeder memakai kode LOG)
        $tlMajors = Major::where('school_id', $school->id)->whereIn('code', ['TL', 'LOG'])->get();
        $this->assertCount(1, $tlMajors);
        $tlMajor = $tlMajors->firstOrFail();

        // 1. Kelas & Wali Kelas XII TL 1
        $tl1 = SchoolClass::with('homeroomTeacher.user')
            ->where('school_id', $school->id)
            ->where('name', 'XII TL 1')
            ->firstOrFail();
        $this->assertSame($tlMajor->id, $tl1->major_id);
        $this->assertSame('Hj. Nanien Diani, M.Pd.', $tl1->homeroomTeacher?->user?->name);

        // 2. Total siswa, guru (4 pembimbing + 1 wali kelas), dan 9 perusahaan DU/DI
        $tlStudentIds = Student::where('class_id', $tl1->id)->pluck('id');
        $this->assertCount(29, $tlStudentIds);
        $this->assertSame(5, $tlMajor->teachers()->count());

        $tlCompanies = Company::where('school_id', $school->id)
            ->whereHas('majors', fn ($q) => $q->where('majors.id', $tlMajor->id))
            ->get();
        $this->assertCount(9, $tlCompanies);
        $this->assertSame(0, Supervisor::whereIn('company_id', $tlCompanies->pluck('id'))->count());

        foreach ($tlCompanies as $company) {
            $this->assertFalse($company->requires_industry_approval);
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

        // 3. Verifikasi koordinat PT. RAMAYANA dan YOGYA DEPT STORE (GARUT) + parsing tanda petik
        $ramayana = $tlCompanies->firstWhere('name', 'PT. RAMAYANA');
        $this->assertNotNull($ramayana);
        $this->assertEqualsWithDelta(-7.2110287, $ramayana->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.90121331, $ramayana->longitude, 0.000001);

        $yogya = $tlCompanies->firstWhere('name', 'YOGYA DEPT STORE (GARUT)');
        $this->assertNotNull($yogya);
        $this->assertEqualsWithDelta(-7.18720491, $yogya->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.90707464, $yogya->longitude, 0.000001);
        $this->assertEqualsWithDelta(107.90707464, BaseCsvMajorPklSeeder::parseCoordinate("'107.90707464057661", false), 0.000001);

        // 4. Verifikasi nomor WA dan gender siswa
        $abyan = Student::with('user.profile')->where('nis', '242510754')->firstOrFail();
        $this->assertSame('089504724721', $abyan->user->profile->phone);
        $this->assertSame('L', $abyan->user->profile->gender);

        $allifa = Student::with('user.profile')->where('nis', '242510756')->firstOrFail();
        $this->assertSame('P', $allifa->user->profile->gender);

        // 5. Plotting aktif tanpa pembimbing DU/DI & persetujuan jurnal oleh guru pembimbing
        $spkls = StudentPkl::with('teacher.user')->whereIn('student_id', $tlStudentIds)->get();
        $this->assertCount(29, $spkls);
        $this->assertTrue($spkls->every(fn (StudentPkl $s) => $s->supervisor_id === null));

        $sampleSpkl = $spkls->first();
        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $sampleSpkl->id,
            'date' => now()->toDateString(),
            'activity_description' => 'Pemeriksaan surat jalan dan penataan stok barang gudang.',
            'status' => 'submitted',
        ]);
        $approved = app(JournalService::class)->approve($journal, $sampleSpkl->teacher->user, 'Disetujui guru pembimbing TL.');
        $this->assertSame('approved', $approved->status);
    }
}
