<?php

namespace App\Models;

use App\Enums\ResponseStatus;
use App\Enums\WithdrawStatus;
use App\Jobs\ProcessConfirmWithdraw;
use App\Services\Tron;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

class Withdraw extends Model
{
    use HasFactory;

    protected $guarded = [''];

    public function charge(): MorphOne
    {
        return $this->morphOne(Charge::class, 'chargeable');
    }

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

    protected function fee(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ($value ?? 0) / Tron::DIGITS,
            set: fn (string $value) => ($value ?? 0) * Tron::DIGITS,
        );
    }


    public function confirm()
    {
        $wallet = Wallet::withdrawable($this->getRawOriginal('amount'));
        if ($wallet == null || $this->to == $wallet->base58_check) abort(ResponseStatus::BAD_REQUEST->value, 'No wallet availabe');
        [$result, $txid, $response] = DB::transaction(function () use ($wallet) {
            $response = $wallet->sendUSDT($this->to, $this->amount - $this->fee);
            if (($response->code ?? null) != null) abort(ResponseStatus::BAD_REQUEST->value, $response->code);
            $result = $response->result ?? false;
            $txid = $response->txid ?? false;
            return [$result, $txid, $response];
        });

        if ($result == true && $txid != false) {
            $this->update([
                'status' => WithdrawStatus::CONFIRMED->value,
                'wallet_id' => $wallet->id,
                'txid' => $txid
            ]);

            ProcessConfirmWithdraw::dispatch($txid, $this->id)->delay(now()->addMinute());
            return $response;
        }
    }

    public function complete(array $tx)
    {
        DB::transaction(function () use ($tx) {
            $transaction = Transaction::create([
                'from' => $this->wallet->base58_check,
                'to' => $this->to,
                'transaction_id' => $tx['id'],
                'token_address' => $tx['token_address'],
                'block_timestamp' => $tx['block_timestamp'],
                'value' => ($this->amount - $this->fee) * Tron::DIGITS,
                'type' => 'Transfer',
                'fee' => $tx['fee'],
                'receipt' => $tx['receipt']
            ]);
            $this->update(['status' => WithdrawStatus::COMPLETED->value, 'transaction_id' => $transaction->id]);
            $user = $this->user;
            $user->update(['balance' => $user->balance - $this->amount]);

            $this->charge()->create(['amount' => $this->fee]);

            $this->wallet->updateBalance();
        });
    }
}
