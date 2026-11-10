<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use HasFactory, BelongsToSchool;

    protected $table = 'classes';

    protected $fillable = [
        'school_id',
        'major_id',
        'name',
        'grade_level',
        'academic_year',
    ];

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }
}
