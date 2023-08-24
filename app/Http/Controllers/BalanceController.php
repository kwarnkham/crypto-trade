<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function deposit(Request $request)
    {
        $agent = Agent::current($request);
        $data = $request->validate([
            'code' => ['required'],
            'name' => ['required']
        ]);

        $user = User::query()->where('agent_id', $agent->id)->where('code', $data['code'])->first();
        if (!is_null($user)) {
            if ($user->wallet != null) {
                return response()->json(['wallet' => $user->wallet->base58_check]);
            }
        }

        $wallet = Wallet::findAvailable();
        if (is_null($wallet)) return response()->json(['wallet' => '']);

        if (is_null($user)) {
            $user = User::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'agent_id' => $agent->id
            ]);

            $user->reserveWallet($wallet);

            return response()->json(['wallet' => $wallet->base58_check]);
        }

        $user->reserveWallet($wallet);

        return response()->json(['wallet' =>  $wallet->base58_check]);
    }
}
