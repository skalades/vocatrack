<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Journal;
use App\Models\JournalPhoto;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    protected function getActiveSpkl()
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(403, 'Profil siswa tidak ditemukan.');
        }

        $spkl = $student->studentPkls()
            ->with(['company', 'teacher.user', 'supervisor.user', 'pklPeriod'])
            ->whereIn('status', ['assigned', 'active'])
            ->latest()
            ->first();

        if (!$spkl) {
            abort(403, 'Anda belum memiliki penugasan PKL aktif.');
        }

        return $spkl;
    }

    public function home()
    {
        $spkl = $this->getActiveSpkl();
        $today = Carbon::today()->toDateString();

        $todayAttendance = Attendance::where('student_pkl_id', $spkl->id)
            ->where('date', $today)
            ->first();

        $todayJournal = Journal::where('student_pkl_id', $spkl->id)
            ->where('date', $today)
            ->first();

        // Monthly stats
        $startOfMonth = Carbon::today()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::today()->endOfMonth()->toDateString();

        $monthAttendances = Attendance::where('student_pkl_id', $spkl->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $stats = [
            'hadir' => $monthAttendances->where('status', 'hadir')->count(),
            'terlambat' => $monthAttendances->where('status', 'terlambat')->count(),
            'izin' => $monthAttendances->where('status', 'izin')->count(),
            'sakit' => $monthAttendances->where('status', 'sakit')->count(),
            'dinas_luar' => $monthAttendances->where('status', 'dinas_luar')->count(),
            'luar_kantor' => $monthAttendances->where('status', 'luar_kantor')->count(),
            'alfa' => $monthAttendances->where('status', 'alfa')->count(),
        ];

        return view('student.home', compact('spkl', 'todayAttendance', 'todayJournal', 'stats'));
    }

    public function attendance()
    {
        $spkl = $this->getActiveSpkl();
        $today = Carbon::today()->toDateString();

        $todayAttendance = Attendance::where('student_pkl_id', $spkl->id)
            ->where('date', $today)
            ->first();

        $history = Attendance::where('student_pkl_id', $spkl->id)
            ->orderBy('date', 'desc')
            ->paginate(15);

        $pendingRequests = AttendanceRequest::where('student_pkl_id', $spkl->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('student.attendance', compact('spkl', 'todayAttendance', 'history', 'pendingRequests'));
    }

    public function checkin(Request $request, AttendanceService $attendanceService)
    {
        $spkl = $this->getActiveSpkl();

        $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'photo' => ['nullable', 'string'], // base64 or file
        ]);

        $photoPath = null;
        if ($request->filled('photo')) {
            $base64Image = $request->input('photo');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]);
                $imageData = base64_decode($base64Image);

                $fileName = 'schools/' . $spkl->school_id . '/attendances/' . date('Y') . '/' . Str::random(20) . '.' . $type;
                Storage::disk('public')->put($fileName, $imageData);
                $photoPath = $fileName;
            }
        }

        try {
            $attendance = $attendanceService->recordCheckIn($spkl, [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'photo_path' => $photoPath,
            ]);

            $statusText = $attendance->status === 'hadir' ? 'Tepat Waktu' : 'Terlambat';
            return back()->with('success', "Check-in berhasil tercatat! Status kehadiran: {$statusText}.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    public function checkout(Request $request, AttendanceService $attendanceService)
    {
        $spkl = $this->getActiveSpkl();

        $request->validate([
            'checkout_notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $attendanceService->recordCheckOut($spkl, [
                'checkout_notes' => $request->checkout_notes,
            ]);

            return back()->with('success', 'Check-out berhasil! Terima kasih atas dedikasi kerja hari ini.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    public function storeRequest(Request $request)
    {
        $spkl = $this->getActiveSpkl();

        $request->validate([
            'type' => ['required', 'in:izin,sakit,dinas_luar,luar_kantor'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:10'],
            'destination_location' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store(
                'schools/' . $spkl->school_id . '/requests/' . date('Y'),
                'public'
            );
        }

        AttendanceRequest::create([
            'school_id' => $spkl->school_id,
            'student_pkl_id' => $spkl->id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'destination_location' => $request->destination_location,
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Permohonan presensi khusus berhasil diajukan dan sedang menunggu verifikasi.');
    }

    public function journals()
    {
        $spkl = $this->getActiveSpkl();
        $today = Carbon::today()->toDateString();

        $journals = Journal::with(['photos', 'approvals.approver'])
            ->where('student_pkl_id', $spkl->id)
            ->orderBy('date', 'desc')
            ->paginate(10);

        $todayJournal = Journal::with('photos')
            ->where('student_pkl_id', $spkl->id)
            ->where('date', $today)
            ->first();

        return view('student.journals', compact('spkl', 'journals', 'todayJournal'));
    }

    public function storeJournal(Request $request)
    {
        $spkl = $this->getActiveSpkl();

        $request->validate([
            'date' => ['required', 'date'],
            'activity_description' => ['required', 'string', 'min:50'],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'action_type' => ['required', 'in:draft,submit'],
        ], [
            'activity_description.min' => 'Uraian kegiatan minimal harus 50 karakter.',
        ]);

        $status = ($request->action_type === 'submit') ? 'submitted' : 'draft';

        $journal = Journal::updateOrCreate(
            [
                'student_pkl_id' => $spkl->id,
                'date' => $request->date,
            ],
            [
                'school_id' => $spkl->school_id,
                'activity_description' => $request->activity_description,
                'status' => $status,
            ]
        );

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('schools/' . $spkl->school_id . '/journals/' . date('Y'), 'public');
                JournalPhoto::create([
                    'journal_id' => $journal->id,
                    'photo_path' => $path,
                ]);
            }
        }

        $msg = ($status === 'submitted')
            ? 'Jurnal berhasil diajukan untuk review Pembimbing DU/DI!'
            : 'Jurnal harian tersimpan sebagai draft.';

        return back()->with('success', $msg);
    }

    public function submitJournal(Journal $journal)
    {
        $spkl = $this->getActiveSpkl();
        if ($journal->student_pkl_id !== $spkl->id) {
            abort(403);
        }

        if (!in_array($journal->status, ['draft', 'revision'])) {
            return back()->with('error', 'Status jurnal saat ini tidak dapat disubmit ulang.');
        }

        $journal->update(['status' => 'submitted']);

        return back()->with('success', 'Jurnal berhasil disubmit dan menunggu approval DU/DI.');
    }

    public function profile()
    {
        $spkl = $this->getActiveSpkl();
        $student = Auth::user()->student;
        $assessment = $spkl->assessment;

        return view('student.profile', compact('spkl', 'student', 'assessment'));
    }
}
