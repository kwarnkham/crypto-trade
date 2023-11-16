<?php

namespace App\Models;

use App\Enums\ExtractStatus;
use App\Services\Tron;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Extract extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ($value ?? 0) / Tron::DIGITS,
            set: fn (string $value) => ($value ?? 0) * Tron::DIGITS,
        );
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
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
                'value' => ($this->amount) * Tron::DIGITS,
                'type' => 'Transfer',
                'fee' => $tx['fee'],
                'receipt' => $tx['receipt']
            ]);
            $this->update(['status' => ExtractStatus::COMPLETED->value, 'transaction_id' => $transaction->id]);

            $this->wallet->updateBalance();
        });
    }
}
