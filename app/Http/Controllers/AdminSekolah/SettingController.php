<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Http\Requests\AdminSekolah\UpdateSettingsRequest;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Journal;
use App\Models\JournalPhoto;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Services\StorageQuotaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends BaseAdminSekolahController
{
    public function settings()
    {
        $schoolId = $this->getSchoolId();
        $school = School::findOrFail($schoolId);
        $settings = SchoolSetting::where('school_id', $schoolId)->pluck('value', 'key');
        $periods = PklPeriod::where('school_id', $schoolId)->withCount('studentPkls')->latest()->get();

        return view('adminsekolah.settings', compact('school', 'settings', 'periods'));
    }

    public function updateSettings(UpdateSettingsRequest $request)
    {
        $schoolId = $this->getSchoolId();
        $school = School::findOrFail($schoolId);

        if (round((float) $request->weight_dudi + (float) $request->weight_guru, 2) !== 100.0) {
            return back()->withErrors(['weight_dudi' => 'Total persentase bobot penilaian DU/DI dan Guru Pembimbing harus tepat 100% (saat ini: ' . ((float) $request->weight_dudi + (float) $request->weight_guru) . '%).'])->withInput();
        }

        $schoolData = [
            'name' => $request->school_name,
            'phone' => $request->school_phone,
            'address' => $request->school_address,
        ];

        if ($request->boolean('remove_logo')) {
            if ($school->logo_path && Storage::disk('public')->exists($school->logo_path)) {
                Storage::disk('public')->delete($school->logo_path);
            }
            $schoolData['logo_path'] = null;
        } elseif ($request->hasFile('school_logo')) {
            $file = $request->file('school_logo');
            $fileSize = $file->getSize();
            if (!app(StorageQuotaService::class)->canUpload($school, $fileSize)) {
                return back()->with('error', 'Kapasitas penyimpanan sekolah Anda telah penuh. Tidak dapat mengunggah logo baru.')->withInput();
            }

            if ($school->logo_path && Storage::disk('public')->exists($school->logo_path)) {
                $oldSize = Storage::disk('public')->size($school->logo_path);
                Storage::disk('public')->delete($school->logo_path);
                app(StorageQuotaService::class)->recordDeletion($school, $oldSize);
            }
            $filename = 'logo_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("schools/{$schoolId}/logo", $filename, 'public');
            app(StorageQuotaService::class)->recordUpload($school, $fileSize);
            $schoolData['logo_path'] = $path;
        }

        $school->update($schoolData);

        $settingsToUpdate = [
            'checkin_start' => $request->checkin_start,
            'checkin_late' => $request->checkin_late,
            'checkin_alfa' => $request->checkin_alfa,
            'checkout_start' => $request->checkout_start,
            'journal_backdate_limit' => $request->journal_backdate_limit,
            'weight_dudi' => $request->weight_dudi,
            'weight_guru' => $request->weight_guru,
            'kop_header_line1' => $request->kop_header_line1,
            'kop_header_line2' => $request->kop_header_line2,
            'kop_header_line3' => $request->kop_header_line3,
            'kepsek_name' => $request->kepsek_name,
            'kepsek_nip' => $request->kepsek_nip,
        ];

        foreach ($settingsToUpdate as $k => $v) {
            SchoolSetting::updateOrCreate(
                ['school_id' => $schoolId, 'key' => $k],
                ['value' => $v ?? '']
            );
        }

        return back()->with('success', 'Konfigurasi identitas sekolah dan jam kerja berhasil disimpan.');
    }

    public function toggleTestMode(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $school = School::findOrFail($schoolId);

        $currentState = $school->isTestModeActive();
        $newState = !$currentState;

        SchoolSetting::updateOrCreate(
            ['school_id' => $schoolId, 'key' => 'stress_test_mode'],
            ['value' => $newState ? '1' : '0']
        );

        if ($newState) {
            SchoolSetting::updateOrCreate(
                ['school_id' => $schoolId, 'key' => 'stress_test_activated_at'],
                ['value' => now()->toIso8601String()]
            );
        }

        Cache::forget(SchoolSetting::cacheKey($schoolId));

        $msg = $newState 
            ? 'Mode Simulasi & Uji Coba berhasil DIAKTIFKAN. Siswa di DU/DI belum mulai kini dapat presensi bebas radius.'
            : 'Mode Simulasi & Uji Coba telah DINONAKTIFKAN. Seluruh aturan jam dan geofencing kembali normal.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $newState,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function getTestModeStats(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $school = School::findOrFail($schoolId);
        $date = $request->query('date') ?: Carbon::today()->toDateString();

        $attQuery = Attendance::where('school_id', $schoolId)
            ->forDate($date)
            ->whereHas('studentPkl.company', fn($cq) => $cq->where('is_pkl_started', false));

        $journalQuery = Journal::where('school_id', $schoolId)
            ->forDate($date)
            ->whereHas('studentPkl.company', fn($cq) => $cq->where('is_pkl_started', false));

        $journalIds = (clone $journalQuery)->pluck('id');
        $photoCount = JournalPhoto::whereIn('journal_id', $journalIds)->count();

        $reqCount = AttendanceRequest::where('school_id', $schoolId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereHas('studentPkl.company', fn($cq) => $cq->where('is_pkl_started', false))
            ->count();

        return response()->json([
            'is_active' => $school->isTestModeActive(),
            'date' => $date,
            'attendances_count' => $attQuery->count(),
            'journals_count' => $journalQuery->count(),
            'photos_count' => $photoCount,
            'requests_count' => $reqCount,
        ]);
    }

    public function cleanTestData(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $school = School::findOrFail($schoolId);
        $date = $request->input('date') ?: Carbon::today()->toDateString();

        $attQuery = Attendance::where('school_id', $schoolId)
            ->forDate($date)
            ->whereHas('studentPkl.company', fn($cq) => $cq->where('is_pkl_started', false));

        $journalQuery = Journal::where('school_id', $schoolId)
            ->forDate($date)
            ->whereHas('studentPkl.company', fn($cq) => $cq->where('is_pkl_started', false));

        $reqQuery = AttendanceRequest::where('school_id', $schoolId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereHas('studentPkl.company', fn($cq) => $cq->where('is_pkl_started', false));

        $attCount = (clone $attQuery)->count();
        $journalCount = (clone $journalQuery)->count();
        $reqCount = (clone $reqQuery)->count();
        $journalIds = (clone $journalQuery)->pluck('id');
        $photoCount = JournalPhoto::whereIn('journal_id', $journalIds)->count();

        $deletedFiles = 0;
        $deleteFileHelper = function (?string $path) use (&$deletedFiles) {
            if (empty($path)) return;
            try {
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                    $deletedFiles++;
                }
            } catch (\Throwable $e) {}
            try {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    $deletedFiles++;
                }
            } catch (\Throwable $e) {}
        };

        // Hapus fisik foto presensi
        $attQuery->chunkById(200, function ($attendances) use ($deleteFileHelper) {
            foreach ($attendances as $att) {
                $deleteFileHelper($att->checkin_photo_path);
                $deleteFileHelper($att->checkout_photo_path ?? null);
            }
        });

        // Hapus fisik foto jurnal
        JournalPhoto::whereIn('journal_id', $journalIds)->chunkById(200, function ($photos) use ($deleteFileHelper) {
            foreach ($photos as $p) {
                $deleteFileHelper($p->photo_path);
            }
        });

        // Hapus fisik lampiran izin
        $reqQuery->chunkById(200, function ($requests) use ($deleteFileHelper) {
            foreach ($requests as $r) {
                $deleteFileHelper($r->attachment_path);
            }
        });

        // Hapus record DB
        DB::transaction(function () use ($attQuery, $journalIds, $journalQuery, $reqQuery) {
            JournalPhoto::whereIn('journal_id', $journalIds)->delete();
            $journalQuery->delete();
            $attQuery->delete();
            $reqQuery->delete();
        });

        // Hitung ulang kuota storage sekolah
        app(StorageQuotaService::class)->calculateSchoolStorage($school);
        Cache::forget("school_storage_bytes_{$school->id}");

        $msg = "Pembersihan batch selesai: {$attCount} presensi, {$journalCount} jurnal ({$photoCount} foto), dan {$deletedFiles} file fisik berhasil dihapus. Data siswa aktif PKL aman 100%.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'deleted' => [
                    'attendances' => $attCount,
                    'journals' => $journalCount,
                    'photos' => $photoCount,
                    'files' => $deletedFiles,
                ],
            ]);
        }

        return back()->with('success', $msg);
    }
}
