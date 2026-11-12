<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentLinkBatchAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;

    protected User $adminA;
    protected User $teacherAUser;
    protected Teacher $teacherA;

    protected Major $majorRpl;
    protected Major $majorTkj;
    protected SchoolClass $classRpl1;
    protected SchoolClass $classRpl2;

    protected Student $student1;
    protected Student $student2;
    protected Student $student3;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup School A
        $this->schoolA = School::create([
            'name' => 'SMKN 1 Maju Bersama',
            'npsn' => '10101010',
            'email' => 'smkn1@example.com',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '021-112233',
            'is_active' => true,
        ]);

        $this->adminA = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Admin Sekolah A',
            'email' => 'admin@schoola.test',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->teacherAUser = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Guru Budi, M.Kom',
            'email' => 'budi.guru@schoola.test',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $this->teacherA = Teacher::create([
            'user_id' => $this->teacherAUser->id,
            'nip' => '198501012010011001',
        ]);

        $this->majorRpl = Major::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
        ]);

        $this->majorTkj = Major::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Teknik Komputer & Jaringan',
            'code' => 'TKJ',
        ]);

        $this->classRpl1 = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'major_id' => $this->majorRpl->id,
            'name' => 'XII RPL 1',
            'academic_year' => '2026/2027',
        ]);

        $this->classRpl2 = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'major_id' => $this->majorRpl->id,
            'name' => 'XII RPL 2',
            'academic_year' => '2026/2027',
        ]);

        // Student 1 (RPL 1, guided by teacherA)
        $u1 = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Aditya Pratama',
            'email' => 'aditya@student.test',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->student1 = Student::create([
            'user_id' => $u1->id,
            'class_id' => $this->classRpl1->id,
            'nis' => '1001',
            'nisn' => '0010010001',
            'parent_token' => 'token-aditya-12345',
            'is_parent_portal_active' => true,
        ]);

        // Student 2 (RPL 1, no token initially)
        $u2 = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Bagas Wicaksono',
            'email' => 'bagas@student.test',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->student2 = Student::create([
            'user_id' => $u2->id,
            'class_id' => $this->classRpl1->id,
            'nis' => '1002',
            'nisn' => '0010010002',
            'is_parent_portal_active' => true,
        ]);
        // Explicitly set null to simulate legacy student who hasn't been generated
        $this->student2->update(['parent_token' => null]);

        // Student 3 (RPL 2, guided by teacherA)
        $u3 = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Citra Lestari',
            'email' => 'citra@student.test',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->student3 = Student::create([
            'user_id' => $u3->id,
            'class_id' => $this->classRpl2->id,
            'nis' => '1003',
            'nisn' => '0010010003',
            'parent_token' => 'token-citra-67890',
            'is_parent_portal_active' => false,
        ]);

        // Company in School A
        $company = Company::create([
            'school_id' => $this->schoolA->id,
            'name' => 'PT Teknologi Unggul',
            'address' => 'Jl. Industri No. 1',
            'contact_person' => 'Bambang HRD',
            'phone' => '021-998877',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_meters' => 100,
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Periode PKL 2026/2027',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'is_active' => true,
        ]);

        // Assign teacherA to Student 1 & 3
        StudentPkl::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->student1->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $this->teacherA->id,
            'status' => 'active',
        ]);
        StudentPkl::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->student3->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $this->teacherA->id,
            'status' => 'active',
        ]);

        // 2. Setup School B (for isolation tests)
        $this->schoolB = School::create([
            'name' => 'SMKN 2 Bintang Harapan',
            'npsn' => '20202020',
            'email' => 'smkn2@example.com',
            'address' => 'Jl. Merdeka No. 2',
            'phone' => '022-998877',
            'is_active' => true,
        ]);
    }

    public function test_admin_sekolah_can_view_parent_links_page(): void
    {
        $response = $this->actingAs($this->adminA)
            ->get(route('adminsekolah.parent-links'));

        $response->assertStatus(200);
        $response->assertSee('Kelola Link Portal Monitoring Orang Tua');
        $response->assertSee('Aditya Pratama');
        $response->assertSee('Bagas Wicaksono');
        $response->assertSee('Citra Lestari');
        $response->assertSee('XII RPL 1');
        $response->assertSee('Rekayasa Perangkat Lunak');
    }

    public function test_admin_can_filter_by_major_and_rombel(): void
    {
        // Filter specifically to class XII RPL 1
        $response = $this->actingAs($this->adminA)
            ->get(route('adminsekolah.parent-links', [
                'major_id' => $this->majorRpl->id,
                'class_id' => $this->classRpl1->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('Aditya Pratama');
        $response->assertSee('Bagas Wicaksono');
        $response->assertDontSee('Citra Lestari'); // In RPL 2, should not appear
    }

    public function test_admin_can_batch_generate_tokens_for_missing_only(): void
    {
        $oldToken1 = $this->student1->parent_token;
        $this->assertNull($this->student2->parent_token);

        $response = $this->actingAs($this->adminA)
            ->post(route('adminsekolah.parent-links.batch-generate'), [
                'mode' => 'filter',
                'major_id' => $this->majorRpl->id,
                'class_id' => $this->classRpl1->id,
                'force_regenerate' => 0,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->student1->refresh();
        $this->student2->refresh();

        // Student 1 token should remain unchanged (safe mode)
        $this->assertEquals($oldToken1, $this->student1->parent_token);

        // Student 2 should now have a newly generated token
        $this->assertNotNull($this->student2->parent_token);
        $this->assertEquals(32, strlen($this->student2->parent_token));
    }

    public function test_admin_can_force_regenerate_tokens_in_batch(): void
    {
        $oldToken1 = $this->student1->parent_token;

        $response = $this->actingAs($this->adminA)
            ->post(route('adminsekolah.parent-links.batch-generate'), [
                'mode' => 'selected',
                'student_ids' => [$this->student1->id, $this->student3->id],
                'force_regenerate' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->student1->refresh();
        $this->student3->refresh();

        $this->assertNotEquals($oldToken1, $this->student1->parent_token);
        $this->assertNotNull($this->student1->parent_token);
        $this->assertTrue($this->student3->is_parent_portal_active);
    }

    public function test_admin_can_batch_toggle_portal_active_status(): void
    {
        $this->assertTrue($this->student1->is_parent_portal_active);

        // Deactivate student 1
        $response = $this->actingAs($this->adminA)
            ->post(route('adminsekolah.parent-links.batch-toggle'), [
                'student_ids' => [$this->student1->id],
                'is_active' => 0,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->student1->refresh();
        $this->assertFalse($this->student1->is_parent_portal_active);

        // Re-activate student 1 & student 3
        $this->actingAs($this->adminA)
            ->post(route('adminsekolah.parent-links.batch-toggle'), [
                'student_ids' => [$this->student1->id, $this->student3->id],
                'is_active' => 1,
            ]);

        $this->student1->refresh();
        $this->student3->refresh();
        $this->assertTrue($this->student1->is_parent_portal_active);
        $this->assertTrue($this->student3->is_parent_portal_active);
    }

    public function test_admin_can_export_parent_links_to_csv(): void
    {
        $response = $this->actingAs($this->adminA)
            ->get(route('adminsekolah.parent-links.export', [
                'major_id' => $this->majorRpl->id,
                'class_id' => $this->classRpl1->id,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();

        // Check UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Check CSV Headers
        $this->assertStringContainsString('NISN', $content);
        $this->assertStringContainsString('Nama Siswa', $content);
        $this->assertStringContainsString('Tautan Monitoring Orang Tua', $content);
        $this->assertStringContainsString('Pesan Siap Kirim WhatsApp', $content);

        // Check rows
        $this->assertStringContainsString('Aditya Pratama', $content);
        $this->assertStringContainsString('XII RPL 1', $content);
        $this->assertStringContainsString('token-aditya-12345', $content);
        $this->assertStringNotContainsString('Citra Lestari', $content); // In RPL 2
    }

    public function test_guru_pembimbing_can_view_and_manage_parent_links(): void
    {
        // 1. View page in default bimbingan scope
        $response = $this->actingAs($this->teacherAUser)
            ->get(route('guru.parent-links', ['scope' => 'bimbingan']));

        $response->assertStatus(200);
        $response->assertSee('Link Monitoring Orang Tua Siswa');
        $response->assertSee('Aditya Pratama'); // Supervised
        $response->assertSee('Citra Lestari');  // Supervised
        $response->assertDontSee('Bagas Wicaksono'); // Not supervised in bimbingan scope

        // 2. View page in 'all' scope (as wali kelas/kajur)
        $responseAll = $this->actingAs($this->teacherAUser)
            ->get(route('guru.parent-links', ['scope' => 'all']));

        $responseAll->assertStatus(200);
        $responseAll->assertSee('Bagas Wicaksono');

        // 3. Guru batch generates tokens for bimbingan
        $this->actingAs($this->teacherAUser)
            ->post(route('guru.parent-links.batch-generate'), [
                'mode' => 'selected',
                'student_ids' => [$this->student1->id],
                'force_regenerate' => 1,
                'scope' => 'bimbingan',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->student1->refresh();
        $this->assertNotEquals('token-aditya-12345', $this->student1->parent_token);

        // 4. Guru exports CSV for their bimbingan
        $exportResponse = $this->actingAs($this->teacherAUser)
            ->get(route('guru.parent-links.export', [
                'scope' => 'bimbingan',
                'class_id' => $this->classRpl1->id,
            ]));

        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csvText = $exportResponse->streamedContent();

        $this->assertStringContainsString('Aditya Pratama', $csvText);
        $this->assertStringNotContainsString('Bagas Wicaksono', $csvText);
    }

    public function test_single_student_regenerate_and_toggle(): void
    {
        // Admin regenerates single student
        $oldToken = $this->student1->parent_token;
        $this->actingAs($this->adminA)
            ->post(route('adminsekolah.parent-links.regenerate', $this->student1))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->student1->refresh();
        $this->assertNotEquals($oldToken, $this->student1->parent_token);

        // Guru toggles portal for single supervised student
        $this->assertTrue($this->student1->is_parent_portal_active);
        $this->actingAs($this->teacherAUser)
            ->post(route('guru.parent-links.toggle', $this->student1))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->student1->refresh();
        $this->assertFalse($this->student1->is_parent_portal_active);
    }

    public function test_multi_tenant_isolation_prevents_unauthorized_cross_school_access(): void
    {
        // Create user and student in School B
        $userB = User::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Siswa Sekolah B',
            'email' => 'siswab@schoolb.test',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $majorB = Major::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Teknik Komputer & Jaringan',
            'code' => 'TKJ-B',
        ]);
        $classB = SchoolClass::create([
            'school_id' => $this->schoolB->id,
            'major_id' => $majorB->id,
            'name' => 'XII TKJ 1',
            'academic_year' => '2026/2027',
        ]);
        $studentB = Student::create([
            'user_id' => $userB->id,
            'class_id' => $classB->id,
            'nis' => '9999',
            'nisn' => '9999999999',
            'parent_token' => 'token-school-b',
            'is_parent_portal_active' => true,
        ]);

        // Admin from School A attempts to regenerate token for Student from School B -> 403 Forbidden
        $this->actingAs($this->adminA)
            ->post(route('adminsekolah.parent-links.regenerate', $studentB))
            ->assertStatus(403);

        // Guru from School A attempts to toggle portal for Student from School B -> 403 Forbidden
        $this->actingAs($this->teacherAUser)
            ->post(route('guru.parent-links.toggle', $studentB))
            ->assertStatus(403);

        // Student B token must remain untouched
        $studentB->refresh();
        $this->assertEquals('token-school-b', $studentB->parent_token);
    }
}
