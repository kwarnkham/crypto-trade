<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait StatusFilterable
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
    }
}
