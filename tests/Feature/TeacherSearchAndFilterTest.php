<?php

namespace Tests\Feature;

use App\Models\Major;
use App\Models\Profile;
use App\Models\School;
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
}
