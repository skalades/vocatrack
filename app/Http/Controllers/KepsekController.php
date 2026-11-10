<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\StudentPkl;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KepsekController extends Controller
{
    protected function getSchoolId(): int
    {
        return Auth::user()->school_id;
    }

    public function dashboard()
    {
        $schoolId = $this->getSchoolId();

        $totalActivePkl = StudentPkl::where('school_id', $schoolId)->where('status', 'active')->count();
        $totalCompanies = Company::where('school_id', $schoolId)->count();

        $totalAttendances = Attendance::where('school_id', $schoolId)->count();
        $presentAttendances = Attendance::where('school_id', $schoolId)->whereIn('status', ['hadir', 'terlambat'])->count();
        $attendanceRate = $totalAttendances > 0 ? round(($presentAttendances / $totalAttendances) * 100, 1) : 100;

        $totalJournals = Journal::where('school_id', $schoolId)->count();
        $approvedJournals = Journal::where('school_id', $schoolId)->where('status', 'approved')->count();
        $journalApprovalRate = $totalJournals > 0 ? round(($approvedJournals / $totalJournals) * 100, 1) : 100;

        $majors = Major::where('school_id', $schoolId)->withCount('classes')->get();

        return view('kepsek.dashboard', compact(
            'totalActivePkl',
            'totalCompanies',
            'attendanceRate',
            'journalApprovalRate',
            'majors'
        ));
    }

    public function rekap(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $month = $request->get('month', Carbon::today()->format('Y-m'));

        $classes = SchoolClass::where('school_id', $schoolId)->get();
        $selectedClassId = $request->get('class_id', $classes->first()?->id);

        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $studentPkls = StudentPkl::where('school_id', $schoolId)
            ->whereHas('student', fn($q) => $q->where('class_id', $selectedClassId))
            ->with(['student.user', 'attendances' => function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
            }])
            ->get();

        return view('kepsek.rekap', compact(
            'classes',
            'selectedClassId',
            'month',
            'daysInMonth',
            'studentPkls',
            'startOfMonth'
        ));
    }

    public function exportRekap(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $month = $request->get('month', Carbon::today()->format('Y-m'));
        $selectedClassId = $request->get('class_id');

        $schoolClass = SchoolClass::where('school_id', $schoolId)->findOrFail($selectedClassId);

        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $studentPkls = StudentPkl::where('school_id', $schoolId)
            ->whereHas('student', fn($q) => $q->where('class_id', $selectedClassId))
            ->with(['student.user', 'attendances' => function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
            }])
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=Rekap_Presensi_{$schoolClass->name}_{$month}.csv",
        ];

        $callback = function () use ($studentPkls, $daysInMonth, $month) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM
            fputs($file, "\xEF\xBB\xBF");

            $header = ['NISN', 'Nama Siswa'];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $header[] = (string) $d;
            }
            $header = array_merge($header, ['Total Hadir', 'Terlambat', 'Izin', 'Sakit', 'Dinas Luar', 'Luar Kantor', 'Alfa']);
            fputcsv($file, $header);

            foreach ($studentPkls as $spkl) {
                $row = [
                    $spkl->student->nisn,
                    $spkl->student->user->name,
                ];

                $attMap = $spkl->attendances->keyBy(fn($a) => Carbon::parse($a->date)->day);

                $h = 0; $t = 0; $i = 0; $s = 0; $dl = 0; $lk = 0; $a = 0;

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    if (isset($attMap[$d])) {
                        $code = match ($attMap[$d]->status) {
                            'hadir' => 'H',
                            'terlambat' => 'T',
                            'izin' => 'I',
                            'sakit' => 'S',
                            'dinas_luar' => 'DL',
                            'luar_kantor' => 'LK',
                            'alfa' => 'A',
                            default => '-'
                        };
                        $row[] = $code;
                        if ($code === 'H') $h++;
                        elseif ($code === 'T') $t++;
                        elseif ($code === 'I') $i++;
                        elseif ($code === 'S') $s++;
                        elseif ($code === 'DL') $dl++;
                        elseif ($code === 'LK') $lk++;
                        elseif ($code === 'A') $a++;
                    } else {
                        $row[] = '-';
                    }
                }

                $row[] = $h;
                $row[] = $t;
                $row[] = $i;
                $row[] = $s;
                $row[] = $dl;
                $row[] = $lk;
                $row[] = $a;

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
