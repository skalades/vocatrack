<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalApproval;
use App\Models\User;
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

            return $lockedJournal;
        });
    }
}
