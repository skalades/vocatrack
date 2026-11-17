<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SchoolSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        $invalidate = static function (SchoolSetting $setting): void {
            Cache::forget(self::cacheKey((int) $setting->school_id));
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    public static function allForSchool(int $schoolId): array
    {
        return Cache::remember(self::cacheKey($schoolId), now()->addHours(6), function () use ($schoolId) {
            return static::where('school_id', $schoolId)->pluck('value', 'key')->all();
        });
    }

    public static function cacheKey(int $schoolId): string
    {
        return "school:{$schoolId}:settings";
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
