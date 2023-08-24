<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Services\Tron;
use Illuminate\Contracts\Database\Eloquent\Builder;
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

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public static function findAvailable(): ?Wallet
    {
        return Wallet::query()
            ->whereNotNull('activated_at')
            ->whereDoesntHave('deposits', function (Builder $query) {
                $query->whereIn('status', [DepositStatus::PENDING->value, DepositStatus::CONFIRMED->value]);
            })
            ->first();
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
