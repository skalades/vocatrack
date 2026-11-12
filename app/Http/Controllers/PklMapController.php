<?php

namespace App\Http\Controllers;

use App\Services\PklMapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PklMapController extends Controller
{
    public function __construct(
        protected PklMapService $mapService
    ) {}

    protected function getSchoolId(): int
    {
        return Auth::user()->school_id;
    }

    /**
     * Tampilkan peta persebaran PKL untuk Admin Sekolah.
     */
    public function adminMap(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $filters = [
            'date' => $request->get('date', now()->toDateString()),
            'major_id' => $request->get('major_id', 'all'),
            'class_id' => $request->get('class_id', 'all'),
            'status_filter' => $request->get('status_filter', 'all'),
            'search' => $request->get('search'),
        ];

        $mapData = $this->mapService->getMapData($schoolId, $filters);
        $filterOptions = $this->mapService->getFilterOptions($schoolId);

        return view('adminsekolah.map', [
            'mapData' => $mapData,
            'majors' => $filterOptions['majors'],
            'classes' => $filterOptions['classes'],
            'filters' => $filters,
            'roleType' => 'admin',
        ]);
    }

    /**
     * Tampilkan peta persebaran PKL untuk Guru Pembimbing.
     */
    public function guruMap(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Profil guru tidak ditemukan.');

        $filters = [
            'date' => $request->get('date', now()->toDateString()),
            'scope' => $request->get('scope', 'bimbingan'), // bimbingan | all
            'major_id' => $request->get('major_id', 'all'),
            'class_id' => $request->get('class_id', 'all'),
            'status_filter' => $request->get('status_filter', 'all'),
            'search' => $request->get('search'),
        ];

        $teacherId = $filters['scope'] === 'bimbingan' ? $teacher->id : null;

        $mapData = $this->mapService->getMapData($schoolId, $filters, $teacherId);
        $filterOptions = $this->mapService->getFilterOptions($schoolId, $teacher->id);

        return view('teacher.map', [
            'mapData' => $mapData,
            'majors' => $filterOptions['majors'],
            'classes' => $filterOptions['classes'],
            'filters' => $filters,
            'roleType' => 'guru',
            'teacher' => $teacher,
        ]);
    }

    /**
     * Tampilkan peta persebaran PKL untuk Kepala Sekolah (Executive Monitoring).
     */
    public function kepsekMap(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $filters = [
            'date' => $request->get('date', now()->toDateString()),
            'major_id' => $request->get('major_id', 'all'),
            'class_id' => $request->get('class_id', 'all'),
            'status_filter' => $request->get('status_filter', 'all'),
            'search' => $request->get('search'),
        ];

        $mapData = $this->mapService->getMapData($schoolId, $filters);
        $filterOptions = $this->mapService->getFilterOptions($schoolId);

        return view('kepsek.map', [
            'mapData' => $mapData,
            'majors' => $filterOptions['majors'],
            'classes' => $filterOptions['classes'],
            'filters' => $filters,
            'roleType' => 'kepsek',
        ]);
    }
}
