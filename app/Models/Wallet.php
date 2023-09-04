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
use Illuminate\Support\Facades\Log;

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
            set: fn ($value) => json_encode($value ?? ''),
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

    public static function withdrawable(int $amount, array $excluded = []): ?Wallet
    {
        if ($amount < Tron::DIGITS) return null;
        $wallet = Wallet::query()
            ->whereRaw(
                'balance >= IFNULL((
            SELECT SUM(amount)
            FROM withdraws
            WHERE withdraws.wallet_id = wallets.id
            AND status IN (?, ?)), 0) + ?',
                [WithdrawStatus::PENDING->value, WithdrawStatus::CONFIRMED->value, $amount]
            )
            ->where(function ($q) {
                return $q->where('trx', '>=', (config('app')['min_trx_for_transaction']) * Tron::DIGITS)->orWhere(function ($query) {
                    $query->where('energy', '>=', config('app')['min_energy_for_transaction'])
                        ->where('bandwidth', '>=', 500);
                });
            })
            ->when(count($excluded) > 0, fn ($q) => $q->whereNotIn('id', $excluded))
            ->whereNotNull('activated_at')
            ->first();

        if ($wallet == null) return $wallet;

        $oldBalance = $wallet->balance;

        $wallet->updateBalance();

        if ($wallet->balance < $oldBalance) {
            return Wallet::withdrawable($amount, [$wallet->id, ...$excluded]);
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
            $unfrozenV2 = collect($response->unfrozenV2 ?? []);
            $energy = ($resource['EnergyLimit'] ?? 0) - ($resource['EnergyUsed'] ?? 0);
            $bandwidth = ($resource['freeNetLimit'] ?? 0) - ($resource['freeNetUsed'] ?? 0) - ($resource['NetUsed'] ?? 0) + ($resource['NetLimit'] ?? 0);
            $this->update([
                'balance' => $usdt->$trc20_address ?? 0,
                'trx' => $response->balance ?? 0,
                'resource' => $resource,
                'energy' => $energy,
                'bandwidth' => $bandwidth,
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
        return Tron::sendUSDT($to, $amount, $this->private_key, $this->base58_check);
    }

    public function freezeBalance(int $amount, string $resource = 'BANDWIDTH')
    {
        $tx =  Tron::freezeBalance($this->base58_check, $resource, $amount * Tron::DIGITS);
        $signed = Tron::signTransaction($tx, $this->private_key);
        return Tron::broadcastTransaction($signed);
    }


    public function cancelAllUnfreezeV2()
    {
        $tx =  Tron::cancelAllUnfreezeV2($this->base58_check);
        $signed = Tron::signTransaction($tx, $this->private_key);
        return Tron::broadcastTransaction($signed);
    }

    public function unfreezeBalance(int $amount, string $resource = 'BANDWIDTH')
    {
        $tx =  Tron::unfreezeBalance($this->base58_check, $resource, $amount * Tron::DIGITS);
        $signed = Tron::signTransaction($tx, $this->private_key);
        return Tron::broadcastTransaction($signed);
    }


    public function withdrawUnfreezeBalance()
    {
        $tx =  Tron::withdrawExpireUnfreeze($this->base58_check);
        $signed = Tron::signTransaction($tx, $this->private_key);
        return Tron::broadcastTransaction($signed);
    }

    public function syncTxs(array $options = [])
    {
        $response = Tron::getTransactionInfoByAccountAddress($this->base58_check, [...$options, 'only_confirmed' => true]);
        Log::info($response->data[0]->txID);
        if (($response->meta->fingerprint ?? false) && ($response->meta->links ?? false) && $response->meta->links->next) {
            $this->syncTxs([...$options, 'fingerprint' => $response->meta->fingerprint]);
        }
    }

    public function syncTrc20Txs(array $options = [])
    {
        ['data' => $data, 'meta' => $meta] = get_object_vars(Tron::getTRC20TransactionInfoByAccountAddress($this->base58_check, [...$options, 'only_confirmed' => true]));
        $transactions = collect($data);
        $transactions->each(function ($tx) {
            if (Transaction::query()->where('transaction_id', $tx->transaction_id)->doesntExist()) {
                $res = Tron::getSolidityTransactionInfoById($tx->transaction_id);
                Transaction::create([
                    'from' => $tx->from,
                    'to' => $tx->to,
                    'transaction_id' => $tx->transaction_id,
                    'token_address' => $tx->token_info->address,
                    'block_timestamp' => $tx->block_timestamp,
                    'value' => $tx->value,
                    'type' => $tx->type,
                    'receipt' => $res->receipt ?? [],
                    'fee' => $res->fee ?? 0
                ]);
            }
        });
        if (($meta->fingerprint ?? false) && ($meta->links ?? false) && $meta->links->next) {
            $transactions = null;
            $data = null;
            $this->syncTrc20Txs([...$options, 'fingerprint' => $meta->fingerprint]);
        }
    }
}
