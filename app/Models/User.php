<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class User extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    function reserveWallet(Wallet $wallet)
    {
        $wallet->user_id = $this->id;
        $wallet->reserved_at = now();
        $wallet->save();
    }
}
