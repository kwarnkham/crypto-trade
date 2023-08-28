<?php

namespace App\Models;

use App\Enums\WithdrawStatus;
use App\Jobs\ProcessConfirmWithdraw;
use App\Jobs\ProcessWithdrawForExpire;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Withdraw extends Model
{
    use HasFactory;

    protected $guarded = [''];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function confirm()
    {
        $wallet = Wallet::withdrawable($this->amount);
        if ($wallet == null || $this->to == $wallet->base58_check) return;

        $this->update(['status' => WithdrawStatus::CONFIRMED->value, 'from' => $wallet->base58_check]);
        $response = $wallet->sendUSDT($this->to, $this->amount - $this->fees);
        $result = $response->result ?? false;
        $txid = $response->txid ?? false;
        if ($result == true && $txid != false) {
            ProcessConfirmWithdraw::dispatch($txid, $this->id)->delay(now()->addMinute());
            ProcessWithdrawForExpire::dispatch($this->id)->delay(now()->addMinutes(5));
            return $response;
        }
    }

    public function complete(array $tx)
    {
        DB::transaction(function () use ($tx) {
            $transaction = Transaction::create([
                'from' => $this->from,
                'to' => $this->to,
                'transaction_id' => $tx['id'],
                'token_address' => $tx['token_address'],
                'block_timestamp' => $tx['block_timestamp'],
                'value' => $this->amount,
                'type' => 'Transfer'
            ]);
            $this->update(['status' => WithdrawStatus::COMPLETED->value, 'transaction_id' => $transaction->id]);
            $user = $this->user;
            $user->update(['balance' => $user->balance - $this->amount]);
        });
    }
}
