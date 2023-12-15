<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Http\Requests\StakeWalletRequest;
use App\Http\Requests\UnstakeWalletRequest;
use App\Models\Wallet;
use App\Services\Tron;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'agent_id' => ['required', 'exists:agents,id']
        ]);
        return response()->json(['wallet' => Wallet::generate($data['agent_id'])]);
    }

    public function validateAddress(Request $request)
    {
        $request->validate([
            'wallet_address' => ['required']
        ]);

        return response()->json(Tron::validateAddress($request->wallet_address));
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => ['sometimes']
        ]);

        $query = Wallet::query()->filter($validated)->with(['unstakes']);

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

    public function stake(StakeWalletRequest $request, Wallet $wallet)
    {
        return response()->json([
            'wallet' => $wallet->updateBalance()->load(['unstakes'])
        ]);
    }

    public function unstake(UnstakeWalletRequest $request, Wallet $wallet)
    {
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
