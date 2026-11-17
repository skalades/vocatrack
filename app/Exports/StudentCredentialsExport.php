<?php

namespace App\Exports;

use App\Exports\Sheets\StudentAccountsDataSheet;
use App\Exports\Sheets\StudentBroadcastWhatsappSheet;
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

        $query = Student::whereHas('user', fn($q) => $q->where('school_id', $this->schoolId))
            ->with(['user.profile', 'class.major', 'activePkl.company']);

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
            'downloaded_at' => now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];

        return [
            new StudentAccountsDataSheet($students, $meta),
            new StudentBroadcastWhatsappSheet($students, $meta),
        ];
    }
}
