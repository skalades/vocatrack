<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalApproval;
use App\Models\User;
use App\Notifications\JournalStatusUpdatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalService
{
    public function approve(Journal $journal, User $approver, ?string $notes = null): Journal
    {
        return DB::transaction(function () use ($journal, $approver, $notes) {
            // Pessimistic lock row to prevent race conditions & double approval
            $lockedJournal = Journal::where('id', $journal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($approver->hasRole('pembimbing-dudi')) {
                if ($lockedJournal->status !== 'submitted') {
                    throw ValidationException::withMessages([
                        'status' => 'Status jurnal saat ini tidak valid untuk review DU/DI (mungkin sudah disetujui atau sedang direvisi).'
                    ]);
                }
                $nextStatus = 'review_guru';
                $roleLevel = 'pembimbing-dudi';
            } elseif ($approver->hasRole('guru-pembimbing')) {
                if ($lockedJournal->status !== 'review_guru') {
                    throw ValidationException::withMessages([
                        'status' => 'Jurnal harus disetujui Pembimbing DU/DI terlebih dahulu sebelum dapat disetujui Guru.'
                    ]);
                }
                $nextStatus = 'approved';
                $roleLevel = 'guru-pembimbing';
            } else {
                throw ValidationException::withMessages(['role' => 'Pengguna tidak memiliki wewenang approval.']);
            }

            $lockedJournal->update(['status' => $nextStatus]);

            JournalApproval::create([
                'journal_id' => $lockedJournal->id,
                'approver_id' => $approver->id,
                'role_level' => $roleLevel,
                'status' => 'approved',
                'notes' => $notes,
                'created_at' => now(),
            ]);

            // Notify student
            $studentUser = $lockedJournal->studentPkl?->student?->user;
            if ($studentUser) {
                $studentUser->notify(new JournalStatusUpdatedNotification($lockedJournal, $nextStatus, $notes));
            }

            return $lockedJournal;
        });
    }

    public function requestRevision(Journal $journal, User $reviewer, string $notes): Journal
    {
        if (!$reviewer->hasRole(['pembimbing-dudi', 'guru-pembimbing'])) {
            throw ValidationException::withMessages(['role' => 'Pengguna tidak memiliki wewenang meminta revisi jurnal.']);
        }

        $roleLevel = $reviewer->hasRole('pembimbing-dudi') ? 'pembimbing-dudi' : 'guru-pembimbing';

        return DB::transaction(function () use ($journal, $reviewer, $roleLevel, $notes) {
            // Pessimistic lock row to avoid concurrent status mutation
            $lockedJournal = Journal::where('id', $journal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedJournal->status, ['submitted', 'review_guru'])) {
                throw ValidationException::withMessages([
                    'status' => 'Jurnal dengan status ini tidak dapat diminta revisi (status saat ini: ' . $lockedJournal->status . ').'
                ]);
            }

            $lockedJournal->update(['status' => 'revision']);

            JournalApproval::create([
                'journal_id' => $lockedJournal->id,
                'approver_id' => $reviewer->id,
                'role_level' => $roleLevel,
                'status' => 'revision',
                'notes' => $notes,
                'created_at' => now(),
            ]);

            // Notify student
            $studentUser = $lockedJournal->studentPkl?->student?->user;
            if ($studentUser) {
                $studentUser->notify(new JournalStatusUpdatedNotification($lockedJournal, 'revision', $notes));
            }

            return $lockedJournal;
        });
    }

    /**
     * Bulk process journals (bulk approve or bulk revision) with pessimistic locking and validation.
     */
    public function bulkProcess(array $journalIds, string $action, ?string $notes, User $user): array
    {
        if (!in_array($action, ['approve', 'revision'])) {
            throw ValidationException::withMessages(['action' => 'Aksi bulk tidak valid.']);
        }

        if ($action === 'revision' && empty($notes)) {
            throw ValidationException::withMessages(['notes' => 'Catatan revisi wajib diisi untuk revisi massal.']);
        }

        $isDudi = $user->hasRole('pembimbing-dudi');
        $isGuru = $user->hasRole('guru-pembimbing');

        if (!$isDudi && !$isGuru) {
            throw ValidationException::withMessages(['role' => 'Pengguna tidak memiliki wewenang approval jurnal.']);
        }

        $processed = 0;
        $skipped = 0;

        DB::transaction(function () use ($journalIds, $action, $notes, $user, $isDudi, $isGuru, &$processed, &$skipped) {
            $journals = Journal::whereIn('id', $journalIds)
                ->with('studentPkl')
                ->lockForUpdate()
                ->get();

            foreach ($journals as $journal) {
                // Verify ownership / tenant assignment
                if ($isDudi) {
                    $supervisor = $user->supervisor;
                    if (!$supervisor || $journal->studentPkl->company_id !== $supervisor->company_id) {
                        $skipped++;
                        continue;
                    }
                    if ($journal->status !== 'submitted') {
                        $skipped++;
                        continue;
                    }
                    $nextStatus = ($action === 'approve') ? 'review_guru' : 'revision';
                    $roleLevel = 'pembimbing-dudi';
                } else { // Guru
                    $teacher = $user->teacher;
                    if (!$teacher || $journal->studentPkl->teacher_id !== $teacher->id) {
                        $skipped++;
                        continue;
                    }
                    if ($journal->status !== 'review_guru') {
                        $skipped++;
                        continue;
                    }
                    $nextStatus = ($action === 'approve') ? 'approved' : 'revision';
                    $roleLevel = 'guru-pembimbing';
                }

                $journal->update(['status' => $nextStatus]);

                JournalApproval::create([
                    'journal_id' => $journal->id,
                    'approver_id' => $user->id,
                    'role_level' => $roleLevel,
                    'status' => $action === 'approve' ? 'approved' : 'revision',
                    'notes' => $notes,
                    'created_at' => now(),
                ]);

                // Notify student
                $studentUser = $journal->studentPkl?->student?->user;
                if ($studentUser) {
                    $studentUser->notify(new JournalStatusUpdatedNotification($journal, $nextStatus, $notes));
                }

                $processed++;
            }
        });

        return [
            'processed' => $processed,
            'skipped' => $skipped,
        ];
    }
}
