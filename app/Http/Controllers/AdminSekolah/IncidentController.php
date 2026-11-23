<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Models\IncidentReport;
use App\Services\IncidentReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentController extends BaseAdminSekolahController
{
    public function incidents(Request $request, IncidentReportService $incidentService)
    {
        $schoolId = $this->getSchoolId();
        $incidents = $incidentService->getIncidentsForSchool($schoolId, $request->only(['status', 'urgency']));

        $stats = [
            'total' => IncidentReport::where('school_id', $schoolId)->count(),
            'open' => IncidentReport::where('school_id', $schoolId)->whereIn('status', ['submitted', 'in_progress'])->count(),
            'critical' => IncidentReport::where('school_id', $schoolId)->where('urgency', 'critical')->count(),
            'resolved' => IncidentReport::where('school_id', $schoolId)->where('status', 'resolved')->count(),
        ];

        return view('adminsekolah.incidents', compact('incidents', 'stats'));
    }

    public function followupIncident(Request $request, IncidentReport $report, IncidentReportService $incidentService)
    {
        $schoolId = $this->getSchoolId();
        abort_if($report->school_id !== $schoolId, 403);

        $validated = $request->validate([
            'action_note' => ['required', 'string', 'max:1000'],
            'status' => ['nullable', 'in:in_progress,resolved,dismissed'],
        ]);

        $incidentService->addFollowup($report, Auth::user(), $validated['action_note'], $validated['status'] ?? null);

        return back()->with('success', 'Tindak lanjut laporan kendala berhasil dicatat.');
    }
}
