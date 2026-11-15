<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentReport extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_pkl_id',
        'category',
        'urgency',
        'subject',
        'description',
        'attachment_path',
        'status',
        'handler_id',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * URL terproteksi untuk lampiran laporan kendala (disajikan dari disk privat).
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path
            ? route('secure.file', ['type' => 'kendala', 'id' => $this->id])
            : null;
    }

    public function studentPkl(): BelongsTo
    {
        return $this->belongsTo(StudentPkl::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handler_id');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(IncidentFollowup::class)->orderBy('created_at', 'asc');
    }
}
