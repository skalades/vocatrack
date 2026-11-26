<?php

namespace Tests\Feature;

use App\Exports\LeadershipCredentialsExport;
use App\Exports\Sheets\KajurAccountsDataSheet;
use App\Exports\Sheets\KajurBroadcastWhatsappSheet;
use App\Exports\Sheets\WaliKelasAccountsDataSheet;
use App\Exports\Sheets\WaliKelasBroadcastWhatsappSheet;
use App\Models\Major;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeadershipCredentialsExportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'SMK Negeri 2 Garut Test',
            'npsn' => '20209299',
            'address' => 'Jl. Suherman No. 90',
            'phone' => '0262-123456',
            'email' => 'info@smkn2test.sch.id',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah Test',
            'email' => 'admin@smkn2test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
    }

    public function test_export_leadership_generates_kajur_and_walikelas_sheets_with_filters_and_tenant_isolation(): void
    {
        // 1. Setup teachers: Kajur TITL, Wali Kelas TITL, Wali Kelas + Kajur TKR (dual role), and regular teacher
        $kajurTitl = $this->createTeacher('Kajur TITL', 'kajur.titl@test.sch.id', '198001012010011001', '08111111111');
        $waliTitl = $this->createTeacher('Wali XII TITL 1', 'wali.titl1@test.sch.id', '198502022012022002', '08222222222');
        $dualTkr = $this->createTeacher('Kajur dan Wali TKR', 'dual.tkr@test.sch.id', '198803032014031003', '08333333333');
        $regularTeacher = $this->createTeacher('Guru Biasa', 'guru.biasa@test.sch.id', '199104042018041004', '08444444444');

        $majorTitl = Major::create([
            'school_id' => $this->school->id,
            'head_teacher_id' => $kajurTitl->id,
            'code' => 'TITL',
            'name' => 'Teknik Instalasi Tenaga Listrik',
        ]);
        $majorTkr = Major::create([
            'school_id' => $this->school->id,
            'head_teacher_id' => $dualTkr->id,
            'code' => 'TKR',
            'name' => 'Teknik Kendaraan Ringan Otomotif',
        ]);

        $kajurTitl->majors()->attach($majorTitl->id);
        $waliTitl->majors()->attach($majorTitl->id);
        $dualTkr->majors()->attach($majorTkr->id);
        $regularTeacher->majors()->attach($majorTitl->id);

        $classTitl1 = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $majorTitl->id,
            'homeroom_teacher_id' => $waliTitl->id,
            'name' => 'XII TITL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);
        SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $majorTkr->id,
            'homeroom_teacher_id' => $dualTkr->id,
            'name' => 'XII TKRO 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        // Seed 2 students into XII TITL 1
        foreach (['24250001', '24250002'] as $nis) {
            $studentUser = User::create([
                'school_id' => $this->school->id,
                'name' => "Siswa {$nis}",
                'email' => "siswa.{$nis}@test.sch.id",
                'password' => bcrypt('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]);
            Student::create([
                'user_id' => $studentUser->id,
                'class_id' => $classTitl1->id,
                'nis' => $nis,
                'nisn' => $nis,
                'parent_token' => Str::random(32),
            ]);
        }

        // 2. Full export (role=all) produces 4 sheets
        $allExport = new LeadershipCredentialsExport($this->school->id);
        $allSheets = $allExport->sheets();
        $this->assertCount(4, $allSheets);
        $this->assertInstanceOf(KajurAccountsDataSheet::class, $allSheets[0]);
        $this->assertInstanceOf(KajurBroadcastWhatsappSheet::class, $allSheets[1]);
        $this->assertInstanceOf(WaliKelasAccountsDataSheet::class, $allSheets[2]);
        $this->assertInstanceOf(WaliKelasBroadcastWhatsappSheet::class, $allSheets[3]);

        $kajurRows = $allSheets[0]->collection();
        $this->assertTrue($kajurRows->contains(fn ($r) => in_array('Kajur TITL', (array) $r) && in_array('1 Kelas / 2 Siswa', (array) $r)));
        $this->assertTrue($kajurRows->contains(fn ($r) => in_array('Kajur dan Wali TKR', (array) $r)));
        $this->assertFalse($kajurRows->contains(fn ($r) => in_array('Wali XII TITL 1', (array) $r)));
        $this->assertFalse($kajurRows->contains(fn ($r) => in_array('Guru Biasa', (array) $r)));

        $kajurWaRows = $allSheets[1]->collection();
        $this->assertTrue($kajurWaRows->contains(fn ($r) => str_contains((string) ($r[4] ?? ''), 'Kepala Program Keahlian (Kajur)')));

        $waliRows = $allSheets[2]->collection();
        $this->assertTrue($waliRows->contains(fn ($r) => in_array('Wali XII TITL 1', (array) $r) && in_array('2 Siswa', (array) $r) && in_array('XII TITL 1', (array) $r)));
        $this->assertTrue($waliRows->contains(fn ($r) => in_array('Kajur dan Wali TKR', (array) $r) && in_array('XII TKRO 1', (array) $r)));
        $this->assertFalse($waliRows->contains(fn ($r) => in_array('Kajur TITL', (array) $r)));
        $this->assertFalse($waliRows->contains(fn ($r) => in_array('Guru Biasa', (array) $r)));

        $waliWaRows = $allSheets[3]->collection();
        $this->assertTrue($waliWaRows->contains(fn ($r) => str_contains((string) ($r[4] ?? ''), 'sebagai Wali Kelas')));

        // 3. Filter by major & search
        $filteredExport = new LeadershipCredentialsExport($this->school->id, ['major' => $majorTitl->id, 'search' => 'TITL']);
        $filteredSheets = $filteredExport->sheets();
        $this->assertCount(4, $filteredSheets);
        $this->assertFalse($filteredSheets[0]->collection()->contains(fn ($r) => in_array('Kajur dan Wali TKR', (array) $r)));
        $this->assertFalse($filteredSheets[2]->collection()->contains(fn ($r) => in_array('Kajur dan Wali TKR', (array) $r)));

        // 4. Role-specific HTTP XLSX downloads
        $resAll = $this->actingAs($this->admin)->get(route('adminsekolah.teachers.export-leadership'));
        $resAll->assertOk();
        $this->assertStringContainsString('Akun_Akses_Kajur_Wali_Kelas_', (string) $resAll->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', (string) $resAll->headers->get('content-disposition'));

        $resKajur = $this->actingAs($this->admin)->get(route('adminsekolah.teachers.export-leadership', ['role' => 'kajur']));
        $resKajur->assertOk();
        $this->assertStringContainsString('Akun_Akses_Kajur_', (string) $resKajur->headers->get('content-disposition'));

        $resWali = $this->actingAs($this->admin)->get(route('adminsekolah.teachers.export-leadership', ['role' => 'walikelas']));
        $resWali->assertOk();
        $this->assertStringContainsString('Akun_Akses_Wali_Kelas_', (string) $resWali->headers->get('content-disposition'));

        // 5. Role-specific CSV streams
        $resKajurCsv = $this->actingAs($this->admin)->get(route('adminsekolah.teachers.export-leadership', ['role' => 'kajur', 'format' => 'csv']));
        $resKajurCsv->assertOk();
        $kajurCsvContent = $resKajurCsv->streamedContent();
        $this->assertStringContainsString('Kajur TITL', $kajurCsvContent);
        $this->assertStringNotContainsString('Wali XII TITL 1', $kajurCsvContent);

        $resWaliCsv = $this->actingAs($this->admin)->get(route('adminsekolah.teachers.export-leadership', ['role' => 'walikelas', 'format' => 'csv']));
        $resWaliCsv->assertOk();
        $waliCsvContent = $resWaliCsv->streamedContent();
        $this->assertStringContainsString('Wali XII TITL 1', $waliCsvContent);
        $this->assertStringNotContainsString('Kajur TITL', $waliCsvContent);

        // 6. UI links present on Admin Teachers & Admin Majors pages
        $this->actingAs($this->admin)->get(route('adminsekolah.teachers'))
            ->assertOk()
            ->assertSee(route('adminsekolah.teachers.export-leadership'), false)
            ->assertSee('Ekspor Kajur')
            ->assertSee('Ekspor Wali Kelas');

        $this->actingAs($this->admin)->get(route('adminsekolah.majors'))
            ->assertOk()
            ->assertSee(route('adminsekolah.teachers.export-leadership'), false)
            ->assertSee('Ekspor Kajur')
            ->assertSee('Ekspor Wali Kelas');

        // 7. Multi-tenant isolation & non-admin authorization guard
        $otherSchool = School::create([
            'name' => 'SMK Lain',
            'npsn' => '88889999',
            'address' => 'Jl. Lain',
            'phone' => '022-999999',
            'email' => 'admin@smklain.sch.id',
            'is_active' => true,
        ]);
        $otherAdmin = User::create([
            'school_id' => $otherSchool->id,
            'name' => 'Admin Lain',
            'email' => 'admin@smklain.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
        $otherCsv = $this->actingAs($otherAdmin)
            ->get(route('adminsekolah.teachers.export-leadership', ['format' => 'csv']))
            ->assertOk()
            ->streamedContent();
        $this->assertStringNotContainsString('Kajur TITL', $otherCsv);

        $this->actingAs($regularTeacher->user)
            ->get(route('adminsekolah.teachers.export-leadership'))
            ->assertStatus(403);
    }

    private function createTeacher(string $name, string $email, string $nip, string $phone): Teacher
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'email' => $email,
            'wa_number' => $phone,
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $user->id,
            'gender' => 'L',
            'phone' => $phone,
        ]);

        return Teacher::create([
            'user_id' => $user->id,
            'nip' => $nip,
        ]);
    }
}
