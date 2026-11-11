<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolHoliday extends Model
{
    use HasFactory, BelongsToSchool;

    protected $table = 'school_holidays';

    protected $fillable = [
        'school_id',
        'title',
        'holiday_date',
        'description',
        'is_national',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_national' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('holiday_date', $year);
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('holiday_date', $year)
                     ->whereMonth('holiday_date', $month);
    }
}
