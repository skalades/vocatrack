<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\LearningObjective;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupPivotTableWithoutIdTest extends TestCase
{
    use RefreshDatabase;

    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDir = storage_path('framework/testing/backups_' . uniqid());
        File::ensureDirectoryExists($this->backupDir);

        $dir = $this->backupDir;
        $this->app->instance(BackupService::class, new class($dir) extends BackupService {
            public function __construct(string $dir)
            {
                $this->backupDir = $dir;
            }
        });
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupDir);
        parent::tearDown();
    }

    public function test_backup_handles_pivot_tables_without_id_column(): void
    {
        $school = School::create([
            'name' => 'SMK Pivot Test',
            'npsn' => '99998888',
            'email' => 'pivot@smk.sch.id',
            'address' => 'Jl. Pendidikan No. 10',
            'phone' => '021-123456',
            'is_active' => true,
        ]);

        $major = Major::create([
            'school_id' => $school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa Pivot',
            'email' => 'siswa.pivot@smk.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'nis' => '99901',
            'nisn' => '0099901',
        ]);

        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'Periode 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru.pivot@smk.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = \App\Models\Teacher::create(['user_id' => $teacherUser->id, 'nip' => '19880101']);

        $company = \App\Models\Company::create([
            'school_id' => $school->id,
            'name' => 'PT Test Industry',
            'address' => 'Jl. Industri',
            'contact_person' => 'PIC Budi',
            'phone' => '0812345678',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'teacher_id' => $teacher->id,
            'pkl_period_id' => $period->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spkl->id,
            'date' => '2026-11-18',
            'activity_description' => 'Mempelajari arsitektur Laravel',
            'status' => 'approved',
        ]);

        $lo = LearningObjective::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'code' => 'TP-01',
            'title' => 'Memahami Eloquent Relationships',
            'description' => 'Memahami Eloquent Relationships dan skema basis data',
            'is_active' => true,
        ]);

        // Insert into pivot table journal_learning_objective (which has no 'id' column)
        DB::table('journal_learning_objective')->insert([
            'journal_id' => $journal->id,
            'learning_objective_id' => $lo->id,
        ]);

        $backupService = app(BackupService::class);
        $result = $backupService->createDatabaseBackup('test_pivot_backup');

        $this->assertFileExists($result['path']);
        $this->assertGreaterThan(0, $result['size']);
        $this->assertArrayHasKey('journal_learning_objective', $result['row_counts']);
        $this->assertSame(1, $result['row_counts']['journal_learning_objective']);

        // Now test restore to ensure roundtrip works
        $restoreResult = $backupService->restoreDatabaseBackup($result['filename']);
        $this->assertNotEmpty($restoreResult['restored_at']);

        // Verify data in pivot table exists after restore
        $this->assertDatabaseHas('journal_learning_objective', [
            'journal_id' => $journal->id,
            'learning_objective_id' => $lo->id,
        ]);
    }
}
