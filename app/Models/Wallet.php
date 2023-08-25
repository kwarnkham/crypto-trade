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

    public function updateBalance()
    {
        $usdt = collect(
            Tron::getAccountInfoByAddress($this->wallet->base58_check)->data[0]->trc20
        )->first(fn ($v) => property_exists($v, 'TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs'));
        $key = 'TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs';
        if ($usdt != null) $this->wallet->update(['balance' => $usdt->$key]);
    }
}
