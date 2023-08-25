<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Services\Tron;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function complete(Transaction $transaction)
    {
        $this->update(['transaction_id' => $transaction->id, 'status' => DepositStatus::COMPLETED->value]);
        $user = $this->user;
        $user->update(['balance' => $user->balance + $this->amount]);
    }

    public function attemptToComplete()
    {
        if ($this->status != DepositStatus::CONFIRMED->value) return;
        $transactions = collect(Tron::getTRC20TransactionInfoByAccountAddress($this->wallet->base58_check, [
            'only_confirmed' => true,
            'limit' => 10,
            'contract_address' => 'TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs',
            'only_to' => true
        ])->data);

        $transactions->each(function ($tx) {
            if (Transaction::query()->where('transaction_id', $tx->transaction_id)->doesntExist()) {
                $transaction = Transaction::create([
                    'from' => $tx->from,
                    'to' => $tx->to,
                    'transaction_id' => $tx->transaction_id,
                    'token_address' => $tx->token_info->address,
                    'block_timestamp' => $tx->block_timestamp,
                    'value' => $tx->value,
                    'type' => $tx->type
                ]);

                if ($this->amount == $transaction->value) {
                    $this->complete($transaction);
                    $this->wallet->updateBalance();
                }
            }
        });
    }
}
