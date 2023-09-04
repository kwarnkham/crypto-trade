<?php

namespace App\Models;

use App\Services\Tron;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Transfer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];


    public function charge(): MorphOne
    {
        return $this->morphOne(Charge::class, 'chargeable');
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ($value ?? 0) / Tron::DIGITS,
            set: fn (?string $value) => ($value ?? 0) * Tron::DIGITS,
        );
    }

    protected function fee(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ($value ?? 0) / Tron::DIGITS,
            set: fn (?string $value) => ($value ?? 0) * Tron::DIGITS,
        );
    }
}
