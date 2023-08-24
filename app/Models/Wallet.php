<?php

namespace App\Models;

use App\Services\Tron;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'private_key',
    ];

    protected $casts = [
        'private_key' => 'encrypted'
    ];


    public static function generate()
    {
        return Wallet::create(Tron::generateAddressLocally());
    }

    public static function findAvailable(): ?Wallet
    {
        return Wallet::query()->whereNull('reserved_at')->whereNotNull('activated_at')->first();
    }

    function removeReservation()
    {
        $this->update([
            'reserved_balance' => 0,
            'reserved_at' => null,
            'user_id' => null
        ]);
    }
}
