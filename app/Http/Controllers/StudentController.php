<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Certificate;
use App\Models\IncidentReport;
use App\Models\Journal;
use App\Models\JournalPhoto;
use App\Models\StudentPkl;
use App\Services\AnnouncementService;
use App\Services\AttendanceService;
use App\Services\CertificateService;
use App\Services\IncidentReportService;
use App\Services\SecureFileService;
use App\Services\StorageQuotaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    /**
     * Resolve StudentPkl assignment.
     * When $mustBeActive is true, strictly requires 'assigned' or 'active'.
     * When $mustBeActive is false, allows 'completed' so alumni/finishing students can view certificates and records.
     */
    protected function getStudentPkl(bool $mustBeActive = false): ?StudentPkl
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(403, 'Profil siswa tidak ditemukan.');
        }

        $query = $student->studentPkls()
            ->with(['student.class.major', 'company', 'teacher.user', 'supervisor.user', 'pklPeriod'])
            ->latest();

        if ($mustBeActive) {
            return $query->whereIn('status', ['assigned', 'active'])->first();
        }

        return $query->first();
    }

    /**
     * Strict active spkl accessor for active write operations.
     */
    protected function getActiveSpkl(): StudentPkl
    {
        $spkl = $this->getStudentPkl(true);
        if (!$spkl) {
            abort(403, 'Anda belum memiliki penugasan PKL aktif.');
        }

        return $spkl;
    }

    public function home(AnnouncementService $announcementService, AttendanceService $attendanceService)
    {
        $spkl = $this->getStudentPkl(false);
        $student = Auth::user()->student?->load('class.major');
        $today = Carbon::today()->toDateString();

        $todayAttendance = null;
        $todayJournal = null;
        $dayStatus = null;
        $stats = [
            'hadir' => 0,
            'terlambat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'dinas_luar' => 0,
            'luar_kantor' => 0,
            'alfa' => 0,
            'total_masuk' => 0,
        ];

        if ($spkl) {
            $todayAttendance = Attendance::where('student_pkl_id', $spkl->id)
                ->where('date', $today)
                ->first();

            $todayJournal = Journal::where('student_pkl_id', $spkl->id)
                ->where('date', $today)
                ->first();

            $dayStatus = $attendanceService->getDayAttendanceStatus($spkl);

            // Monthly stats
            $startOfMonth = Carbon::today()->startOfMonth()->toDateString();
            $endOfMonth = Carbon::today()->endOfMonth()->toDateString();

            $monthAttendances = Attendance::where('student_pkl_id', $spkl->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->get();

            $hadir = $monthAttendances->where('status', 'hadir')->count();
            $terlambat = $monthAttendances->where('status', 'terlambat')->count();
            $dinasLuar = $monthAttendances->where('status', 'dinas_luar')->count();
            $luarKantor = $monthAttendances->where('status', 'luar_kantor')->count();

            $stats = [
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'izin' => $monthAttendances->where('status', 'izin')->count(),
                'sakit' => $monthAttendances->where('status', 'sakit')->count(),
                'dinas_luar' => $dinasLuar,
                'luar_kantor' => $luarKantor,
                'alfa' => $monthAttendances->where('status', 'alfa')->count(),
                'total_masuk' => $hadir + $terlambat + $dinasLuar + $luarKantor,
            ];
        }

        // Pengumuman tersaring berdasarkan peran & jurusan siswa
        $announcements = $announcementService->getLatestForUser(Auth::user(), 6);

        $dayNumber = 27;
        $currentWeek = 6;
        if ($spkl && $spkl->start_date) {
            $startDate = Carbon::parse($spkl->start_date);
            $dayNumber = max(1, (int) $startDate->diffInDays(Carbon::today()) + 1);
            $currentWeek = max(1, (int) ceil($dayNumber / 7));
        }

        $totalDays = ($stats['total_masuk'] ?? 0) + ($stats['alfa'] ?? 0) + ($stats['izin'] ?? 0) + ($stats['sakit'] ?? 0);
        $attendanceRate = $totalDays > 0 ? (int) round((($stats['total_masuk'] ?? 0) / $totalDays) * 100) : 94;

        $completedJournalsCount = $spkl ? Journal::where('student_pkl_id', $spkl->id)->whereIn('status', ['submitted', 'review_guru', 'approved'])->count() : 18;
        $targetJournalsCount = max($completedJournalsCount, 20);

        return view('student.home', compact(
            'spkl', 'student', 'todayAttendance', 'todayJournal', 'stats', 'announcements', 
            'dayStatus', 'dayNumber', 'currentWeek', 'attendanceRate', 'completedJournalsCount', 'targetJournalsCount'
        ));
    }

    public function attendance(AttendanceService $attendanceService)
    {
        $spkl = $this->getStudentPkl(false);
        $student = Auth::user()->student?->load('class.major');
        $today = Carbon::today()->toDateString();

        $todayAttendance = null;
        $history = collect();
        $pendingRequests = collect();
        $dayStatus = null;

        $dayNumber = 27;
        if ($spkl && $spkl->start_date) {
            $startDate = Carbon::parse($spkl->start_date);
            $dayNumber = max(1, (int) $startDate->diffInDays(Carbon::today()) + 1);
        }

        if ($spkl) {
            $todayAttendance = Attendance::where('student_pkl_id', $spkl->id)
                ->where('date', $today)
                ->first();

            $history = Attendance::where('student_pkl_id', $spkl->id)
                ->orderBy('date', 'desc')
                ->paginate(15);

            $pendingRequests = AttendanceRequest::where('student_pkl_id', $spkl->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            $dayStatus = $attendanceService->getDayAttendanceStatus($spkl);
        }

        return view('student.attendance', compact('spkl', 'student', 'todayAttendance', 'history', 'pendingRequests', 'dayStatus', 'dayNumber'));
    }

    public function checkin(Request $request, AttendanceService $attendanceService)
    {
        $spkl = $this->getStudentPkl(true);
        if (!$spkl) {
            return back()->withErrors(['time' => 'Anda tidak memiliki penugasan PKL aktif untuk melakukan presensi.']);
        }

        $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
            'is_mocked' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'string'], // base64 swafoto or empty in simulated test
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->has('is_within_radius') && filter_var($request->input('is_within_radius'), FILTER_VALIDATE_BOOLEAN) === false) {
            if (!$request->filled('reason') || strlen(trim($request->reason)) < 5) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'Anda berada di luar radius lokasi penugasan DU/DI. Wajib menyertakan alasan penugasan luar radius.',
                        'errors' => ['reason' => ['Anda berada di luar radius lokasi penugasan DU/DI. Wajib menyertakan alasan penugasan luar radius.']]
                    ], 422);
                }
                throw ValidationException::withMessages([
                    'reason' => 'Anda berada di luar radius lokasi penugasan DU/DI. Wajib menyertakan alasan penugasan luar radius.',
                ]);
            }
        }

        $photoPath = null;
        if ($request->filled('photo')) {
            $base64Image = $request->input('photo');
            
            // Klien sudah mengompres swafoto (maks 800px, ~250KB). Server hanya melakukan
            // pengecekan murah (panjang string + header gambar) tanpa resize/re-encode.
            // 1.4MB base64 ≈ 1MB biner — longgar untuk perangkat lama, ketat untuk payload liar.
            if (strlen($base64Image) > 1400 * 1024) {
                return back()->withErrors(['photo' => 'Ukuran foto swafoto terlalu besar (maksimal 1MB). Silakan ambil ulang foto.']);
            }

            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $typeMatch)) {
                $rawType = strtolower($typeMatch[1]);
                $allowedMimeMap = [
                    'jpeg' => 'jpg',
                    'jpg'  => 'jpg',
                    'png'  => 'png',
                    'webp' => 'webp',
                ];

                if (!isset($allowedMimeMap[$rawType])) {
                    return back()->withErrors(['photo' => 'Format foto tidak valid. Gunakan format JPG, PNG, atau WEBP.']);
                }

                $extension = $allowedMimeMap[$rawType];
                $cleanBase64 = substr($base64Image, strpos($base64Image, ',') + 1);
                $imageData = base64_decode($cleanBase64, true);

                if ($imageData === false) {
                    return back()->withErrors(['photo' => 'Data gambar swafoto rusak atau tidak valid.']);
                }

                // Verifikasi isi berkas benar-benar gambar (jangan percaya prefix data URI dari klien).
                $realMimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $imageInfo = @getimagesizefromstring($imageData);
                if ($imageInfo === false || !isset($realMimeMap[$imageInfo['mime'] ?? ''])) {
                    return back()->withErrors(['photo' => 'Data gambar swafoto rusak atau tidak valid.']);
                }
                $extension = $realMimeMap[$imageInfo['mime']];

                // Tolak dimensi berlebih (dibaca dari header, tanpa decode piksel).
                if (($imageInfo[0] ?? 0) > 2000 || ($imageInfo[1] ?? 0) > 2000) {
                    return back()->withErrors(['photo' => 'Resolusi foto swafoto terlalu besar. Silakan ambil ulang foto.']);
                }

                $fileSize = strlen($imageData);
                if (!app(StorageQuotaService::class)->canUpload($spkl->school, $fileSize)) {
                    return back()->withErrors(['photo' => 'Kapasitas penyimpanan sekolah Anda telah penuh. Tidak dapat mengunggah foto swafoto. Hubungi admin sekolah.']);
                }

                $fileName = 'schools/' . $spkl->school_id . '/attendances/' . date('Y') . '/' . Str::random(24) . '.' . $extension;
                app(SecureFileService::class)->putContents($fileName, $imageData);
                app(StorageQuotaService::class)->recordUpload($spkl->school, $fileSize);
                $photoPath = $fileName;
            }
        }

        try {
            $attendance = $attendanceService->recordCheckIn($spkl, [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy,
                'is_mocked' => $request->boolean('is_mocked'),
                'photo_path' => $photoPath,
                'reason' => $request->reason,
            ]);

            $statusText = $attendance->status === 'hadir' ? 'Tepat Waktu' : 'Terlambat';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Check-in berhasil tercatat! Status kehadiran: {$statusText}.",
                    'data' => $attendance,
                ]);
            }
            return back()->with('success', "Check-in berhasil tercatat! Status kehadiran: {$statusText}.");
        } catch (ValidationException $e) {
            // Check-in ditolak: hapus selfie yang sudah terunggah dan kembalikan kuotanya.
            if ($photoPath) {
                app(SecureFileService::class)->delete($photoPath);
                app(StorageQuotaService::class)->recordDeletion($spkl->school, $fileSize ?? 0);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withErrors($e->errors());
        }
    }

    public function checkout(Request $request, AttendanceService $attendanceService)
    {
        $spkl = $this->getStudentPkl(true);
        if (!$spkl) {
            return back()->withErrors(['checkout' => 'Anda tidak memiliki penugasan PKL aktif.']);
        }

        $request->validate([
            'checkout_notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $attendanceService->recordCheckOut($spkl, [
                'checkout_notes' => $request->checkout_notes,
            ]);

            return back()->with('success', 'Check-out berhasil! Terima kasih atas dedikasi kerja hari ini.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    public function storeRequest(Request $request)
    {
        $spkl = $this->getStudentPkl(true);
        if (!$spkl) {
            return back()->withErrors(['request' => 'Anda tidak memiliki penugasan PKL aktif untuk mengajukan permohonan.']);
        }

        $request->validate([
            'type' => ['required', 'in:izin,sakit,dinas_luar,luar_kantor'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:10'],
            'destination_location' => ['nullable', 'required_if:type,dinas_luar', 'string', 'max:255'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'destination_location.required_if' => 'Lokasi tujuan / penugasan wajib diisi untuk dinas luar.',
            'reason.min' => 'Alasan permohonan minimal 10 karakter.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentFile = $request->file('attachment');
            $fileSize = $attachmentFile->getSize();
            if (!app(StorageQuotaService::class)->canUpload($spkl->school, $fileSize)) {
                return back()->withErrors(['attachment' => 'Kapasitas penyimpanan sekolah Anda telah penuh. Tidak dapat mengunggah bukti lampiran. Hubungi admin sekolah.'])->withInput();
            }

            $attachmentPath = app(SecureFileService::class)->storeUploadedFile($attachmentFile, 'schools/' . $spkl->school_id . '/requests/' . date('Y'));
            app(StorageQuotaService::class)->recordUpload($spkl->school, $fileSize);
        }

        return DB::transaction(function () use ($spkl, $request, $attachmentPath) {
            // Check for overlapping pending or approved requests to prevent duplicates
            $overlapping = AttendanceRequest::where('student_pkl_id', $spkl->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                        ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                        ->orWhere(function ($q) use ($request) {
                            $q->where('start_date', '<=', $request->start_date)
                                ->where('end_date', '>=', $request->end_date);
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($overlapping) {
                return back()->withErrors(['start_date' => 'Sudah ada permohonan presensi aktif atau pending pada rentang tanggal tersebut.']);
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

            return back()->with('success', 'Permohonan presensi khusus berhasil diajukan dan sedang menunggu verifikasi pembimbing.');
        });
    }

    public function journals(Request $request)
    {
        $spkl = $this->getStudentPkl(false);
        $student = Auth::user()->student?->load('class.major');
        $today = Carbon::today()->toDateString();

        $currentWeek = 6;
        if ($spkl && $spkl->start_date) {
            $startDate = Carbon::parse($spkl->start_date);
            $dayNumber = max(1, (int) $startDate->diffInDays(Carbon::today()) + 1);
            $currentWeek = max(1, (int) ceil($dayNumber / 7));
        }

        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weeklyDays = [];
        $dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'];
        for ($i = 0; $i < 5; $i++) {
            $d = (clone $startOfWeek)->addDays($i);
            $weeklyDays[] = [
                'short' => $dayNames[$i],
                'date_num' => $d->format('d'),
                'date_full' => $d->toDateString(),
                'is_today' => $d->isToday(),
            ];
        }

        $journals = collect();
        $todayJournal = null;
        $statusCounts = [
            'all' => 0,
            'approved' => 0,
            'review' => 0,
            'revision' => 0,
            'draft' => 0,
        ];

        if ($spkl) {
            $query = Journal::with(['photos', 'approvals.approver'])
                ->where('student_pkl_id', $spkl->id);

            // Filter berdasarkan status
            if ($request->filled('status') && $request->status !== 'all') {
                if ($request->status === 'review') {
                    $query->whereIn('status', ['submitted', 'review_guru']);
                } else {
                    $query->where('status', $request->status);
                }
            }

            // Filter pencarian teks uraian kegiatan
            if ($request->filled('search')) {
                $query->where('activity_description', 'like', '%' . trim($request->search) . '%');
            }

            // Filter bulan (Format: YYYY-MM)
            if ($request->filled('month')) {
                $query->where('date', 'like', $request->month . '%');
            }

            // Hitung total status untuk filter pills
            $baseQuery = Journal::where('student_pkl_id', $spkl->id);
            $statusCounts = [
                'all' => (clone $baseQuery)->count(),
                'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
                'review' => (clone $baseQuery)->whereIn('status', ['submitted', 'review_guru'])->count(),
                'revision' => (clone $baseQuery)->where('status', 'revision')->count(),
                'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            ];

            // Pagination responsif
            $perPage = $request->integer('per_page', 5);
            $journals = $query->orderBy('date', 'desc')
                ->paginate($perPage)
                ->withQueryString();

            $todayJournal = Journal::with('photos')
                ->where('student_pkl_id', $spkl->id)
                ->where('date', $today)
                ->first();
        }

        return view('student.journals', compact('spkl', 'student', 'journals', 'todayJournal', 'statusCounts', 'currentWeek', 'weeklyDays'));
    }

    public function storeJournal(Request $request)
    {
        $spkl = $this->getStudentPkl(true);
        if (!$spkl) {
            return $this->journalErrorResponse($request, ['date' => 'Anda tidak memiliki penugasan PKL aktif untuk mengisi jurnal.']);
        }

        $desc = $request->input('activity_description') ?? $request->input('description');
        if ($desc) {
            $request->merge(['activity_description' => $desc]);
        }

        $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'activity_description' => ['required', 'string', 'min:50'],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'action_type' => ['nullable', 'in:draft,submit'],
            'is_draft' => ['nullable', 'boolean'],
        ], [
            'date.before_or_equal' => 'Tanggal jurnal tidak boleh melebihi hari ini.',
            'activity_description.min' => 'Uraian kegiatan minimal harus 50 karakter.',
        ]);

        $isDraft = $request->boolean('is_draft') || ($request->input('action_type') === 'draft');
        $status = $isDraft ? 'draft' : 'submitted';

        try {
            $journal = DB::transaction(function () use ($spkl, $request, $status) {
                $existing = Journal::where('student_pkl_id', $spkl->id)
                    ->whereDate('date', $request->date)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if (in_array($existing->status, ['review_guru', 'approved'])) {
                        throw ValidationException::withMessages([
                            'date' => 'Jurnal untuk tanggal ini sudah dalam tahap review pembimbing atau telah disetujui, sehingga tidak dapat disunting kembali.',
                        ]);
                    }

                    $existing->update([
                        'activity_description' => $request->activity_description,
                        'status' => $status,
                    ]);

                    return $existing;
                }

                try {
                    return Journal::create([
                        'school_id' => $spkl->school_id,
                        'student_pkl_id' => $spkl->id,
                        'date' => $request->date,
                        'activity_description' => $request->activity_description,
                        'status' => $status,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Integrity constraint violation')) {
                        $existing = Journal::where('student_pkl_id', $spkl->id)
                            ->whereDate('date', $request->date)
                            ->lockForUpdate()
                            ->firstOrFail();

                        if (in_array($existing->status, ['review_guru', 'approved'])) {
                            throw ValidationException::withMessages([
                                'date' => 'Jurnal untuk tanggal ini sudah dalam tahap review atau telah disetujui.',
                            ]);
                        }

                        $existing->update([
                            'activity_description' => $request->activity_description,
                            'status' => $status,
                        ]);

                        return $existing;
                    }
                    throw $e;
                }
            });
        } catch (ValidationException $e) {
            return $this->journalErrorResponse($request, $e->errors());
        }

        if ($request->hasFile('photos')) {
            $totalSize = 0;
            foreach ($request->file('photos') as $photo) {
                $totalSize += $photo->getSize();
            }

            if (!app(StorageQuotaService::class)->canUpload($spkl->school, $totalSize)) {
                return $this->journalErrorResponse($request, ['photos' => 'Kapasitas penyimpanan sekolah Anda telah penuh. Tidak dapat mengunggah foto jurnal. Hubungi admin sekolah.']);
            }

            foreach ($request->file('photos') as $photo) {
                $path = app(SecureFileService::class)->storeUploadedFile($photo, 'schools/' . $spkl->school_id . '/journals/' . date('Y'));
                JournalPhoto::create([
                    'journal_id' => $journal->id,
                    'photo_path' => $path,
                ]);
            }
            app(StorageQuotaService::class)->recordUpload($spkl->school, $totalSize);
        }

        $msg = ($status === 'submitted')
            ? 'Jurnal berhasil diajukan untuk review Pembimbing DU/DI!'
            : 'Jurnal harian tersimpan sebagai draft.';

        // Sinkronisasi jurnal offline dari PWA mengirim Accept: application/json.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $msg,
                'journal_id' => $journal->id,
                'status' => $journal->status,
                'date' => $journal->date?->toDateString(),
            ]);
        }

        return redirect()->route('student.journals')->with('success', $msg);
    }

    /**
     * Respons galat jurnal: JSON 422 untuk sinkronisasi offline, redirect-back untuk form biasa.
     */
    protected function journalErrorResponse(Request $request, array $errors)
    {
        if ($request->expectsJson()) {
            $first = collect($errors)->flatten()->first();

            return response()->json(['message' => $first, 'errors' => $errors], 422);
        }

        return back()->withErrors($errors)->withInput();
    }

    public function submitJournal(Journal $journal)
    {
        $spkl = $this->getStudentPkl(true);
        if (!$spkl || $journal->student_pkl_id !== $spkl->id) {
            abort(403);
        }

        return DB::transaction(function () use ($journal) {
            $lockedJournal = Journal::where('id', $journal->id)->lockForUpdate()->firstOrFail();

            if (!in_array($lockedJournal->status, ['draft', 'revision'])) {
                return back()->with('error', 'Status jurnal saat ini tidak dapat disubmit ulang.');
            }

            $lockedJournal->update(['status' => 'submitted']);

            return back()->with('success', 'Jurnal berhasil disubmit dan menunggu approval DU/DI.');
        });
    }

    public function destroyJournal(Journal $journal)
    {
        $spkl = $this->getStudentPkl(true);
        if (!$spkl || $journal->student_pkl_id !== $spkl->id) {
            abort(403);
        }

        if ($journal->status !== 'draft') {
            abort(403, 'Hanya jurnal berstatus draft yang dapat dihapus.');
        }

        $freedBytes = 0;
        DB::transaction(function () use ($journal, &$freedBytes) {
            $files = app(SecureFileService::class);
            foreach ($journal->photos as $photo) {
                if ($photo->photo_path) {
                    $freedBytes += $files->size($photo->photo_path);
                    $files->delete($photo->photo_path);
                }
                $photo->delete();
            }
            $journal->delete();
        });

        if ($freedBytes > 0 && $journal->school) {
            app(StorageQuotaService::class)->recordDeletion($journal->school, $freedBytes);
        }

        return redirect()->route('student.journals')->with('success', 'Draft jurnal berhasil dihapus.');
    }

    public function profile(IncidentReportService $incidentService)
    {
        $spkl = $this->getStudentPkl(false);
        $student = Auth::user()->student?->load('class.major');

        if ($student && empty($student->parent_token)) {
            $student->regenerateParentToken();
            $student->refresh();
        }

        $assessment = $spkl?->assessment;
        $certificate = $spkl ? Certificate::where('student_pkl_id', $spkl->id)->first() : null;
        $incidents = $spkl ? $incidentService->getIncidentsForStudent($spkl) : collect();

        return view('student.profile', compact('spkl', 'student', 'assessment', 'certificate', 'incidents'));
    }

    public function incidents(IncidentReportService $incidentService)
    {
        $spkl = $this->getStudentPkl(false);
        $student = Auth::user()->student?->load('class.major');
        $incidents = $spkl ? $incidentService->getIncidentsForStudent($spkl) : collect();

        return view('student.incidents', compact('spkl', 'student', 'incidents'));
    }

    public function downloadCertificate(CertificateService $certificateService)
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(403);
        }

        $spklIds = $student->studentPkls()->pluck('id');
        $certificate = Certificate::whereIn('student_pkl_id', $spklIds)->firstOrFail();

        try {
            $pdf = $certificateService->renderPdf($certificate);
            $sanitizedName = Str::slug(Auth::user()->name);
            $safeCertNumber = str_replace(['/', '\\'], '-', $certificate->certificate_number);

            return $pdf->download("E-Sertifikat_PKL_{$sanitizedName}_{$safeCertNumber}.pdf");
        } catch (\Throwable $e) {
            Log::error("Download sertifikat siswa gagal (User ID: " . Auth::id() . "): " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', 'Gagal mengunduh dokumen sertifikat: ' . $e->getMessage());
        }
    }

    public function storeIncident(Request $request, IncidentReportService $incidentService)
    {
        $spkl = $this->getStudentPkl(true);
        if (!$spkl) {
            return back()->withErrors(['category' => 'Anda tidak memiliki penugasan PKL aktif untuk melaporkan kendala.']);
        }

        $validated = $request->validate([
            'category' => ['required', 'in:administrative,curriculum_mismatch,interpersonal,safety_health,emergency'],
            'urgency' => ['required', 'in:low,medium,high,critical'],
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'image', 'max:5120'], // max 5MB image
        ]);

        $incidentService->createReport($spkl, $validated, $request->file('attachment'));

        $isEmergency = in_array($validated['urgency'], ['high', 'critical']);
        $msg = $isEmergency 
            ? 'Laporan Darurat / SOS telah dikirimkan ke Guru Pembimbing dan Admin Sekolah untuk penanganan segera.' 
            : 'Laporan kendala Anda berhasil dikirimkan ke Guru Pembimbing.';

        return back()->with('success', $msg);
    }

    public function followupIncident(Request $request, IncidentReport $report, IncidentReportService $incidentService)
    {
        $spkl = $this->getStudentPkl(false);
        if (!$spkl || $report->student_pkl_id !== $spkl->id) {
            abort(403, 'Akses ditolak: Anda bukan pemilik laporan kendala ini.');
        }

        $validated = $request->validate([
            'action_note' => ['required', 'string', 'max:1000'],
            'status' => ['nullable', 'in:resolved'],
        ]);

        $incidentService->addFollowup($report, Auth::user(), $validated['action_note'], $validated['status'] ?? null);

        $msg = ($validated['status'] ?? null) === 'resolved'
            ? 'Laporan darurat SOS telah ditandai selesai. Tetap jaga keselamatan Anda.'
            : 'Update kondisi darurat berhasil dikirimkan ke Guru Pembimbing.';

        return back()->with('success', $msg);
    }
}
