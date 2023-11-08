<?php

namespace App\Models;

use App\Services\Tron;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BalanceLog extends Model
{
    use HasFactory, Filterable;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ($value ?? 0) / Tron::DIGITS,
            set: fn ($value) => ($value ?? 0) * Tron::DIGITS,
        );
    }
}
