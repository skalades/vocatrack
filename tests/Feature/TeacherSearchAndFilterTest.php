<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherSearchAndFilterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $adminUser;
    private Major $rpl;
    private Major $dkv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20209999',
            'name' => 'SMK Negeri 2 Garut Uji',
            'email' => 'info@smkn2garut-test.sch.id',
            'address' => 'Garut',
            'phone' => '0262',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin PKL',
            'email' => 'admin@smkn2garut-test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->rpl = Major::create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
        ]);

        $this->dkv = Major::create([
            'school_id' => $this->school->id,
            'name' => 'Desain Komunikasi Visual',
            'code' => 'DKV',
        ]);
    }

    private function createTeacher(string $name, string $email, string $nip, ?Major $major = null, ?string $phone = null): Teacher
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

        if ($phone) {
            Profile::create([
                'user_id' => $user->id,
                'phone' => $phone,
            ]);
        }

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'nip' => $nip,
        ]);

        if ($major) {
            $teacher->majors()->attach($major->id);
        }

        return $teacher;
    }

    public function test_teacher_search_finds_teachers_beyond_first_pagination_page_case_insensitively(): void
    {
        // Buat 18 guru baru sehingga target "Herdi Permana" (dibuat pertama) berada di halaman 2 (paginate 15).
        $herdi = $this->createTeacher('Herdi Permana, S.Kom', 'herdi@smkn2garut-test.sch.id', '198801012015011001', $this->rpl, '081234567890');
        $herdi->forceFill(['created_at' => now()->subDay()])->save();

        for ($i = 1; $i <= 18; $i++) {
            $this->createTeacher(
                "Guru Penguji {$i}",
                "guru{$i}@smkn2garut-test.sch.id",
                '19900000000000' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                $this->dkv
            );
        }

        // Tanpa pencarian, halaman 1 hanya menampilkan 15 guru terbaru (Herdi belum tampak di halaman 1).
        $page1 = $this->actingAs($this->adminUser)->get(route('adminsekolah.teachers'));
        $page1->assertOk()->assertDontSee('Herdi Permana, S.Kom');

        // Pencarian server-side case-insensitive menemukan Herdi lintas halaman.
        $searchRes = $this->actingAs($this->adminUser)->get(route('adminsekolah.teachers', ['search' => 'HERDI']));
        $searchRes->assertOk()
            ->assertSee('Herdi Permana, S.Kom')
            ->assertSee('1 Guru Ditemukan')
            ->assertDontSee('Guru Penguji 18');

        // Pencarian berdasarkan kode jurusan atau nomor HP juga berfungsi.
        $this->actingAs($this->adminUser)
            ->get(route('adminsekolah.teachers', ['search' => '081234567890']))
            ->assertOk()
            ->assertSee('Herdi Permana, S.Kom');

        // Pencarian + filter jurusan tetap dipertahankan bersama.
        $this->actingAs($this->adminUser)
            ->get(route('adminsekolah.teachers', ['search' => 'herdi', 'major' => $this->dkv->id]))
            ->assertOk()
            ->assertSee('Guru Tidak Ditemukan')
            ->assertDontSee('Herdi Permana, S.Kom');
    }

    public function test_teacher_search_renders_server_side_form_inputs(): void
    {
        $this->createTeacher('Asep Nasrudin', 'asep@smkn2garut-test.sch.id', '19850101', $this->dkv);

        $response = $this->actingAs($this->adminUser)
            ->get(route('adminsekolah.teachers', ['search' => 'asep']));

        $response->assertOk()
            ->assertSee('name="search"', false)
            ->assertSee('value="asep"', false)
            ->assertSee('id="teacher-filter-form"', false);
    }

    public function test_teacher_search_and_searchable_selectors_work_across_plotting_students_majors_companies_and_kajur(): void
    {
        $teacherA = $this->createTeacher('Zamzam, M.Pd', 'zamzam@smkn2garut-test.sch.id', '19810101', $this->dkv);
        $teacherB = $this->createTeacher('Cecep Supriatna, S.T.', 'cecep@smkn2garut-test.sch.id', '19820202', $this->rpl);
        $this->rpl->update(['head_teacher_id' => $teacherB->id]);

        $classRpl = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->rpl->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'PKL 2026/2027',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'is_active' => true,
        ]);

        $companyA = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Nusantara Audio',
            'address' => 'Garut',
            'contact_person' => 'PIC A',
            'phone' => '0262111111',
            'work_days' => '5_days',
        ]);

        $companyB = Company::create([
            'school_id' => $this->school->id,
            'name' => 'CV Digital Karsa',
            'address' => 'Bandung',
            'contact_person' => 'PIC B',
            'phone' => '0222222222',
            'work_days' => '5_days',
        ]);

        $studentUserA = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Satu',
            'email' => 'siswa1@smkn2garut-test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $studentA = Student::create([
            'user_id' => $studentUserA->id,
            'class_id' => $classRpl->id,
            'nis' => '24250001',
            'nisn' => '0090000001',
        ]);
        StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $studentA->id,
            'company_id' => $companyA->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacherA->id,
            'supervisor_id' => null,
            'status' => 'active',
        ]);

        $studentUserB = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Dua',
            'email' => 'siswa2@smkn2garut-test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $studentB = Student::create([
            'user_id' => $studentUserB->id,
            'class_id' => $classRpl->id,
            'nis' => '24250002',
            'nisn' => '0090000002',
        ]);
        StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $studentB->id,
            'company_id' => $companyB->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacherB->id,
            'supervisor_id' => null,
            'status' => 'active',
        ]);

        // 1. Plotting Admin Sekolah: pencarian kata kunci berdasarkan nama guru & NIS + filter teacher_id + searchable select
        $plottingRes = $this->actingAs($this->adminUser)
            ->get(route('adminsekolah.plotting', ['search' => 'Zamzam']))
            ->assertOk()
            ->assertSee('data-searchable-select', false);
        $this->assertSame(['Siswa Satu'], $plottingRes->viewData('studentPkls')->pluck('student.user.name')->all());

        // 2. Master Siswa Admin Sekolah: pencarian kata kunci berdasarkan nama guru pembimbing
        $this->actingAs($this->adminUser)
            ->get(route('adminsekolah.students', ['search' => 'Zamzam']))
            ->assertOk()
            ->assertSee('Siswa Satu')
            ->assertDontSee('Siswa Dua');

        // 3. Jurusan & Kelas Admin Sekolah: dropdown Kajur & Wali Kelas memiliki pencarian guru
        $this->actingAs($this->adminUser)
            ->get(route('adminsekolah.majors'))
            ->assertOk()
            ->assertSee('data-searchable-select', false);

        // 4. Perusahaan DU/DI Admin Sekolah: pencarian perusahaan berdasarkan guru + pencarian modal guru
        $this->actingAs($this->adminUser)
            ->get(route('adminsekolah.companies', ['search' => 'Zamzam']))
            ->assertOk()
            ->assertSee('PT Nusantara Audio')
            ->assertDontSee('CV Digital Karsa')
            ->assertSee('id="modal-teacher-search"', false)
            ->assertSee('data-searchable-select', false);

        // 5. Siswa Kajur: pencarian nama guru + filter teacher_id + searchable select
        $kajurUser = $teacherB->user;
        $this->actingAs($kajurUser)
            ->get(route('kajur.students', ['search' => 'Zamzam']))
            ->assertOk()
            ->assertSee('Siswa Satu')
            ->assertDontSee('Siswa Dua')
            ->assertSee('data-searchable-select', false);

        $this->actingAs($kajurUser)
            ->get(route('kajur.students', ['teacher_id' => $teacherB->id]))
            ->assertOk()
            ->assertSee('Siswa Dua')
            ->assertDontSee('Siswa Satu');
    }
}
