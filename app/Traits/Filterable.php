<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['status'] ?? null,
            fn (Builder $query, $status) => $query->whereIn(
                'status',
                explode(',', $status)
            )
        );

        $query->when(
            $filters['name'] ?? null,
            fn (Builder $query, $name) => $query->where(
                'name',
                $name
            )
        );
    }
}
