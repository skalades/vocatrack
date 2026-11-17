<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCrudAndSecurityEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected Major $major;
    protected SchoolClass $class;
    protected PklPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '11223344',
            'name' => 'SMK Negeri Kejuruan',
            'address' => 'Jl. Merdeka No. 1',
            'phone' => '021-998877',
            'email' => 'admin@smkn.sch.id',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'adminsekolah@smkn.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->major = Major::create([
            'school_id' => $this->school->id,
            'code' => 'TKJ',
            'name' => 'Teknik Komputer dan Jaringan',
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->major->id,
            'name' => 'XII TKJ 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'PKL Semester Ganjil 2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
    }

    public function test_school_settings_rejects_svg_upload(): void
    {
        Storage::fake('public');

        $svgFile = UploadedFile::fake()->create('malicious_logo.svg', 10, 'image/svg+xml');

        $response = $this->actingAs($this->adminUser)
            ->post(route('adminsekolah.settings.update'), [
                'school_name' => 'SMK Negeri Kejuruan Updated',
                'school_address' => 'Jl. Merdeka No. 1',
                'school_phone' => '021-998877',
                'checkin_start' => '06:00',
                'checkin_late' => '07:30',
                'checkin_alfa' => '10:00',
                'checkout_start' => '16:00',
                'journal_backdate_limit' => 3,
                'weight_dudi' => 60,
                'weight_guru' => 40,
                'school_logo' => $svgFile,
            ]);

        $response->assertSessionHasErrors('school_logo');
    }

    public function test_school_settings_rejects_unbalanced_weights(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('adminsekolah.settings.update'), [
                'school_name' => 'SMK Negeri Kejuruan',
                'school_address' => 'Jl. Merdeka No. 1',
                'school_phone' => '021-998877',
                'checkin_start' => '06:00',
                'checkin_late' => '07:30',
                'checkin_alfa' => '10:00',
                'checkout_start' => '16:00',
                'journal_backdate_limit' => 3,
                'weight_dudi' => 70,
                'weight_guru' => 40, // 70 + 40 = 110 != 100
            ]);

        $response->assertSessionHasErrors('weight_dudi');
    }

    public function test_school_settings_accepts_valid_weights(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('adminsekolah.settings.update'), [
                'school_name' => 'SMK Negeri Kejuruan',
                'school_address' => 'Jl. Merdeka No. 1',
                'school_phone' => '021-998877',
                'checkin_start' => '06:00',
                'checkin_late' => '07:30',
                'checkin_alfa' => '10:00',
                'checkout_start' => '16:00',
                'journal_backdate_limit' => 3,
                'weight_dudi' => 65,
                'weight_guru' => 35, // 65 + 35 = 100
            ]);

        $response->assertSessionHas('success');
        $settingDudi = SchoolSetting::where('school_id', $this->school->id)->where('key', 'weight_dudi')->first();
        $this->assertEquals(65, (int) $settingDudi->value);
        $settingGuru = SchoolSetting::where('school_id', $this->school->id)->where('key', 'weight_guru')->first();
        $this->assertEquals(35, (int) $settingGuru->value);
    }

    public function test_major_and_class_lifecycle_and_deletion_protection(): void
    {
        // 1. Cannot delete major while it has classes
        $response = $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.majors.destroy', $this->major->id));
        $response->assertSessionHasErrors('major');
        $this->assertDatabaseHas('majors', ['id' => $this->major->id]);

        // 2. Create student in the class
        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Test',
            'email' => 'siswa@test.com',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
        ]);
        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $studentUser->id,
            'class_id' => $this->class->id,
            'nis' => '1001',
            'nisn' => '9988776655',
            'gender' => 'L',
        ]);

        // 3. Cannot delete class while it has students
        $resClass = $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.classes.destroy', $this->class->id));
        $resClass->assertSessionHasErrors('class');
        $this->assertDatabaseHas('classes', ['id' => $this->class->id]);

        // 4. Update class
        $resClassUpdate = $this->actingAs($this->adminUser)
            ->put(route('adminsekolah.classes.update', $this->class->id), [
                'name' => 'XII TKJ 1 Unggulan',
                'grade_level' => 'XII',
                'academic_year' => '2026/2027',
                'major_id' => $this->major->id,
            ]);
        $resClassUpdate->assertSessionHas('success');
        $this->assertDatabaseHas('classes', ['id' => $this->class->id, 'name' => 'XII TKJ 1 Unggulan']);

        // 5. Delete student first
        $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.students.destroy', $student->id));
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('users', ['id' => $studentUser->id]);

        // 6. Now class can be deleted
        $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.classes.destroy', $this->class->id));
        $this->assertDatabaseMissing('classes', ['id' => $this->class->id]);

        // 7. Now major can be deleted
        $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.majors.destroy', $this->major->id));
        $this->assertDatabaseMissing('majors', ['id' => $this->major->id]);
    }

    public function test_teacher_crud_lifecycle(): void
    {
        // 1. Create Teacher with Majors
        $resStore = $this->actingAs($this->adminUser)
            ->post(route('adminsekolah.teachers.store'), [
                'name' => 'Guru Kejuruan PSPT',
                'email' => 'gurukejuruan@test.com',
                'nip' => '198001012005011002',
                'phone' => '081234567890',
                'major_ids' => [$this->major->id],
            ]);
        $resStore->assertSessionHas('success');

        $storedUser = User::where('email', 'gurukejuruan@test.com')->first();
        $this->assertNotNull($storedUser);
        $teacher = Teacher::where('user_id', $storedUser->id)->first();
        $this->assertNotNull($teacher);
        $this->assertTrue($teacher->majors->contains('id', $this->major->id));

        // 2. Update Teacher
        $resUpdate = $this->actingAs($this->adminUser)
            ->put(route('adminsekolah.teachers.update', $teacher->id), [
                'name' => 'Guru Pembimbing Baru',
                'email' => 'gurubaru@test.com',
                'nip' => '198001012005011099',
                'phone' => '089876543210',
                'major_ids' => [$this->major->id],
            ]);
        $resUpdate->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $storedUser->id,
            'name' => 'Guru Pembimbing Baru',
            'email' => 'gurubaru@test.com',
        ]);
        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'nip' => '198001012005011099',
        ]);
        $this->assertTrue($teacher->fresh()->majors->contains('id', $this->major->id));

        // 3. Delete Teacher
        $resDelete = $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.teachers.destroy', $teacher->id));
        $resDelete->assertSessionHas('success');

        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
        $this->assertDatabaseMissing('users', ['id' => $storedUser->id]);
        $this->assertDatabaseMissing('major_teacher', ['teacher_id' => $teacher->id]);
    }

    public function test_company_destroy_prevented_when_students_assigned(): void
    {
        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Teknologi Mitra',
            'address' => 'Jl. Industri No. 10',
            'contact_person' => 'Budi Santoso',
            'phone' => '0811223344',
            'work_days' => '5_days',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 150,
            'is_active' => true,
        ]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Magang',
            'email' => 'siswamagang@test.com',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
        ]);
        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $studentUser->id,
            'class_id' => $this->class->id,
            'nis' => '1002',
            'nisn' => '1122334455',
        ]);

        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@test.com',
            'password' => bcrypt('password123'),
            'role' => 'guru-pembimbing',
        ]);
        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
            'nip' => '198501012008011003',
        ]);

        $studentPkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'teacher_id' => $teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
        ]);

        // Attempt deleting company that has student assignment
        $response = $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.companies.destroy', $company->id));
        $response->assertSessionHasErrors('company');
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_company_supervisor_store_and_destroy(): void
    {
        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Media Sejahtera',
            'address' => 'Jl. Sudirman No. 10',
            'contact_person' => 'Dedi',
            'phone' => '0812345678',
            'work_days' => '5_days',
        ]);

        // Add supervisor
        $resStore = $this->actingAs($this->adminUser)
            ->post(route('adminsekolah.companies.supervisors.store', $company->id), [
                'name' => 'Mentor Baru DU/DI',
                'email' => 'mentorbaru@dudi.com',
                'position' => 'Senior Technical Lead',
                'phone' => '081299887766',
            ]);
        $resStore->assertSessionHas('success');

        $user = User::where('email', 'mentorbaru@dudi.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('pembimbing-dudi', $user->role);

        $supervisor = \App\Models\Supervisor::where('user_id', $user->id)->first();
        $this->assertNotNull($supervisor);
        $this->assertEquals($company->id, $supervisor->company_id);

        // Delete supervisor
        $resDelete = $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.companies.supervisors.destroy', [$company->id, $supervisor->id]));
        $resDelete->assertSessionHas('success');

        $this->assertDatabaseMissing('supervisors', ['id' => $supervisor->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_unplot_protection_when_records_exist_and_success_when_clean(): void
    {
        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Solusi Mandiri',
            'address' => 'Jl. Merdeka No. 5',
            'contact_person' => 'Siti Nurhaliza',
            'phone' => '0822334455',
            'work_days' => '5_days',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 150,
            'is_active' => true,
        ]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Unplot',
            'email' => 'siswaunplot@test.com',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
        ]);
        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $studentUser->id,
            'class_id' => $this->class->id,
            'nis' => '1003',
            'nisn' => '7788990011',
        ]);

        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing Unplot',
            'email' => 'guruunplot@test.com',
            'password' => bcrypt('password123'),
            'role' => 'guru-pembimbing',
        ]);
        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
            'nip' => '198501012008011004',
        ]);

        $studentPkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'teacher_id' => $teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
        ]);

        // Case 1: Has attendance record -> unplot blocked
        $attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'student_pkl_id' => $studentPkl->id,
            'company_id' => $company->id,
            'date' => '2026-08-01',
            'check_in_time' => '07:55:00',
            'check_in_lat' => -6.200000,
            'check_in_long' => 106.816666,
            'check_in_photo' => 'attendance/photo.jpg',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        $responseBlocked = $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.plotting.destroy', $studentPkl->id));
        $responseBlocked->assertSessionHasErrors('student_pkl');
        $this->assertDatabaseHas('student_pkl', ['id' => $studentPkl->id]);

        // Case 2: Clean record -> unplot succeeds
        $attendance->delete();

        $responseSuccess = $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.plotting.destroy', $studentPkl->id));
        $responseSuccess->assertSessionHas('success');
        $this->assertDatabaseMissing('student_pkl', ['id' => $studentPkl->id]);
    }

    public function test_teacher_supervision_visit_scoped_and_prevented_when_not_assigned(): void
    {
        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Monitoring',
            'email' => 'gurumonitoring@test.com',
            'password' => bcrypt('password123'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
            'nip' => '198801012010011005',
        ]);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Mitra Unassigned',
            'address' => 'Jl. Industri No. 20',
            'contact_person' => 'Joko',
            'phone' => '0833445566',
            'work_days' => '5_days',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 150,
            'is_active' => true,
        ]);

        // Teacher has NO students placed at $company.
        // Attempting to record supervision visit must fail.
        $response = $this->actingAs($teacherUser)
            ->post(route('guru.supervisions.store'), [
                'company_id' => $company->id,
                'pkl_period_id' => $this->period->id,
                'stage' => 'monitoring_1',
                'visit_date' => '2026-08-10',
                'student_progress_summary' => 'Perkembangan siswa sangat baik dan disiplin tinggi',
                'company_feedback' => 'Siswa beradaptasi cepat dengan tim industri',
                'follow_up_agreement' => 'Melanjutkan proyek modul kedua',
            ]);

        $response->assertSessionHasErrors('company_id');
        $this->assertDatabaseMissing('supervision_visits', [
            'teacher_id' => $teacher->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_company_supervisor_update_and_safe_destroy_with_reassign(): void
    {
        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Teknologi Unggul',
            'address' => 'Jl. Asia Afrika No. 1',
            'contact_person' => 'Rina',
            'phone' => '0812345678',
            'work_days' => '5_days',
        ]);

        $user1 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mentor A',
            'email' => 'mentora@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $sup1 = \App\Models\Supervisor::create([
            'user_id' => $user1->id,
            'company_id' => $company->id,
            'position' => 'Senior Developer',
        ]);

        $user2 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mentor B',
            'email' => 'mentorb@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $sup2 = \App\Models\Supervisor::create([
            'user_id' => $user2->id,
            'company_id' => $company->id,
            'position' => 'Tech Lead',
        ]);

        // 1. Test Update Supervisor
        $resUpdate = $this->actingAs($this->adminUser)
            ->put(route('adminsekolah.companies.supervisors.update', [$company->id, $sup1->id]), [
                'name' => 'Mentor A Diperbarui',
                'email' => 'mentora_updated@test.com',
                'position' => 'Head of Engineering',
                'phone' => '0899887766',
                'password' => 'newpassword123',
            ]);
        $resUpdate->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user1->id,
            'name' => 'Mentor A Diperbarui',
            'email' => 'mentora_updated@test.com',
        ]);
        $this->assertDatabaseHas('supervisors', [
            'id' => $sup1->id,
            'position' => 'Head of Engineering',
        ]);

        // 2. Assign student to sup1
        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Penguji',
            'email' => 'gurupenguji@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = \App\Models\Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '9988776655',
        ]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Binaan',
            'email' => 'siswabinaan@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = \App\Models\Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $this->class->id,
            'nis' => '1001',
            'nisn' => '7766554433',
        ]);

        $studentPkl = \App\Models\StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $teacher->id,
            'supervisor_id' => $sup1->id,
            'status' => 'active',
        ]);

        // Deleting sup1 without replacement or detach must fail
        $resFail = $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.companies.supervisors.destroy', [$company->id, $sup1->id]));
        $resFail->assertSessionHasErrors('supervisor');

        // Deleting sup1 with replacement_supervisor_id reassigns student to sup2
        $resSuccess = $this->actingAs($this->adminUser)
            ->delete(route('adminsekolah.companies.supervisors.destroy', [$company->id, $sup1->id]), [
                'replacement_supervisor_id' => $sup2->id,
            ]);
        $resSuccess->assertSessionHas('success');
        $this->assertDatabaseMissing('supervisors', ['id' => $sup1->id]);
        $this->assertDatabaseHas('student_pkl', [
            'id' => $studentPkl->id,
            'supervisor_id' => $sup2->id,
        ]);
    }

    public function test_plotting_reassign_and_batch_reassign(): void
    {
        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Studio Kreatif',
            'address' => 'Jl. Riau No. 10',
            'contact_person' => 'Budi',
            'phone' => '0812345678',
            'work_days' => '5_days',
        ]);

        $teacherUser1 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pertama',
            'email' => 'guru1@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher1 = \App\Models\Teacher::create(['user_id' => $teacherUser1->id, 'nip' => '111111']);

        $teacherUser2 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Kedua',
            'email' => 'guru2@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher2 = \App\Models\Teacher::create(['user_id' => $teacherUser2->id, 'nip' => '222222']);

        $supUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mentor Utama',
            'email' => 'mentorutama@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $sup = \App\Models\Supervisor::create(['user_id' => $supUser->id, 'company_id' => $company->id, 'position' => 'Supervisor']);

        $stUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Kreatif',
            'email' => 'siswakreatif@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $st = \App\Models\Student::create(['user_id' => $stUser->id, 'class_id' => $this->class->id, 'nis' => '1002', 'nisn' => '5544332211']);

        $studentPkl = \App\Models\StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $st->id,
            'company_id' => $company->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $teacher1->id,
            'supervisor_id' => null,
            'status' => 'active',
        ]);

        // 1. Single Reassign Plotting
        $resReassign = $this->actingAs($this->adminUser)
            ->put(route('adminsekolah.plotting.reassign', $studentPkl->id), [
                'teacher_id' => $teacher2->id,
                'supervisor_id' => $sup->id,
            ]);
        $resReassign->assertSessionHas('success');
        $this->assertDatabaseHas('student_pkl', [
            'id' => $studentPkl->id,
            'teacher_id' => $teacher2->id,
            'supervisor_id' => $sup->id,
        ]);

        // 2. Batch Reassign across Company
        $resBatch = $this->actingAs($this->adminUser)
            ->post(route('adminsekolah.companies.reassign-batch', $company->id), [
                'target_type' => 'teacher',
                'teacher_id' => $teacher1->id,
                'filter_teacher_id' => $teacher2->id,
            ]);
        $resBatch->assertSessionHas('success');
        $this->assertDatabaseHas('student_pkl', [
            'id' => $studentPkl->id,
            'teacher_id' => $teacher1->id,
        ]);
    }

    public function test_admin_can_export_students_credentials_filtered_by_major(): void
    {
        $major1 = Major::create(['school_id' => $this->school->id, 'name' => 'Rekayasa Perangkat Lunak', 'code' => 'RPL']);
        $major2 = Major::create(['school_id' => $this->school->id, 'name' => 'Teknik Komputer Jaringan', 'code' => 'TKJ']);

        $class1 = SchoolClass::create(['school_id' => $this->school->id, 'major_id' => $major1->id, 'name' => 'XII RPL 1', 'academic_year' => '2026/2027']);
        $class2 = SchoolClass::create(['school_id' => $this->school->id, 'major_id' => $major2->id, 'name' => 'XII TKJ 1', 'academic_year' => '2026/2027']);

        // Student in Major 1
        $u1 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Ahmad RPL',
            'email' => 'ahmad.rpl@test.sch.id',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $st1 = Student::create([
            'user_id' => $u1->id,
            'class_id' => $class1->id,
            'nis' => '1001',
            'nisn' => '0012345678',
        ]);

        // Student in Major 2
        $u2 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Budi TKJ',
            'email' => 'budi.tkj@test.sch.id',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $st2 = Student::create([
            'user_id' => $u2->id,
            'class_id' => $class2->id,
            'nis' => '1002',
            'nisn' => '0098765432',
        ]);

        // 1. Export XLSX (default Excel)
        $resXlsx = $this->actingAs($this->adminUser)->get(route('adminsekolah.students.export'));
        $resXlsx->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $resXlsx->headers->get('content-disposition'));

        // 2. Export CSV (for direct string assertions)
        $resAll = $this->actingAs($this->adminUser)->get(route('adminsekolah.students.export', ['format' => 'csv']));
        $resAll->assertOk();
        $contentAll = $resAll->streamedContent();
        $this->assertStringContainsString('Ahmad RPL', $contentAll);
        $this->assertStringContainsString('Budi TKJ', $contentAll);
        $this->assertStringContainsString('password', $contentAll);
        $this->assertStringContainsString('0012345678', $contentAll);
        $this->assertStringContainsString('Tautan Monitoring Orang Tua', $contentAll);
        $this->assertStringContainsString('/pantau/', $contentAll);

        // 3. Export filtered by Major 1 (RPL)
        $resRpl = $this->actingAs($this->adminUser)->get(route('adminsekolah.students.export', ['major_id' => $major1->id, 'format' => 'csv']));
        $resRpl->assertOk();
        $contentRpl = $resRpl->streamedContent();
        $this->assertStringContainsString('Ahmad RPL', $contentRpl);
        $this->assertStringNotContainsString('Budi TKJ', $contentRpl);
        $this->assertStringContainsString('Rekayasa Perangkat Lunak', $contentRpl);

        // 4. Multi-tenant: Other school admin cannot see these students
        $otherSchool = School::create([
            'name' => 'SMK Luar',
            'npsn' => '99999999',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '021-123456',
            'email' => 'admin@smkluar.sch.id',
            'is_active' => true,
        ]);
        $otherAdmin = User::create([
            'school_id' => $otherSchool->id,
            'name' => 'Other Admin',
            'email' => 'otheradmin@luar.sch.id',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
        $resOther = $this->actingAs($otherAdmin)->get(route('adminsekolah.students.export', ['format' => 'csv']));
        $resOther->assertOk();
        $contentOther = $resOther->streamedContent();
        $this->assertStringNotContainsString('Ahmad RPL', $contentOther);
        $this->assertStringNotContainsString('Budi TKJ', $contentOther);
    }

    public function test_admin_excel_export_includes_sheets_filtered_per_company(): void
    {
        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing Utama',
            'email' => 'guruutama@test.com',
            'password' => bcrypt('password123'),
            'role' => 'guru-pembimbing',
        ]);
        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
            'nip' => '198501012008011003',
        ]);

        $comp1 = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Solusi Teknologi Digital',
            'address' => 'Jl. Digital No. 1',
            'contact_person' => 'Budi Mentor',
            'phone' => '0812345678',
            'work_days' => '5_days',
            'is_active' => true,
        ]);

        $comp2 = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Mitra Kreatif / Studio *Tech*',
            'address' => 'Jl. Kreatif No. 2',
            'contact_person' => 'Siti Mentor',
            'phone' => '0899999999',
            'work_days' => '5_days',
            'is_active' => true,
        ]);

        // Student 1 -> Company 1
        $u1 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Comp 1',
            'email' => 'siswa1@comp.com',
            'password' => bcrypt('password'),
            'role' => 'siswa',
        ]);
        Profile::create(['user_id' => $u1->id, 'gender' => 'L']);
        $st1 = Student::create([
            'user_id' => $u1->id,
            'class_id' => $this->class->id,
            'nis' => '101',
            'nisn' => '00101',
        ]);
        StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $st1->id,
            'company_id' => $comp1->id,
            'teacher_id' => $teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
        ]);

        // Student 2 -> Company 2
        $u2 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Comp 2',
            'email' => 'siswa2@comp.com',
            'password' => bcrypt('password'),
            'role' => 'siswa',
        ]);
        Profile::create(['user_id' => $u2->id, 'gender' => 'P']);
        $st2 = Student::create([
            'user_id' => $u2->id,
            'class_id' => $this->class->id,
            'nis' => '102',
            'nisn' => '00102',
        ]);
        StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $st2->id,
            'company_id' => $comp2->id,
            'teacher_id' => $teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
        ]);

        // Student 3 -> Belum Diplot (Unassigned)
        $u3 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Belum Diplot',
            'email' => 'siswa3@unassigned.com',
            'password' => bcrypt('password'),
            'role' => 'siswa',
        ]);
        Profile::create(['user_id' => $u3->id, 'gender' => 'L']);
        $st3 = Student::create([
            'user_id' => $u3->id,
            'class_id' => $this->class->id,
            'nis' => '103',
            'nisn' => '00103',
        ]);

        // 1. Export sheets directly without company filter
        $export = new \App\Exports\StudentCredentialsExport($this->school->id);
        $sheets = $export->sheets();

        // Must have: Master Sheet + WA Broadcast + Comp 1 + Comp 2 + Belum Ditempatkan
        $this->assertCount(5, $sheets);
        $titles = array_map(fn($s) => $s->title(), $sheets);
        $this->assertContains('Data Akun Siswa', $titles);
        $this->assertContains('Format Siap Kirim WA', $titles);
        $this->assertContains('PT Solusi Teknologi Digital', $titles);
        $this->assertContains('PT Mitra Kreatif Studio Tech', $titles); // Sanitized from / and *
        $this->assertContains('Belum Ditempatkan', $titles);

        // Verify company sheet content
        $comp1Sheet = collect($sheets)->first(fn($s) => $s->title() === 'PT Solusi Teknologi Digital');
        $this->assertInstanceOf(\App\Exports\Sheets\CompanyStudentsSheet::class, $comp1Sheet);
        $rowsComp1 = $comp1Sheet->collection();
        $this->assertTrue($rowsComp1->contains(fn($r) => in_array('Siswa Comp 1', (array) $r)));
        $this->assertFalse($rowsComp1->contains(fn($r) => in_array('Siswa Comp 2', (array) $r)));

        // Verify Belum Ditempatkan sheet content
        $unassignedSheet = collect($sheets)->first(fn($s) => $s->title() === 'Belum Ditempatkan');
        $this->assertInstanceOf(\App\Exports\Sheets\CompanyStudentsSheet::class, $unassignedSheet);
        $rowsUnassigned = $unassignedSheet->collection();
        $this->assertTrue($rowsUnassigned->contains(fn($r) => in_array('Siswa Belum Diplot', (array) $r)));
        $this->assertFalse($rowsUnassigned->contains(fn($r) => in_array('Siswa Comp 1', (array) $r)));

        // 2. Export filtered specifically by Company 1
        $exportComp1 = new \App\Exports\StudentCredentialsExport($this->school->id, ['company_id' => $comp1->id]);
        $sheetsComp1 = $exportComp1->sheets();
        $this->assertCount(3, $sheetsComp1); // Master + WA + Comp 1
        $titlesComp1 = array_map(fn($s) => $s->title(), $sheetsComp1);
        $this->assertContains('PT Solusi Teknologi Digital', $titlesComp1);
        $this->assertNotContains('PT Mitra Kreatif Studio Tech', $titlesComp1);
        $this->assertNotContains('Belum Ditempatkan', $titlesComp1);

        // 3. Export filtered by unassigned
        $exportUnassigned = new \App\Exports\StudentCredentialsExport($this->school->id, ['company_id' => 'unassigned']);
        $sheetsUnassigned = $exportUnassigned->sheets();
        $this->assertCount(3, $sheetsUnassigned); // Master + WA + Belum Ditempatkan
        $titlesUnassigned = array_map(fn($s) => $s->title(), $sheetsUnassigned);
        $this->assertContains('Belum Ditempatkan', $titlesUnassigned);
        $this->assertNotContains('PT Solusi Teknologi Digital', $titlesUnassigned);

        // 4. HTTP Route download works with company_id filter
        $response = $this->actingAs($this->adminUser)->get(route('adminsekolah.students.export', ['company_id' => $comp1->id]));
        $response->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        // 5. HTTP Route students view accepts company_id filter
        $resView = $this->actingAs($this->adminUser)->get(route('adminsekolah.students', ['company_id' => $comp1->id]));
        $resView->assertOk();
        $resView->assertSee('Siswa Comp 1');
        $resView->assertDontSee('Siswa Comp 2');
    }

    public function test_sanitize_sheet_title_edge_cases(): void
    {
        $usedTitles = ['data akun siswa', 'format siap kirim wa'];

        // Test forbidden characters
        $title1 = \App\Exports\StudentCredentialsExport::sanitizeSheetTitle('PT [Inovasi] / Tech : Super * (CV)', $usedTitles);
        $this->assertStringNotContainsString('/', $title1);
        $this->assertStringNotContainsString('[', $title1);
        $this->assertStringNotContainsString(']', $title1);
        $this->assertStringNotContainsString('*', $title1);
        $this->assertStringNotContainsString(':', $title1);

        // Test length limit (max 31 characters)
        $veryLongTitle = 'PT Perusahaan Multinasional Sangat Panjang Sekali Sekali Banget';
        $title2 = \App\Exports\StudentCredentialsExport::sanitizeSheetTitle($veryLongTitle, $usedTitles);
        $this->assertLessThanOrEqual(31, mb_strlen($title2));

        // Test collision handling
        $title3 = \App\Exports\StudentCredentialsExport::sanitizeSheetTitle($veryLongTitle, $usedTitles);
        $this->assertLessThanOrEqual(31, mb_strlen($title3));
        $this->assertNotEquals($title2, $title3);
        $this->assertStringEndsWith('(2)', $title3);
    }

    public function test_admin_excel_export_teachers_includes_sheets_filtered_per_major(): void
    {
        $majorRpl = Major::create(['school_id' => $this->school->id, 'name' => 'Rekayasa Perangkat Lunak', 'code' => 'RPL']);
        $majorTkj = Major::create(['school_id' => $this->school->id, 'name' => 'Teknik Komputer Jaringan', 'code' => 'TKJ']);

        // Teacher 1 -> RPL
        $u1 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru RPL Murni',
            'email' => 'gururpl@test.com',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
        ]);
        Profile::create(['user_id' => $u1->id, 'gender' => 'L', 'phone' => '0811111111']);
        $t1 = Teacher::create(['user_id' => $u1->id, 'nip' => '198001012010011001']);
        $t1->majors()->attach($majorRpl->id);

        // Teacher 2 -> TKJ
        $u2 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru TKJ Murni',
            'email' => 'gurutkj@test.com',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
        ]);
        Profile::create(['user_id' => $u2->id, 'gender' => 'P', 'phone' => '0822222222']);
        $t2 = Teacher::create(['user_id' => $u2->id, 'nip' => '198202022012022002']);
        $t2->majors()->attach($majorTkj->id);

        // Teacher 3 -> RPL & TKJ
        $u3 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Dua Jurusan',
            'email' => 'gurudua@test.com',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
        ]);
        Profile::create(['user_id' => $u3->id, 'gender' => 'L', 'phone' => '0833333333']);
        $t3 = Teacher::create(['user_id' => $u3->id, 'nip' => '198503032015031003']);
        $t3->majors()->attach([$majorRpl->id, $majorTkj->id]);

        // Teacher 4 -> Lintas Jurusan (no major attached)
        $u4 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Lintas Jurusan',
            'email' => 'gurulintas@test.com',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
        ]);
        Profile::create(['user_id' => $u4->id, 'gender' => 'P', 'phone' => '0844444444']);
        $t4 = Teacher::create(['user_id' => $u4->id, 'nip' => '199004042020042004']);

        // 1. Export all teachers
        $export = new \App\Exports\TeacherCredentialsExport($this->school->id);
        $sheets = $export->sheets();

        // Must contain: Data Akun Guru + Format Siap Kirim WA + RPL + TKJ + Lintas Jurusan (total 5 sheets)
        $this->assertCount(5, $sheets);
        $titles = array_map(fn($s) => $s->title(), $sheets);
        $this->assertContains('Data Akun Guru', $titles);
        $this->assertContains('Format Siap Kirim WA', $titles);
        $this->assertContains('RPL - Rekayasa Perangkat Lunak', $titles);
        $this->assertContains('TKJ - Teknik Komputer Jaringan', $titles);
        $this->assertContains('Lintas Jurusan', $titles);

        // Verify RPL Sheet content: Teacher 1 & 3, NOT Teacher 2 & 4
        $rplSheet = collect($sheets)->first(fn($s) => $s->title() === 'RPL - Rekayasa Perangkat Lunak');
        $this->assertInstanceOf(\App\Exports\Sheets\MajorTeachersSheet::class, $rplSheet);
        $rplRows = $rplSheet->collection();
        $this->assertTrue($rplRows->contains(fn($r) => in_array('Guru RPL Murni', (array) $r)));
        $this->assertTrue($rplRows->contains(fn($r) => in_array('Guru Dua Jurusan', (array) $r)));
        $this->assertFalse($rplRows->contains(fn($r) => in_array('Guru TKJ Murni', (array) $r)));
        $this->assertFalse($rplRows->contains(fn($r) => in_array('Guru Lintas Jurusan', (array) $r)));

        // Verify Lintas Sheet content: Teacher 4 only
        $lintasSheet = collect($sheets)->first(fn($s) => $s->title() === 'Lintas Jurusan');
        $this->assertInstanceOf(\App\Exports\Sheets\MajorTeachersSheet::class, $lintasSheet);
        $lintasRows = $lintasSheet->collection();
        $this->assertTrue($lintasRows->contains(fn($r) => in_array('Guru Lintas Jurusan', (array) $r)));
        $this->assertFalse($lintasRows->contains(fn($r) => in_array('Guru RPL Murni', (array) $r)));

        // 2. Export filtered specifically by RPL
        $exportRpl = new \App\Exports\TeacherCredentialsExport($this->school->id, ['major' => $majorRpl->id]);
        $sheetsRpl = $exportRpl->sheets();
        $this->assertCount(3, $sheetsRpl); // Master + WA + RPL
        $titlesRpl = array_map(fn($s) => $s->title(), $sheetsRpl);
        $this->assertContains('RPL - Rekayasa Perangkat Lunak', $titlesRpl);
        $this->assertNotContains('TKJ - Teknik Komputer Jaringan', $titlesRpl);
        $this->assertNotContains('Lintas Jurusan', $titlesRpl);

        // 3. Export filtered specifically by Lintas Jurusan
        $exportLintas = new \App\Exports\TeacherCredentialsExport($this->school->id, ['major' => 'lintas']);
        $sheetsLintas = $exportLintas->sheets();
        $this->assertCount(3, $sheetsLintas); // Master + WA + Lintas Jurusan
        $titlesLintas = array_map(fn($s) => $s->title(), $sheetsLintas);
        $this->assertContains('Lintas Jurusan', $titlesLintas);
        $this->assertNotContains('RPL - Rekayasa Perangkat Lunak', $titlesLintas);

        // 4. HTTP Route download works (XLSX)
        $resXlsx = $this->actingAs($this->adminUser)->get(route('adminsekolah.teachers.export'));
        $resXlsx->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $resXlsx->headers->get('content-disposition'));

        // 5. HTTP Route download works (CSV)
        $resCsv = $this->actingAs($this->adminUser)->get(route('adminsekolah.teachers.export', ['format' => 'csv']));
        $resCsv->assertOk();
        $csvContent = $resCsv->streamedContent();
        $this->assertStringContainsString('Guru RPL Murni', $csvContent);
        $this->assertStringContainsString('Guru TKJ Murni', $csvContent);
        $this->assertStringContainsString('198001012010011001', $csvContent);
        $this->assertStringContainsString('password', $csvContent);

        // 6. Multi-tenant isolation: other school admin cannot export these teachers
        $otherSchool = School::create([
            'name' => 'SMK Lain',
            'npsn' => '88887777',
            'address' => 'Jl. Lain',
            'phone' => '022-123456',
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
        $resOtherCsv = $this->actingAs($otherAdmin)->get(route('adminsekolah.teachers.export', ['format' => 'csv']));
        $resOtherCsv->assertOk();
        $otherCsvContent = $resOtherCsv->streamedContent();
        $this->assertStringNotContainsString('Guru RPL Murni', $otherCsvContent);
        $this->assertStringNotContainsString('Guru TKJ Murni', $otherCsvContent);
    }
}
