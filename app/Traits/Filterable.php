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

        $query->when(
            $filters['wallet_id'] ?? null,
            fn (Builder $query, $wallet_id) => $query->where(
                'wallet_id',
                $wallet_id
            )
        );

        $query->when(
            $filters['user_id'] ?? null,
            fn (Builder $query, $user_id) => $query->where(
                'user_id',
                $user_id
            )
        );

        $query->when(
            $filters['agent_id'] ?? null,
            fn (Builder $query, $agent_id) => $query->where(
                'agent_id',
                $agent_id
            )
        );

        $query->when(
            $filters['token_address'] ?? null,
            fn (Builder $query, $token_address) => $query->where(
                'token_address',
                $token_address
            )
        );

        $query->when(
            $filters['transactionable_type'] ?? null,
            fn (Builder $query, $transactionable_type) => $query->whereIn(
                'transactionable_type',
                explode(',', $transactionable_type)
            )
        );
    }
}
