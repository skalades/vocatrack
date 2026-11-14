<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function auditLogs(Request $request)
    {
        $query = ActivityLog::with(['school', 'user'])->latest();

        if ($request->filled('module') && $request->module !== 'all') {
            $query->where('module', $request->module);
        }

        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        if ($request->filled('school_id') && $request->school_id !== 'all') {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('school', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")->orWhere('npsn', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->paginate(20)->withQueryString();
        $schools = School::orderBy('name')->get();

        $totalLogsToday = ActivityLog::whereDate('created_at', Carbon::today())->count();
        $impersonationEventsCount = ActivityLog::whereIn('action', ['impersonation_start', 'impersonation_end'])->count();
        $securityEventsCount = ActivityLog::where('module', 'security')->count();
        $totalLogsAll = ActivityLog::count();

        return view('superadmin.audit_logs', compact(
            'logs',
            'schools',
            'totalLogsToday',
            'impersonationEventsCount',
            'securityEventsCount',
            'totalLogsAll'
        ));
    }
}
