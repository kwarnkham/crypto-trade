<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Services\Tron;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Deposit extends Model
{
    use Filterable, HasFactory;

    protected $with = ['user'];

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function balanceLogs(): MorphMany
    {
        return $this->morphMany(BalanceLog::class, 'loggable');
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }


    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ($value ?? 0) / Tron::DIGITS,
            set: fn (?string $value) => ($value ?? 0) * Tron::DIGITS,
        );
    }

    public function complete(Transaction $transaction)
    {
        $this->update(['transaction_id' => $transaction->id, 'status' => DepositStatus::COMPLETED->value]);
        $this->refresh();
    }

    public function attemptToComplete()
    {
        if ($this->status != DepositStatus::CONFIRMED->value) return;
        $this->increment('attempts');

        Log::info("Attempt to complete a confirmed deposit (id => $this->id / agent_transaction_id=> $this->agent_transaction_id)");

        $transactions = collect(Tron::getTRC20TransactionInfoByAccountAddress($this->wallet->base58_check, [
            'only_confirmed' => true,
            'limit' => 10,
            'contract_address' => config('app')['trc20_address'],
            'only_to' => true
        ])->data);

        $matchedTx = $transactions->first(
            fn ($tx) =>
            Transaction::query()->where('transaction_id', $tx->transaction_id)->doesntExist() && (($this->getRawOriginal('amount')) == $tx->value)
        );
        if ($matchedTx != null) {
            DB::transaction(function () use ($matchedTx) {
                $res = Tron::getSolidityTransactionInfoById($matchedTx->transaction_id);
                $transaction = Transaction::create([
                    'from' => $matchedTx->from,
                    'to' => $matchedTx->to,
                    'transaction_id' => $matchedTx->transaction_id,
                    'token_address' => $matchedTx->token_info->address,
                    'block_timestamp' => $matchedTx->block_timestamp,
                    'value' => $matchedTx->value,
                    'type' => $matchedTx->type,
                    'receipt' => $res->receipt ?? [],
                    'fee' => $res->fee ?? 0
                ]);
                $this->complete($transaction);
            });
        }
    }

    public static function produce(array $data): array
    {
        $agent = $data['agent'];
        $wallet = $data['wallet'];
        $user = $agent->users()->where('code', $data['code'])->first();

        if ($user == null)
            $user = User::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'agent_id' => $agent->id
            ]);

        $deposit = $user->deposits()->create([
            'wallet_id' => $wallet->id,
            'amount' => $data['amount'],
            'agent_transaction_id' => $data['agent_transaction_id']
        ]);

        return [$wallet, $deposit];
    }
}
