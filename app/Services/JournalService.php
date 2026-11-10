<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalApproval;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class JournalService
{
    public function approve(Journal $journal, User $approver, ?string $notes = null): Journal
    {
        if ($approver->hasRole('pembimbing-dudi')) {
            if ($journal->status !== 'submitted') {
                throw ValidationException::withMessages(['status' => 'Status jurnal saat ini tidak valid untuk review DU/DI.']);
            }
            $nextStatus = 'review_guru';
            $roleLevel = 'pembimbing-dudi';
        } elseif ($approver->hasRole('guru-pembimbing')) {
            if ($journal->status !== 'review_guru') {
                throw ValidationException::withMessages(['status' => 'Jurnal harus disetujui Pembimbing DU/DI terlebih dahulu.']);
            }
            $nextStatus = 'approved';
            $roleLevel = 'guru-pembimbing';
        } else {
            throw ValidationException::withMessages(['role' => 'Pengguna tidak memiliki wewenang approval.']);
        }

        $journal->update(['status' => $nextStatus]);

        JournalApproval::create([
            'journal_id' => $journal->id,
            'approver_id' => $approver->id,
            'role_level' => $roleLevel,
            'status' => 'approved',
            'notes' => $notes,
            'created_at' => now(),
        ]);

        return $journal;
    }

    public function requestRevision(Journal $journal, User $reviewer, string $notes): Journal
    {
        if (!$reviewer->hasRole(['pembimbing-dudi', 'guru-pembimbing'])) {
            throw ValidationException::withMessages(['role' => 'Pengguna tidak memiliki wewenang meminta revisi jurnal.']);
        }

        $roleLevel = $reviewer->hasRole('pembimbing-dudi') ? 'pembimbing-dudi' : 'guru-pembimbing';

        $journal->update(['status' => 'revision']);

        JournalApproval::create([
            'journal_id' => $journal->id,
            'approver_id' => $reviewer->id,
            'role_level' => $roleLevel,
            'status' => 'revision',
            'notes' => $notes,
            'created_at' => now(),
        ]);

        return $journal;
    }
}
