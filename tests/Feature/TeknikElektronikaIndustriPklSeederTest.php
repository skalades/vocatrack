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
use Database\Seeders\TeknikElektronikaIndustriPklSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeknikElektronikaIndustriPklSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tei_seeder_merges_datasets_normalizes_coords_work_hours_dates_and_teacher_only_approval(): void
    {
        $this->seed([
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            TeknikElektronikaIndustriPklSeeder::class,
        ]);

        // Idempotensi
        $this->seed(TeknikElektronikaIndustriPklSeeder::class);

        $school = School::where('npsn', '20209203')->firstOrFail();
        $major = Major::where('school_id', $school->id)->where('code', 'TEI')->firstOrFail();

        // 1. Kelas & Wali Kelas (termasuk alias Dra. Iceu Setiawati / Dra. Hj. Iceu Setiawati menyatu menjadi 12 guru)
        $elin1 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII ELIN 1')->firstOrFail();
        $elin2 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII ELIN 2')->firstOrFail();
        $this->assertSame('Dra. Hj. Iceu Setiawati', $elin1->homeroomTeacher?->user?->name);
        $this->assertSame('Hj. Siti Hasanaty, S.Pd., M.Si', $elin2->homeroomTeacher?->user?->name);

        // 2. Total siswa, guru, dan DUDI TEI
        $studentIds = Student::whereIn('class_id', [$elin1->id, $elin2->id])->pluck('id');
        $this->assertCount(69, $studentIds);
        $this->assertSame(12, $major->teachers()->count());

        $companies = Company::where('school_id', $school->id)
            ->whereHas('majors', fn ($q) => $q->where('majors.id', $major->id))
            ->get();
        $this->assertCount(23, $companies);
        $this->assertSame(0, Supervisor::whereIn('company_id', $companies->pluck('id'))->count());

        foreach ($companies as $company) {
            $this->assertFalse($company->requires_industry_approval, "{$company->name} must use teacher-only verification");
            $this->assertTrue($company->gps_enabled);
            $this->assertTrue($company->override_hours);
            $this->assertNotNull($company->custom_work_hours);
            $this->assertNotNull($company->default_start_date);
            $this->assertNotNull($company->default_end_date);
        }

        // 3. Normalisasi koordinat dengan nol belakang terpotong (1078696 -> 107.8696, 1078995 -> 107.8995)
        $novita = $companies->firstWhere('name', 'PT. NOVITA ELECTRIK MULTI ENERGI');
        $this->assertNotNull($novita);
        $this->assertEqualsWithDelta(-7.188982, $novita->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.8696, $novita->longitude, 0.000001);
        $this->assertSame(100, $novita->radius_meters);
        $this->assertSame('08:00:00', $novita->custom_work_hours['checkin_start']);
        $this->assertSame('08:30:00', $novita->custom_work_hours['checkin_late']);
        $this->assertSame('16:30:00', $novita->custom_work_hours['checkout_start']);

        $zahran = $companies->firstWhere('name', 'ZAHRAN VARIASI');
        $this->assertNotNull($zahran);
        $this->assertEqualsWithDelta(-7.200740, $zahran->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.8995, $zahran->longitude, 0.000001);
        $this->assertSame(300, $zahran->radius_meters);

        // 4. Tanggal PKL per perusahaan & siswa
        $faveHotel = $companies->firstWhere('name', 'FAVE HOTEL');
        $this->assertSame('2027-01-01', $faveHotel->default_start_date->toDateString());
        $this->assertSame('2027-03-07', $faveHotel->default_end_date->toDateString());

        $nugraha = Student::with('activePkl')->where('nis', '242510313')->firstOrFail();
        $this->assertSame('2027-01-01', $nugraha->activePkl->start_date->toDateString());
        $this->assertSame('2027-03-07', $nugraha->activePkl->end_date->toDateString());

        // 5. Nomor WA yang memiliki newline/apostrof tetap bersih
        $rehan = Student::with('user.profile')->where('nis', '242510317')->firstOrFail();
        $this->assertSame('083827589518', $rehan->user->profile->phone);

        // 6. Seluruh plotting TEI memiliki supervisor_id = null & guru dapat langsung menyetujui jurnal
        $spkls = StudentPkl::with('teacher.user')->whereIn('student_id', $studentIds)->get();
        $this->assertCount(69, $spkls);
        $this->assertTrue($spkls->every(fn (StudentPkl $s) => $s->supervisor_id === null));

        $sampleSpkl = $spkls->first();
        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $sampleSpkl->id,
            'date' => now()->toDateString(),
            'activity_description' => 'Instalasi panel kontrol dan pengujian sensor industri.',
            'status' => 'submitted',
        ]);
        $approved = app(JournalService::class)->approve($journal, $sampleSpkl->teacher->user, 'Terverifikasi langsung oleh guru pembimbing TEI.');
        $this->assertSame('approved', $approved->status);
    }
}
