<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentFollowup extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'incident_report_id',
        'user_id',
        'action_note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function incidentReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
