<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Major;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    /**
     * Admin Sekolah: List all announcements.
     */
    public function adminIndex(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $majors = Major::where('school_id', $schoolId)->orderBy('name')->get();

        $filters = [
            'category' => $request->get('category', 'all'),
            'major_id' => $request->get('major_id', 'all'),
            'search' => $request->get('search'),
        ];

        $announcements = $this->announcementService->getAnnouncementsForUser(Auth::user(), $filters, 10);

        // Stats for admin dashboard cards
        $baseQuery = Announcement::where('school_id', $schoolId);
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'pinned' => (clone $baseQuery)->where('is_pinned', true)->count(),
            'important' => (clone $baseQuery)->where('category', 'penting')->count(),
        ];

        return view('adminsekolah.announcements', compact('announcements', 'majors', 'stats', 'filters'));
    }

    /**
     * Admin Sekolah: Store new announcement.
     */
    public function adminStore(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:umum,penting,kegiatan,akademik,libur,dudi'],
            'target_role' => ['required', 'in:all,siswa,guru-pembimbing,pembimbing-dudi,kepala-sekolah'],
            'target_major_id' => ['nullable', 'exists:majors,id'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_pinned' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'content.required' => 'Isi teks pengumuman wajib ditulis.',
            'image.max' => 'Ukuran gambar maksimal 2MB.',
            'image.mimes' => 'Format gambar harus berupa JPG, PNG, atau WEBP.',
        ]);

        // Validate major belongs to school if selected
        if ($request->filled('target_major_id')) {
            Major::where('id', $request->target_major_id)->where('school_id', $schoolId)->firstOrFail();
        }

        $this->announcementService->createAnnouncement(
            Auth::user(),
            $request->all(),
            $request->file('image')
        );

        return redirect()->route('adminsekolah.announcements')
            ->with('success', 'Pengumuman baru berhasil diterbitkan dan disiarkan ke pengguna target!');
    }

    /**
     * Admin Sekolah: Update announcement.
     */
    public function adminUpdate(Request $request, Announcement $announcement)
    {
        $schoolId = Auth::user()->school_id;
        if ($announcement->school_id !== $schoolId) {
            abort(403, 'Aksi tidak diizinkan untuk sekolah lain.');
        }

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:umum,penting,kegiatan,akademik,libur,dudi'],
            'target_role' => ['required', 'in:all,siswa,guru-pembimbing,pembimbing-dudi,kepala-sekolah'],
            'target_major_id' => ['nullable', 'exists:majors,id'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->filled('target_major_id')) {
            Major::where('id', $request->target_major_id)->where('school_id', $schoolId)->firstOrFail();
        }

        $this->announcementService->updateAnnouncement(
            $announcement,
            $request->all(),
            $request->file('image'),
            $request->boolean('remove_image')
        );

        return redirect()->route('adminsekolah.announcements')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    /**
     * Admin Sekolah: Delete announcement.
     */
    public function adminDestroy(Announcement $announcement)
    {
        $schoolId = Auth::user()->school_id;
        if ($announcement->school_id !== $schoolId) {
            abort(403);
        }

        $this->announcementService->deleteAnnouncement($announcement);

        return redirect()->route('adminsekolah.announcements')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }

    /**
     * Admin Sekolah: Toggle pin status.
     */
    public function adminTogglePin(Announcement $announcement)
    {
        $schoolId = Auth::user()->school_id;
        if ($announcement->school_id !== $schoolId) {
            abort(403);
        }

        $pinned = $this->announcementService->togglePin($announcement);
        $msg = $pinned ? 'Pengumuman berhasil disematkan ke posisi teratas!' : 'Sematkan pengumuman dinonaktifkan.';

        return back()->with('success', $msg);
    }

    /**
     * Guru Pembimbing: List and manage announcements.
     */
    public function guruIndex(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $majors = Major::where('school_id', $schoolId)->orderBy('name')->get();

        $filters = [
            'category' => $request->get('category', 'all'),
            'major_id' => $request->get('major_id', 'all'),
            'search' => $request->get('search'),
        ];

        $announcements = $this->announcementService->getAnnouncementsForUser(Auth::user(), $filters, 10);

        // Check how many announcements authored by this guru
        $myAnnouncementsCount = Announcement::where('school_id', $schoolId)
            ->where('author_id', Auth::id())
            ->count();

        return view('teacher.announcements', compact('announcements', 'majors', 'filters', 'myAnnouncementsCount'));
    }

    /**
     * Guru Pembimbing: Store announcement.
     */
    public function guruStore(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:umum,penting,kegiatan,akademik,libur,dudi'],
            'target_role' => ['required', 'in:all,siswa,guru-pembimbing'],
            'target_major_id' => ['nullable', 'exists:majors,id'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        if ($request->filled('target_major_id')) {
            Major::where('id', $request->target_major_id)->where('school_id', $schoolId)->firstOrFail();
        }

        $this->announcementService->createAnnouncement(
            Auth::user(),
            $request->all(),
            $request->file('image')
        );

        return redirect()->route('guru.announcements')
            ->with('success', 'Pengumuman dari guru berhasil dipublikasikan!');
    }

    /**
     * Guru Pembimbing: Update announcement (only own announcements).
     */
    public function guruUpdate(Request $request, Announcement $announcement)
    {
        if ($announcement->author_id !== Auth::id() || $announcement->school_id !== Auth::user()->school_id) {
            abort(403, 'Anda hanya dapat mengedit pengumuman yang Anda buat sendiri.');
        }

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:umum,penting,kegiatan,akademik,libur,dudi'],
            'target_role' => ['required', 'in:all,siswa,guru-pembimbing'],
            'target_major_id' => ['nullable', 'exists:majors,id'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $this->announcementService->updateAnnouncement(
            $announcement,
            $request->all(),
            $request->file('image'),
            $request->boolean('remove_image')
        );

        return redirect()->route('guru.announcements')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    /**
     * Guru Pembimbing: Delete announcement (only own announcements).
     */
    public function guruDestroy(Announcement $announcement)
    {
        if ($announcement->author_id !== Auth::id() || $announcement->school_id !== Auth::user()->school_id) {
            abort(403, 'Anda hanya dapat menghapus pengumuman yang Anda buat sendiri.');
        }

        $this->announcementService->deleteAnnouncement($announcement);

        return redirect()->route('guru.announcements')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }

    /**
     * Siswa: List all announcements targeted to this student.
     */
    public function studentIndex(Request $request)
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(403, 'Profil siswa tidak ditemukan.');
        }

        $spkl = $student->studentPkls()
            ->with(['company', 'pklPeriod'])
            ->whereIn('status', ['assigned', 'active'])
            ->latest()
            ->first();

        $filters = [
            'category' => $request->get('category', 'all'),
            'search' => $request->get('search'),
        ];

        $announcements = $this->announcementService->getAnnouncementsForUser(Auth::user(), $filters, 6);

        // Category counts for quick filter pills
        $user = Auth::user();
        $baseQuery = Announcement::query()->active()->forUser($user);
        $categoryCounts = [
            'all' => (clone $baseQuery)->count(),
            'penting' => (clone $baseQuery)->where('category', 'penting')->count(),
            'kegiatan' => (clone $baseQuery)->where('category', 'kegiatan')->count(),
            'akademik' => (clone $baseQuery)->where('category', 'akademik')->count(),
            'libur' => (clone $baseQuery)->where('category', 'libur')->count(),
            'umum' => (clone $baseQuery)->where('category', 'umum')->count(),
        ];

        return view('student.announcements', compact('announcements', 'filters', 'categoryCounts', 'spkl'));
    }

    /**
     * Get announcement details (for modal or AJAX).
     */
    public function show(Announcement $announcement)
    {
        // Must belong to user's school
        if ($announcement->school_id !== Auth::user()->school_id) {
            abort(404);
        }

        $announcement->load(['author.profile', 'major']);

        if (request()->wantsJson()) {
            return response()->json([
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'category' => $announcement->category,
                'category_label' => $announcement->category_label,
                'category_badge' => $announcement->category_badge_variant,
                'target_role_label' => $announcement->target_role_label,
                'target_scope' => $announcement->target_scope_description,
                'major_name' => $announcement->major?->name ?? 'Semua Jurusan',
                'image_url' => $announcement->image_url,
                'author_name' => $announcement->author?->name ?? 'Pihak Sekolah',
                'author_role' => $announcement->author?->role ?? '-',
                'published_at' => $announcement->published_at ? $announcement->published_at->isoFormat('dddd, D MMMM Y • HH:mm') : $announcement->created_at->isoFormat('dddd, D MMMM Y • HH:mm'),
                'is_pinned' => $announcement->is_pinned,
            ]);
        }

        return view('components.announcement-detail', compact('announcement'));
    }

    /**
     * Get the latest active announcement for the authenticated student (for real-time popup & polling).
     */
    public function latestForStudent(Request $request)
    {
        $user = Auth::user();
        $latest = Announcement::query()
            ->active()
            ->forUser($user)
            ->pinnedFirst()
            ->first();

        if (!$latest) {
            return response()->json(['has_announcement' => false]);
        }

        $latest->load(['author.profile', 'major']);

        return response()->json([
            'has_announcement' => true,
            'id' => $latest->id,
            'title' => $latest->title,
            'content' => $latest->content,
            'category' => $latest->category,
            'category_label' => $latest->category_label,
            'category_badge' => $latest->category_badge_variant,
            'target_role_label' => $latest->target_role_label,
            'target_scope' => $latest->target_scope_description,
            'major_name' => $latest->major?->name ?? 'Semua Jurusan',
            'image_url' => $latest->image_url,
            'author_name' => $latest->author?->name ?? 'Pihak Sekolah',
            'author_role' => $latest->author?->role ?? '-',
            'published_at' => $latest->published_at ? $latest->published_at->isoFormat('dddd, D MMMM Y • HH:mm') : $latest->created_at->isoFormat('dddd, D MMMM Y • HH:mm'),
            'is_pinned' => $latest->is_pinned,
        ]);
    }
}
