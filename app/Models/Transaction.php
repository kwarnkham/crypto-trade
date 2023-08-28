<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function receipt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => json_decode($value ?? ''),
        );
    }
}
