<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check() && !auth()->user()->hasRole('super-admin')) {
            $schoolId = auth()->user()->school_id;
            if ($schoolId) {
                $builder->where($model->getTable() . '.school_id', '=', $schoolId);
            } else {
                // Fail-closed security: non-super-admin with no school_id must not see any records
                $builder->whereRaw('1 = 0');
            }
        }
    }
}
