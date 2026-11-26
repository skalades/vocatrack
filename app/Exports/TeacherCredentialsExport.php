<?php

namespace App\Exports;

use App\Exports\Sheets\MajorTeachersSheet;
use App\Exports\Sheets\TeacherAccountsDataSheet;
use App\Exports\Sheets\TeacherBroadcastWhatsappSheet;
use App\Models\Major;
use App\Models\School;
use App\Models\Teacher;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TeacherCredentialsExport implements WithMultipleSheets
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

        $majorFilterName = 'Semua Jurusan';
        if (!empty($this->filters['major']) && $this->filters['major'] !== 'all') {
            if ($this->filters['major'] === 'lintas') {
                $majorFilterName = 'Lintas Jurusan';
            } else {
                $major = Major::where('school_id', $this->schoolId)->find($this->filters['major']);
                if ($major) {
                    $majorFilterName = $major->name;
                }
            }
        }

        $query = Teacher::whereHas('user', fn($q) => $q->where('school_id', $this->schoolId))
            ->with(['user.profile', 'majors', 'headedMajors', 'studentPkls'])
            ->applyAdminFilters($this->filters['search'] ?? null, $this->filters['major'] ?? null);

        $teachers = $query->latest('teachers.id')->get();

        $meta = [
            'school_name' => $schoolName,
            'major_name' => $majorFilterName,
            'downloaded_at' => now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];

        $sheets = [
            new TeacherAccountsDataSheet($teachers, $meta),
            new TeacherBroadcastWhatsappSheet($teachers, $meta),
        ];

        $usedTitles = [
            mb_strtolower('Data Akun Guru'),
            mb_strtolower('Format Siap Kirim WA'),
        ];

        // 1. If filtered specifically by major
        if (!empty($this->filters['major']) && $this->filters['major'] !== 'all') {
            if ($this->filters['major'] === 'lintas') {
                $title = self::sanitizeSheetTitle('Lintas Jurusan', $usedTitles);
                $sheets[] = new MajorTeachersSheet(null, $teachers, $title, $meta);
            } else {
                $selectedMajor = Major::where('school_id', $this->schoolId)->with('headTeacher.user')->find($this->filters['major']);
                if ($selectedMajor) {
                    $rawTitle = $selectedMajor->code ? "{$selectedMajor->code} - {$selectedMajor->name}" : $selectedMajor->name;
                    $title = self::sanitizeSheetTitle($rawTitle, $usedTitles);
                    $sheets[] = new MajorTeachersSheet($selectedMajor, $teachers, $title, $meta);
                }
            }
            return $sheets;
        }

        // 2. If all majors: Partition teachers per major
        $schoolMajors = Major::where('school_id', $this->schoolId)->with('headTeacher.user')->orderBy('name')->get();

        foreach ($schoolMajors as $major) {
            $majorTeachers = $teachers->filter(function ($teacher) use ($major) {
                return $teacher->majors->contains('id', $major->id);
            });

            if ($majorTeachers->isNotEmpty()) {
                $rawTitle = $major->code ? "{$major->code} - {$major->name}" : $major->name;
                $sheetTitle = self::sanitizeSheetTitle($rawTitle, $usedTitles);
                $sheets[] = new MajorTeachersSheet($major, $majorTeachers, $sheetTitle, $meta);
            }
        }

        // Interdisciplinary teachers without specific major
        $lintasTeachers = $teachers->filter(function ($teacher) {
            return $teacher->majors->isEmpty();
        });

        if ($lintasTeachers->isNotEmpty()) {
            $lintasTitle = self::sanitizeSheetTitle('Lintas Jurusan', $usedTitles);
            $sheets[] = new MajorTeachersSheet(null, $lintasTeachers, $lintasTitle, $meta);
        }

        return $sheets;
    }

    public static function sanitizeSheetTitle(string $title, array &$usedTitles = []): string
    {
        // Remove invalid Excel sheet characters: \ / ? * : [ ]
        $clean = preg_replace('~[\\\\\/\?\*\:\[\]]~', ' ', $title);
        $clean = trim(preg_replace('/\s+/', ' ', (string) $clean));

        if (empty($clean)) {
            $clean = 'Jurusan';
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
