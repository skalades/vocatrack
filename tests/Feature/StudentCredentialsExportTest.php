<?php

namespace Tests\Feature;

use App\Exports\Sheets\CompanyStudentsSheet;
use App\Exports\Sheets\StudentAccountsDataSheet;
use App\Exports\Sheets\StudentBroadcastWhatsappSheet;
use App\Exports\StudentCredentialsExport;
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
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class StudentCredentialsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_credentials_export_includes_guru_pembimbing_column_and_renders_clean_xlsx(): void
    {
        $school = School::create([
            'name' => 'SMK Negeri 2 Garut',
            'npsn' => '20209298',
            'address' => 'Jl. Suherman No. 90',
            'phone' => '0262-123456',
            'email' => 'info@smkn2test.sch.id',
            'is_active' => true,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin.export@smkn2test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $major = Major::create([
            'school_id' => $school->id,
            'code' => 'DKV',
            'name' => 'Desain Komunikasi Visual',
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'name' => 'XII DKV 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Asep Nasrudin, S.T.',
            'email' => 'asep.export@smkn2test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id]);
        $teacher->majors()->attach($major->id);

        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'JOEJOE CREATIVE',
            'address' => 'Garut',
            'contact_person' => 'Budi',
            'phone' => '08123456789',
        ]);

        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'PKL 2026/2027',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'is_active' => true,
        ]);

        $placedUser = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa Terplotting',
            'email' => 'siswa.placed@smkn2test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $placedStudent = Student::create([
            'user_id' => $placedUser->id,
            'class_id' => $class->id,
            'nis' => '242510538',
            'nisn' => '242510538',
            'parent_token' => Str::random(32),
        ]);
        StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $placedStudent->id,
            'company_id' => $company->id,
            'teacher_id' => $teacher->id,
            'pkl_period_id' => $period->id,
            'status' => 'active',
        ]);

        $unplacedUser = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa Belum Diplot',
            'email' => 'siswa.unplaced@smkn2test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Student::create([
            'user_id' => $unplacedUser->id,
            'class_id' => $class->id,
            'nis' => '242510575',
            'nisn' => '242510575',
            'parent_token' => Str::random(32),
        ]);

        $export = new StudentCredentialsExport($school->id);
        $sheets = $export->sheets();
        $this->assertInstanceOf(StudentAccountsDataSheet::class, $sheets[0]);
        $this->assertInstanceOf(StudentBroadcastWhatsappSheet::class, $sheets[1]);
        $this->assertInstanceOf(CompanyStudentsSheet::class, $sheets[2]);

        $binary = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $tmpPath = tempnam(sys_get_temp_dir(), 'student_export_') . '.xlsx';
        file_put_contents($tmpPath, $binary);

        try {
            $spreadsheet = IOFactory::load($tmpPath);
            $accountsSheet = $spreadsheet->getSheetByName('Data Akun Siswa');
            $this->assertNotNull($accountsSheet);

            $headers = $accountsSheet->rangeToArray('A4:M4')[0];
            $this->assertSame([
                'No',
                'Nama Lengkap Siswa',
                'NISN (Username Login)',
                'NIS',
                'Jurusan',
                'Kelas / Rombel',
                'Tempat PKL / Perusahaan',
                'Guru Pembimbing',
                'Email Akun',
                'Password Default',
                'Status Akun',
                'Tautan Login Siswa',
                'Tautan Monitoring Orang Tua',
            ], $headers);

            $dataRows = collect($accountsSheet->rangeToArray('A5:M6'));
            $placedRow = $dataRows->first(fn ($row) => $row[1] === 'Siswa Terplotting');
            $unplacedRow = $dataRows->first(fn ($row) => $row[1] === 'Siswa Belum Diplot');

            $this->assertNotNull($placedRow);
            $this->assertSame('JOEJOE CREATIVE', $placedRow[6]);
            $this->assertSame('Asep Nasrudin, S.T.', $placedRow[7]);
            $this->assertSame('siswa.placed@smkn2test.sch.id', $placedRow[8]);
            $this->assertSame('Aktif', $placedRow[10]);

            $this->assertNotNull($unplacedRow);
            $this->assertSame('Belum Diplot', $unplacedRow[6]);
            $this->assertSame('-', $unplacedRow[7]);
            $this->assertSame('siswa.unplaced@smkn2test.sch.id', $unplacedRow[8]);

            $waSheet = $spreadsheet->getSheetByName('Format Siap Kirim WA');
            $this->assertNotNull($waSheet);
            $waRows = collect($waSheet->rangeToArray('B5:E6'));
            $placedWa = $waRows->first(fn ($row) => $row[0] === 'Siswa Terplotting');
            $unplacedWa = $waRows->first(fn ($row) => $row[0] === 'Siswa Belum Diplot');

            $this->assertStringContainsString('• Guru Pembimbing: Asep Nasrudin, S.T.', (string) $placedWa[3]);
            $this->assertStringNotContainsString('• Guru Pembimbing:', (string) $unplacedWa[3]);
        } finally {
            @unlink($tmpPath);
        }

        $response = $this->actingAs($admin)->get(route('adminsekolah.students.export'));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
