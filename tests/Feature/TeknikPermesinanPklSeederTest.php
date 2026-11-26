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
use Database\Seeders\TeknikPermesinanPklSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeknikPermesinanPklSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tp_seeder_merges_datasets_normalizes_coords_work_hours_and_teacher_only_approval(): void
    {
        $this->seed([
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            TeknikPermesinanPklSeeder::class,
        ]);

        // Idempotensi
        $this->seed(TeknikPermesinanPklSeeder::class);

        $school = School::where('npsn', '20209203')->firstOrFail();
        $major = Major::where('school_id', $school->id)->where('code', 'TP')->firstOrFail();

        // 1. Kelas & Wali Kelas (penyatuan ejaan Taufiq Ismail / Taufik Ismail menjadi 12 guru)
        $tp1 = SchoolClass::with('homeroomTeacher.user')->where('school_id', $school->id)->where('name', 'XII TP 1')->firstOrFail();
        $this->assertSame('Taufiq Ismail, S.Tr.T.', $tp1->homeroomTeacher?->user?->name);

        // 2. Total siswa (30), guru (12), dan DUDI (10)
        $students = Student::with('user.profile')->where('class_id', $tp1->id)->get();
        $this->assertCount(30, $students);
        $this->assertSame(12, $major->teachers()->count());

        // Dummy row 2425112 Dede Rahmat XI TP 1 tidak ikut di-seed
        $this->assertNull(Student::where('nis', '2425112')->first());

        // Fajri Nugraha menggunakan NIS kanonis 242510797 (bukan typo NIS siswa DKV 242510826)
        $fajri = $students->firstWhere('nis', '242510797');
        $this->assertNotNull($fajri);
        $this->assertSame('FAJRI NUGRAHA', $fajri->user?->name);

        // Gender siswi perempuan & format telepon bersih
        $wulan = $students->firstWhere('nis', '242510823');
        $this->assertNotNull($wulan);
        $this->assertSame('P', $wulan->user?->profile?->gender);
        $this->assertSame('0895413844747', $wulan->user?->profile?->phone);

        $companies = Company::where('school_id', $school->id)
            ->whereHas('majors', fn ($q) => $q->where('majors.id', $major->id))
            ->get();
        $this->assertCount(10, $companies);
        $this->assertSame(0, Supervisor::whereIn('company_id', $companies->pluck('id'))->count());

        foreach ($companies as $company) {
            $this->assertFalse($company->requires_industry_approval, "{$company->name} must use teacher-only verification");
            $this->assertTrue($company->gps_enabled, "{$company->name} must have GPS coordinates enabled");
            $this->assertTrue($company->override_hours);
            $this->assertNotNull($company->custom_work_hours);
        }

        // 3. Normalisasi koordinat trailing dot (-7.2045.) pada PT. SINKO TEKNIK UTAMA & fallback jam terlambat
        $sinko = $companies->firstWhere('name', 'PT. SINKO TEKNIK UTAMA');
        $this->assertNotNull($sinko);
        $this->assertEqualsWithDelta(-7.2045, $sinko->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.9255, $sinko->longitude, 0.000001);
        $this->assertSame(150, $sinko->radius_meters);
        $this->assertSame('08:00:00', $sinko->custom_work_hours['checkin_start']);
        $this->assertSame('09:00:00', $sinko->custom_work_hours['checkin_late']);
        $this->assertSame('16:00:00', $sinko->custom_work_hours['checkout_start']);

        // 4. Normalisasi koordinat tanpa desimal (-7225644, 107871631) pada HIJAYA
        $hijaya = $companies->firstWhere('name', 'HIJAYA');
        $this->assertNotNull($hijaya);
        $this->assertEqualsWithDelta(-7.225644, $hijaya->latitude, 0.000001);
        $this->assertEqualsWithDelta(107.871631, $hijaya->longitude, 0.000001);
        $this->assertSame('15:00:00', $hijaya->custom_work_hours['checkout_start']);

        $polman = $companies->firstWhere('name', 'POLMAN');
        $this->assertNotNull($polman);
        $this->assertSame(300, $polman->radius_meters);
        $this->assertSame('07:30:00', $polman->custom_work_hours['checkin_start']);
        $this->assertSame('08:30:00', $polman->custom_work_hours['checkin_late']);

        // 5. Seluruh plotting TP memiliki supervisor_id = null & guru dapat langsung menyetujui jurnal
        $spkls = StudentPkl::with('teacher.user')->whereIn('student_id', $students->pluck('id'))->get();
        $this->assertCount(30, $spkls);
        $this->assertTrue($spkls->every(fn (StudentPkl $s) => $s->supervisor_id === null));

        $sampleSpkl = $spkls->first();
        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $sampleSpkl->id,
            'date' => now()->toDateString(),
            'activity_description' => 'Melaksanakan pembubutan poros bertingkat dan pengukuran mikrometer.',
            'status' => 'submitted',
        ]);
        $approved = app(JournalService::class)->approve($journal, $sampleSpkl->teacher->user, 'Terverifikasi langsung oleh guru pembimbing TP.');
        $this->assertSame('approved', $approved->status);
    }
}
