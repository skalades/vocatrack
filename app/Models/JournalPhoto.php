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

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
