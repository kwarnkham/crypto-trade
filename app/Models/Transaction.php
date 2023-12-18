<?php

namespace App\Models;

use App\Services\Tron;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function receipt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => json_decode($value ?? ''),
            set: fn ($value) => json_encode($value ?? []),
        );
    }


    protected function fee(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ($value ?? 0) / Tron::DIGITS,
        );
    }

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ($value ?? 0) / Tron::DIGITS,
        );
    }
}
