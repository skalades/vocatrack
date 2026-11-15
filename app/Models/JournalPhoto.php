<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'photo_path',
        'caption',
    ];

    /**
     * URL terproteksi untuk foto jurnal (disajikan dari disk privat).
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path
            ? route('secure.file', ['type' => 'jurnal', 'id' => $this->id])
            : null;
    }

    /**
     * URL foto jurnal untuk portal orang tua (diotorisasi lewat token siswa).
     */
    public function parentUrl(string $token): ?string
    {
        return $this->photo_path
            ? route('parent.monitor.photo', ['token' => $token, 'photo' => $this->id])
            : null;
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
