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

    public static function validate(string $address)
    {
        $response = Tron::validateAddress($address);
        return $response->result;
    }

    public static function withdrawable(int $amount): ?Wallet
    {
        $wallet = Wallet::query()->where('balance', '>=', $amount)->first();
        if ($wallet == null) return $wallet;
        $wallet->updateBalance();
        if ($wallet->balance < $amount) {
            $wallets = Wallet::query()
                ->whereNotNull('activated_at')
                ->where('id', '!=', $wallet->id)
                ->get();
            $wallet = $wallets->first(function ($w) use ($amount) {
                return $w->updateBalance()->balance >= $amount;
            });
            if ($wallet == null) return $wallet;
            $wallet->refresh();
        }
        return $wallet;
    }

    public function updateBalance()
    {
        $trc20_address = config('app')['trc20_address'];
        $usdt = collect(
            Tron::getAccountInfoByAddress($this->base58_check)->data[0]->trc20
        )->first(fn ($v) => property_exists($v, $trc20_address));

        if ($usdt != null) $this->update(['balance' => $usdt->$trc20_address]);
        return $this->refresh();
    }

    public function sendUSDT(string $to, int $amount)
    {
        return Tron::sendUSDT($to, $amount, $this);
    }
}
