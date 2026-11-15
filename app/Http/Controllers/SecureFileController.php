<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalPhoto;
use App\Models\StudentPkl;
use App\Services\ParentMonitoringService;
use App\Services\SecureFileService;
use Illuminate\Support\Facades\Auth;

class SecureFileController extends Controller
{
    public function __construct(
        protected SecureFileService $files
    ) {}

    /**
     * Sajikan berkas pribadi siswa kepada pengguna yang berwenang.
     */
    public function show(string $type, string $id)
    {
        $resolved = $this->files->resolve($type, $id);
        abort_if($resolved === null, 404, 'Berkas tidak ditemukan.');

        [, $path, $spkl] = $resolved;

        abort_unless(
            $this->files->canAccess(Auth::user(), $spkl, $type),
            403,
            'Akses ditolak: Anda tidak berwenang melihat berkas ini.'
        );

        return $this->files->respond($path);
    }

    /**
     * Foto jurnal untuk portal orang tua (tanpa login, diotorisasi oleh token siswa).
     */
    public function parentJournalPhoto(string $token, string $photo, ParentMonitoringService $monitoring)
    {
        $student = $monitoring->findAccessibleStudentByToken($token);
        abort_if($student === null, 404, 'Berkas tidak ditemukan.');

        // Akses publik berbasis token: lepas scope tenant (pengunjung bisa saja sedang
        // login sebagai pengguna sekolah lain), kepemilikan diverifikasi manual di bawah.
        $journalPhoto = JournalPhoto::find($photo);
        $journal = $journalPhoto ? Journal::withoutGlobalScopes()->find($journalPhoto->journal_id) : null;
        $spkl = $journal ? StudentPkl::withoutGlobalScopes()->find($journal->student_pkl_id) : null;

        abort_if(
            !$journalPhoto || !$journalPhoto->photo_path || !$spkl || (int) $spkl->student_id !== (int) $student->id,
            404,
            'Berkas tidak ditemukan.'
        );

        return $this->files->respond($journalPhoto->photo_path);
    }
}
