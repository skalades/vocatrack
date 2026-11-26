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
use Database\Seeders\TeknikKendaraanRinganPklSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeknikKendaraanRinganPklSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tkr_seeder_merges_datasets_normalizes_coords_work_hours_and_teacher_only_approval(): void
    {
        $this->seed([
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            TeknikKendaraanRinganPklSeeder::class,
        ]);

        // Idempotensi
        $this->seed(TeknikKendaraanRinganPklSeeder::class);

        $school = School::where('npsn', '20209203')->firstOrFail();
        $major = Major::where('school_id', $school->id)->where('code', 'TKR')->firstOrFail();

        // 1. Kelas & Wali Kelas (termasuk penyatuan alias Dra. Hj. Atin Priatin Nurhaeni)
        $tkro1 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII TKRO 1')->firstOrFail();
        $tkro2 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII TKRO 2')->firstOrFail();
        $tkro3 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII TKRO 3')->firstOrFail();

        $this->assertSame('Dra. Hj. Atin Priatin Nurhaeni', $tkro1->homeroomTeacher?->user?->name);
        $this->assertSame('Ali Aji Santoso, S.Pd', $tkro2->homeroomTeacher?->user?->name);
        $this->assertSame('Santi Susanti, S.Pd., M.Si', $tkro3->homeroomTeacher?->user?->name);

        // 2. Total siswa (95 incl. 2 belum plotting), guru (18), dan DUDI TKR (33)
        $studentIds = Student::whereIn('class_id', [$tkro1->id, $tkro2->id, $tkro3->id])->pluck('id');
        $this->assertCount(95, $studentIds);
        $this->assertSame(18, $major->teachers()->count());

        $afgan = Student::with('user')->where('nis', '242510395')->firstOrFail();
        $revan = Student::with('user')->where('nis', '242510417')->firstOrFail();
        $this->assertSame($tkro2->id, $afgan->class_id);
        $this->assertSame($tkro2->id, $revan->class_id);
        $this->assertSame(0, StudentPkl::whereIn('student_id', [$afgan->id, $revan->id])->count());

        $melany = Student::with('user.profile')->where('nis', '242510446')->firstOrFail();
        $this->assertSame('P', $melany->user?->profile?->gender);

        $companies = Company::where('school_id', $school->id)
            ->whereHas('majors', fn ($q) => $q->where('majors.id', $major->id))
            ->get();
        $this->assertCount(33, $companies);
        $this->assertNull($companies->firstWhere('name', '-'));
        $this->assertSame(0, Supervisor::whereIn('company_id', $companies->pluck('id'))->count());

        foreach ($companies as $company) {
            $this->assertFalse($company->requires_industry_approval, "{$company->name} must use teacher-only verification");
            $this->assertTrue($company->override_hours);
            $this->assertSame('07:30:00', $company->custom_work_hours['checkin_start']);
            $this->assertSame('10:00:00', $company->custom_work_hours['checkin_late']);
            $this->assertSame('11:00:00', $company->custom_work_hours['checkin_alfa']);
            $this->assertSame('16:00:00', $company->custom_work_hours['checkout_start']);
        }

        // 3. Normalisasi koordinat 16 digit rusak dari Excel pada PT. PRIMAJASA PERDANARAYA UTAMA
        $primajasa = $companies->firstWhere('name', 'PT. PRIMAJASA PERDANARAYA UTAMA');
        $this->assertNotNull($primajasa);
        $this->assertTrue($primajasa->gps_enabled);
        $this->assertEqualsWithDelta(-7.09916242, $primajasa->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.92850851, $primajasa->longitude, 0.000001);
        $this->assertSame(300, $primajasa->radius_meters);

        // 4. Perusahaan tanpa koordinat (TIGA BERSAUDARA) menonaktifkan GPS
        $tigaBersaudara = $companies->firstWhere('name', 'TIGA BERSAUDARA');
        $this->assertNotNull($tigaBersaudara);
        $this->assertFalse($tigaBersaudara->gps_enabled);
        $this->assertNull($tigaBersaudara->latitude);
        $this->assertNull($tigaBersaudara->longitude);

        // 5. PT. DIPO INTERNASIONAL vs PT. DIPO INTERNATIONAL PAHALA OTOMOTIF sesuai plotting live
        $dipoGarut = $companies->firstWhere('name', 'PT. DIPO INTERNASIONAL PAHALA OTOMOTIF');
        $dipoInternational = $companies->firstWhere('name', 'PT. DIPO INTERNATIONAL PAHALA OTOMOTIF');
        $this->assertNotNull($dipoGarut);
        $this->assertNotNull($dipoInternational);
        $this->assertEqualsWithDelta(-7.20393534, $dipoGarut->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.89115447, $dipoGarut->longitude, 0.000001);
        $this->assertEqualsWithDelta(-7.20393534, $dipoInternational->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.89115447, $dipoInternational->longitude, 0.000001);

        $fahmi = Student::where('nis', '242510400')->firstOrFail();
        $fahmiSpkl = StudentPkl::where('student_id', $fahmi->id)->firstOrFail();
        $this->assertSame($dipoGarut->id, $fahmiSpkl->company_id);

        // 6. Seluruh plotting TKR memiliki supervisor_id = null & guru dapat langsung menyetujui jurnal
        $spkls = StudentPkl::with('teacher.user')->whereIn('student_id', $studentIds)->get();
        $this->assertCount(93, $spkls);
        $this->assertTrue($spkls->every(fn (StudentPkl $s) => $s->supervisor_id === null));

        $sampleSpkl = $spkls->first();
        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $sampleSpkl->id,
            'date' => now()->toDateString(),
            'activity_description' => 'Melaksanakan tune up mesin dan pemeriksaan sistem pengereman kendaraan ringan.',
            'status' => 'submitted',
        ]);
        $approved = app(JournalService::class)->approve($journal, $sampleSpkl->teacher->user, 'Terverifikasi langsung oleh guru pembimbing TKR.');
        $this->assertSame('approved', $approved->status);
    }
}
