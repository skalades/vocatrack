<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
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
}
