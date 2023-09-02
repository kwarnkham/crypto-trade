<?php

namespace App\Models;

use App\Services\Tron;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unstake extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'withdrawable_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ($value ?? 0) / Tron::DIGITS,
            set: fn (string $value) => ($value ?? 0) * Tron::DIGITS,
        );
    }
}
