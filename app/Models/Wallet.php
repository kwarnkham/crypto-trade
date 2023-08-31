<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Enums\WithdrawStatus;
use App\Services\Tron;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    use HasFactory;

    protected $guarded = ['id'];


    protected $hidden = [
        'private_key', 'public_key', 'base64', 'hex_address'
    ];

    protected $casts = [
        'private_key' => 'encrypted',
        'activated_at' => 'datetime'
    ];

    protected function resource(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => json_decode($value ?? ''),
        );
    }


    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ($value ?? 0) / Tron::DIGITS,
        );
    }

    protected function stakedForBandwidth(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ($value ?? 0) / Tron::DIGITS,
        );
    }

    protected function stakedForEnergy(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ($value ?? 0) / Tron::DIGITS,
        );
    }

    protected function trx(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ($value ?? 0) / Tron::DIGITS,
        );
    }

    public function unstakes()
    {
        return $this->hasMany(Unstake::class);
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
        return DB::transaction(function () {
            $resource = Tron::getAccountResource($this->base58_check);
            $trc20_address = config('app')['trc20_address'];
            $response =  Tron::getAccountInfoByAddress($this->base58_check)->data[0];
            $usdt = collect($response->trc20)->first(fn ($v) => property_exists($v, $trc20_address));
            $frozenV2 = collect($response->frozenV2);
            $unfrozenV2 = collect($response->unfrozenV2);
            $this->update([
                'balance' => $usdt->$trc20_address ?? 0,
                'trx' => $response->balance ?? 0,
                'resource' => $resource,
                'staked_for_energy' => $frozenV2->first(fn ($v) => $v->type ?? null == 'ENERGY')->amount ?? 0,
                'staked_for_bandwidth' => $frozenV2->first(fn ($v) => !property_exists($v, 'type'))->amount ?? 0,
            ]);

            $this->unstakes()->delete();

            $unfrozenV2->each(function ($unstake) {
                if ($unstake->unfreeze_amount ?? null != null)
                    $this->unstakes()->create([
                        'amount' => $unstake->unfreeze_amount,
                        'type' => property_exists($unstake, 'type') ? $unstake->type : 'BANDWIDTH',
                        'withdrawable_at' => Carbon::createFromTimestamp($unstake->unfreeze_expire_time / 1000)
                    ]);
            });

            return $this;
        });
    }

    public function sendUSDT(string $to, int $amount)
    {
        return Tron::sendUSDT($to, $amount, $this);
    }

    public function freezeBalance(int $amount, string $resource = 'BANDWIDTH')
    {
        $tx =  Tron::freezeBalance($this->base58_check, $resource, $amount * Tron::DIGITS);
        $signed = Tron::signTransaction($tx, $this);
        return Tron::broadcastTransaction($signed);
    }


    public function unfreezeBalance(int $amount, string $resource = 'BANDWIDTH')
    {
        $tx =  Tron::unfreezeBalance($this->base58_check, $resource, $amount * Tron::DIGITS);
        $signed = Tron::signTransaction($tx, $this);
        return Tron::broadcastTransaction($signed);
    }


    public function withdrawUnfreezeBalance()
    {
        $tx =  Tron::withdrawExpireUnfreeze($this->base58_check);
        $signed = Tron::signTransaction($tx, $this);
        return Tron::broadcastTransaction($signed);
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
