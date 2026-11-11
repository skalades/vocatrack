<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementService
{
    /**
     * Get scoped announcements for the authenticated user with optional filtering.
     */
    public function getAnnouncementsForUser(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Announcement::query()
            ->with(['author.profile', 'major'])
            ->forUser($user);

        // Siswa & DUDI only see active announcements
        if (!in_array($user->role, ['super-admin', 'admin-sekolah'])) {
            $query->active();
        }

        // Filter by category
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        // Filter by major (useful for admin/teacher filters)
        if (!empty($filters['major_id']) && $filters['major_id'] !== 'all') {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('target_major_id', $filters['major_id'])
                  ->orWhereNull('target_major_id');
            });
        }

        // Search in title and content
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        return $query->pinnedFirst()->paginate($perPage)->withQueryString();
    }

    /**
     * Get latest announcements for widget/card view (e.g. mobile beranda).
     */
    public function getLatestForUser(User $user, int $limit = 5): Collection
    {
        return Announcement::query()
            ->with(['author.profile', 'major'])
            ->active()
            ->forUser($user)
            ->pinnedFirst()
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new announcement with optional image.
     */
    public function createAnnouncement(User $author, array $data, ?UploadedFile $imageFile = null): Announcement
    {
        $schoolId = $author->school_id;
        $imagePath = null;

        if ($imageFile && $imageFile->isValid()) {
            $extension = $imageFile->getClientOriginalExtension() ?: 'jpg';
            $fileName = Str::random(32) . '.' . strtolower($extension);
            $year = Carbon::now()->format('Y');
            $dir = "schools/{$schoolId}/announcements/{$year}";
            
            $imagePath = $imageFile->storeAs($dir, $fileName, 'public');
        }

        return Announcement::create([
            'school_id' => $schoolId,
            'author_id' => $author->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'category' => $data['category'] ?? 'umum',
            'image_path' => $imagePath,
            'target_role' => $data['target_role'] ?? 'all',
            'target_major_id' => !empty($data['target_major_id']) ? $data['target_major_id'] : null,
            'is_pinned' => !empty($data['is_pinned']),
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'published_at' => Carbon::now(),
        ]);
    }

    /**
     * Update an existing announcement.
     */
    public function updateAnnouncement(Announcement $announcement, array $data, ?UploadedFile $imageFile = null, bool $removeImage = false): Announcement
    {
        $imagePath = $announcement->image_path;

        if ($removeImage && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        if ($imageFile && $imageFile->isValid()) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $schoolId = $announcement->school_id;
            $extension = $imageFile->getClientOriginalExtension() ?: 'jpg';
            $fileName = Str::random(32) . '.' . strtolower($extension);
            $year = Carbon::now()->format('Y');
            $dir = "schools/{$schoolId}/announcements/{$year}";
            
            $imagePath = $imageFile->storeAs($dir, $fileName, 'public');
        }

        $announcement->update([
            'title' => $data['title'] ?? $announcement->title,
            'content' => $data['content'] ?? $announcement->content,
            'category' => $data['category'] ?? $announcement->category,
            'image_path' => $imagePath,
            'target_role' => $data['target_role'] ?? $announcement->target_role,
            'target_major_id' => !empty($data['target_major_id']) ? $data['target_major_id'] : null,
            'is_pinned' => isset($data['is_pinned']) ? (bool)$data['is_pinned'] : $announcement->is_pinned,
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : $announcement->is_active,
        ]);

        return $announcement;
    }

    /**
     * Delete an announcement and its media.
     */
    public function deleteAnnouncement(Announcement $announcement): bool
    {
        if ($announcement->image_path) {
            Storage::disk('public')->delete($announcement->image_path);
        }

        return (bool) $announcement->delete();
    }

    /**
     * Toggle pinned status.
     */
    public function togglePin(Announcement $announcement): bool
    {
        $announcement->is_pinned = !$announcement->is_pinned;
        $announcement->save();

        return $announcement->is_pinned;
    }
}
