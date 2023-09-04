<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Services\Tron;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Deposit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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

    public function complete(Transaction $transaction)
    {
        $this->update(['transaction_id' => $transaction->id, 'status' => DepositStatus::COMPLETED->value]);
        $user = $this->user;
        $user->update(['balance' => $user->balance + $this->amount]);
        $this->refresh();
    }

    public function attemptToComplete()
    {
        if ($this->status != DepositStatus::CONFIRMED->value) return;
        $this->increment('attempts');

        $transactions = collect(Tron::getTRC20TransactionInfoByAccountAddress($this->wallet->base58_check, [
            'only_confirmed' => true,
            'limit' => 10,
            'contract_address' => config('app')['trc20_address'],
            'only_to' => true
        ])->data);

        $transactions->each(function ($tx) {
            DB::transaction(function () use ($tx) {
                if (Transaction::query()->where('transaction_id', $tx->transaction_id)->doesntExist()) {
                    if (($this->getRawOriginal('amount')) == $tx->value) {
                        $res = Tron::getSolidityTransactionInfoById($tx->transaction_id);
                        $transaction = Transaction::create([
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
                        $this->complete($transaction);
                        $this->wallet->updateBalance();
                    }
                }
            });
        });
    }
}
