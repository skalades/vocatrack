<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nip',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'major_teacher')->withTimestamps();
    }

    public function studentPkls(): HasMany
    {
        return $this->hasMany(StudentPkl::class);
    }

    public function supervisionVisits(): HasMany
    {
        return $this->hasMany(SupervisionVisit::class);
    }
}
