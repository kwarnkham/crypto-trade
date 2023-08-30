<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Enums\WithdrawStatus;
use App\Services\Tron;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    protected function resource(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => json_decode($value ?? ''),
        );
    }

    public static function generate()
    {
        return Wallet::create(Tron::generateAddressLocally());
    }

    public function withdraws()
    {
        return $this->hasMany(Withdraw::class);
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public static function findAvailable(): ?Wallet
    {
        return Wallet::query()
            ->whereNotNull('activated_at')
            ->whereDoesntHave('deposits', function ($query) {
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
        $wallet = Wallet::query()->whereRaw(
            'balance >= IFNULL((
            SELECT SUM(amount)
            FROM withdraws
            WHERE withdraws.wallet_id = wallets.id
            AND status IN (?, ?)), 0) + ?',
            [WithdrawStatus::PENDING->value, WithdrawStatus::CONFIRMED->value, $amount]
        )->first();

        if ($wallet == null) return $wallet;

        $oldBalance = $wallet->balance;

        $wallet->updateBalance();

        if ($wallet->balance < $oldBalance) {
            $wallets = Wallet::query()
                ->whereNotNull('activated_at')
                ->where('id', '!=', $wallet->id)
                ->get();
            $wallet = $wallets->first(function ($w) use ($amount) {
                return $w->updateBalance()->balance >= $amount + $w->withdraws()->whereIn('status', [WithdrawStatus::PENDING->value, WithdrawStatus::CONFIRMED->value])->sum('amount');
            });
            if ($wallet == null) return $wallet;
            $wallet->refresh();
        }
        return $wallet;
    }

    public function updateBalance()
    {
        $trc20_address = config('app')['trc20_address'];
        $response =  Tron::getAccountInfoByAddress($this->base58_check)->data[0];
        $usdt = collect($response->trc20)->first(fn ($v) => property_exists($v, $trc20_address));
        $trx = $response->balance;

        if ($usdt != null) $this->update(['balance' => $usdt->$trc20_address, 'trx' => $trx]);
        return $this->refresh();
    }

    public function sendUSDT(string $to, int $amount)
    {
        return Tron::sendUSDT($to, $amount, $this);
    }

    public function freezeBalance(int $amount, string $resource = 'BANDWIDTH')
    {
        $tx =  Tron::freezeBalance($this->base58_check, $resource, $amount * 1000000);
        $signed = Tron::signTransaction($tx, $this);
        return Tron::broadcastTransaction($signed);
    }

    public function updateResource()
    {
        $resource = Tron::getAccountResource($this->base58_check);
        $this->update(['resource' => $resource]);
        return $this;
    }

    public function syncTxs(array $options = [])
    {
        $response = Tron::getTransactionInfoByAccountAddress($this->base58_check, $options);
        if (($response->meta->fingerprint ?? false) && ($response->meta->links ?? false) && $response->meta->links->next) {
            $this->getTxs([...$options, 'fingerprint' => $response->meta->fingerprint]);
        }
    }

    public function getTxs(array $options = [])
    {
        $response = Tron::getTransactionInfoByAccountAddress($this->base58_check, $options);
        return $response;
    }
}
