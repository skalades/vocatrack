<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Major extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'code',
        'name',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }
}
