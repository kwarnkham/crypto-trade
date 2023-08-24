<?php

namespace App\Models;

use App\Enums\DepositStatus;
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
}
