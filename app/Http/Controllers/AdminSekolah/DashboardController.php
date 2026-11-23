<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use Carbon\Carbon;

class DashboardController extends BaseAdminSekolahController
{
    public function dashboard()
    {
        $schoolId = $this->getSchoolId();
        $totalStudents = Student::whereHas('user', fn($q) => $q->where('school_id', $schoolId))->count();
        $totalTeachers = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId))->count();
        $totalCompanies = Company::where('school_id', $schoolId)->count();
        $activePkl = StudentPkl::where('school_id', $schoolId)->where('status', 'active')->count();

        $today = Carbon::today()->toDateString();
        $countsByStatus = Attendance::where('school_id', $schoolId)
            ->where('date', $today)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $attendanceStats = [
            'hadir' => (int) ($countsByStatus['hadir'] ?? 0),
            'terlambat' => (int) ($countsByStatus['terlambat'] ?? 0),
            'izin' => (int) ($countsByStatus['izin'] ?? 0),
            'sakit' => (int) ($countsByStatus['sakit'] ?? 0),
            'alfa' => (int) ($countsByStatus['alfa'] ?? 0),
        ];

        $recentAnnouncements = Announcement::where('school_id', $schoolId)
            ->with(['author', 'major'])
            ->pinnedFirst()
            ->limit(4)
            ->get();

        return view('adminsekolah.dashboard', compact(
            'totalStudents',
            'totalTeachers',
            'totalCompanies',
            'activePkl',
            'attendanceStats',
            'recentAnnouncements'
        ));
    }
}
