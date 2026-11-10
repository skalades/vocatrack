<?php

namespace App\Traits;

use App\Scopes\SchoolScope;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new SchoolScope);

        static::creating(function (Model $model) {
            if (empty($model->school_id) && auth()->check() && auth()->user()->school_id) {
                $model->school_id = auth()->user()->school_id;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
