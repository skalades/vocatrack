<?php

namespace App\Exports;

use App\Exports\Sheets\CompanyStudentsSheet;
use App\Exports\Sheets\StudentAccountsDataSheet;
use App\Exports\Sheets\StudentBroadcastWhatsappSheet;
use App\Models\Company;
use App\Models\Major;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentCredentialsExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected int $schoolId,
        protected array $filters = []
    ) {}

    public function sheets(): array
    {
        $school = School::find($this->schoolId);
        $schoolName = $school?->name ?? 'Sekolah';

        $majorName = 'Semua Jurusan';
        if (!empty($this->filters['major_id']) && $this->filters['major_id'] !== 'all') {
            $major = Major::where('school_id', $this->schoolId)->find($this->filters['major_id']);
            if ($major) {
                $majorName = $major->name;
            }
        }

        $className = 'Semua Kelas';
        if (!empty($this->filters['class_id']) && $this->filters['class_id'] !== 'all') {
            $class = SchoolClass::where('school_id', $this->schoolId)->find($this->filters['class_id']);
            if ($class) {
                $className = $class->name;
            }
        }

        $companyFilterName = 'Semua Perusahaan';
        if (!empty($this->filters['company_id']) && $this->filters['company_id'] !== 'all') {
            if ($this->filters['company_id'] === 'unassigned') {
                $companyFilterName = 'Belum Ditempatkan';
            } else {
                $company = Company::where('school_id', $this->schoolId)->find($this->filters['company_id']);
                if ($company) {
                    $companyFilterName = $company->name;
                }
            }
        }

        $query = Student::whereHas('user', fn($q) => $q->where('school_id', $this->schoolId))
            ->with([
                'user.profile',
                'class.major',
                'activePkl.company',
                'activePkl.teacher.user',
                'activePkl.supervisor.user',
            ]);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhereHas('class', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if (!empty($this->filters['major_id']) && $this->filters['major_id'] !== 'all') {
            $query->whereHas('class', fn($cq) => $cq->where('major_id', $this->filters['major_id']));
        }

        if (!empty($this->filters['class_id']) && $this->filters['class_id'] !== 'all') {
            $query->where('class_id', $this->filters['class_id']);
        }

        if (!empty($this->filters['company_id']) && $this->filters['company_id'] !== 'all') {
            if ($this->filters['company_id'] === 'unassigned') {
                $query->whereDoesntHave('activePkl');
            } else {
                $companyId = $this->filters['company_id'];
                $query->whereHas('activePkl', fn($pq) => $pq->where('company_id', $companyId));
            }
        }

        $students = $query->latest('students.id')->get();

        // Ensure parent_token is populated for each student
        foreach ($students as $student) {
            if (empty($student->parent_token)) {
                $student->parent_token = Str::random(32);
                $student->save();
            }
        }

        $meta = [
            'school_name' => $schoolName,
            'major_name' => $majorName,
            'class_name' => $className,
            'company_name' => $companyFilterName,
            'downloaded_at' => now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];

        $sheets = [
            new StudentAccountsDataSheet($students, $meta),
            new StudentBroadcastWhatsappSheet($students, $meta),
        ];

        // Track used sheet titles for uniqueness (case-insensitive in Excel)
        $usedTitles = [
            mb_strtolower('Data Akun Siswa'),
            mb_strtolower('Format Siap Kirim WA'),
        ];

        // Filter and partition per perusahaan
        $assignedStudents = $students->filter(fn($s) => $s->activePkl && $s->activePkl->company);
        $unassignedStudents = $students->reject(fn($s) => $s->activePkl && $s->activePkl->company);

        // Group assigned students by company ID
        $groupedByCompany = $assignedStudents->groupBy(fn($s) => $s->activePkl->company_id);

        // Sort company groups alphabetically by company name
        $sortedCompanyGroups = $groupedByCompany->sortBy(function ($companyStudents) {
            return mb_strtolower($companyStudents->first()?->activePkl?->company?->name ?? '');
        });

        foreach ($sortedCompanyGroups as $companyStudents) {
            $company = $companyStudents->first()?->activePkl?->company;
            if (!$company) {
                continue;
            }

            $rawTitle = $company->name;
            $sheetTitle = self::sanitizeSheetTitle($rawTitle, $usedTitles);

            $sheets[] = new CompanyStudentsSheet(
                $company,
                $companyStudents,
                $sheetTitle,
                $meta
            );
        }

        // Include "Belum Ditempatkan" sheet if there are students not assigned to any company
        if ($unassignedStudents->isNotEmpty()) {
            $unassignedTitle = self::sanitizeSheetTitle('Belum Ditempatkan', $usedTitles);
            $sheets[] = new CompanyStudentsSheet(
                null,
                $unassignedStudents,
                $unassignedTitle,
                $meta
            );
        }

        return $sheets;
    }

    public static function sanitizeSheetTitle(string $title, array &$usedTitles = []): string
    {
        // Remove invalid Excel sheet characters: \ / ? * : [ ]
        $clean = preg_replace('~[\\\\\/\?\*\:\[\]]~', ' ', $title);
        $clean = trim(preg_replace('/\s+/', ' ', (string) $clean));

        if (empty($clean)) {
            $clean = 'Perusahaan';
        }

        $base = mb_substr($clean, 0, 31);
        $finalTitle = $base;
        $counter = 2;

        while (in_array(mb_strtolower($finalTitle), $usedTitles, true)) {
            $suffix = " ({$counter})";
            $allowedBaseLength = 31 - mb_strlen($suffix);
            $finalTitle = mb_substr($base, 0, max(1, $allowedBaseLength)) . $suffix;
            $counter++;
        }

        $usedTitles[] = mb_strtolower($finalTitle);

        return $finalTitle;
    }
}
