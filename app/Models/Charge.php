<?php

namespace App\Models;

use App\Services\Tron;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Charge extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ($value) / Tron::DIGITS,
            set: fn (string $value) => ($value) * Tron::DIGITS,
        );
    }

    public function chargeable(): MorphTo
    {
        return $this->morphTo();
    }
}
