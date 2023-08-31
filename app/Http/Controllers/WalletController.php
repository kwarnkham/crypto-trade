<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Wallet;
use App\Services\Tron;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function store(Request $request)
    {
        $wallet = Wallet::generate();

        return response()->json(['wallet' => $wallet->fresh()]);
    }

    public function index(Request $request)
    {
        $query = Wallet::query()->with(['unstakes']);

        return response()->json($query->paginate($request->per_page ?? 20));
    }

    public function activate(Wallet $wallet)
    {
        if (Tron::isActivated($wallet->base58_check)) {
            $wallet->update(['activated_at' => now()]);
            $wallet->updateBalance();
        }
        return response()->json(['wallet' => $wallet]);
    }

    public function find(Wallet $wallet)
    {
        return response()->json(['wallet' => $wallet->updateBalance()->load('unstakes')]);
    }

    public function stake(Request $request, Wallet $wallet)
    {
        $data = $request->validate([
            'type' => ['required', 'in:ENERGY,BANDWIDTH'],
            'amount' => ['required', 'numeric', 'gt:0', 'integer', 'lte:' . ($wallet->trx)]
        ]);

        $response =  $wallet->freezeBalance($data['amount'], $data['type']);

        if (($response->result ?? false) != true) abort(ResponseStatus::BAD_REQUEST->value, 'Tron network error');

        return response()->json([
            'wallet' => $wallet->updateBalance()
        ]);
    }

    public function unstake(Request $request, Wallet $wallet)
    {
        $data = $request->validate([
            'type' => ['required', 'in:ENERGY,BANDWIDTH'],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'integer',
                'lte:' . ($request->type == 'ENERGY' ? $wallet->staked_for_energy : $wallet->staked_for_bandwidth)
            ]
        ]);

        $response =  $wallet->unfreezeBalance($data['amount'], $data['type']);

        if (($response->result ?? false) != true) abort(ResponseStatus::BAD_REQUEST->value, 'Tron network error');

        return response()->json([
            'wallet' => $wallet->updateBalance()->load('unstakes')
        ]);
    }


    public function withdrawUnstake(Wallet $wallet)
    {
        $response =  $wallet->withdrawUnfreezeBalance();
        if (($response->result ?? false) != true) abort(ResponseStatus::BAD_REQUEST->value, 'Tron network error');
        return response()->json([
            'wallet' => $wallet->updateBalance()->load('unstakes')->load('unstakes')
        ]);
    }

    public function cancelUnstake(Wallet $wallet)
    {
        $response =  $wallet->cancelAllUnfreezeV2();
        if (($response->result ?? false) != true) abort(ResponseStatus::BAD_REQUEST->value, 'Tron network error');
        return response()->json([
            'wallet' => $wallet->updateBalance()->load('unstakes')->load('unstakes')
        ]);
    }
}
