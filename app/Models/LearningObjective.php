<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningObjective extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'major_id',
        'code',
        'title',
        'description',
        'element_name',
        'target_hours',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'target_hours' => 'integer',
        'sort_order' => 'integer',
    ];

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function journals(): BelongsToMany
    {
        return $this->belongsToMany(Journal::class, 'journal_learning_objective');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMajor($query, $majorId)
    {
        return $query->where('major_id', $majorId);
    }
}
