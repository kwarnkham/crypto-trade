<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Agent;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BalanceController extends Controller
{
    public function deposit(Request $request)
    {
        $agent = Agent::current($request);
        $data = $request->validate([
            'code' => ['required'],
            'name' => ['required'],
            'amount' => ['required', 'numeric']
        ]);

        $user = $agent->users()->where('code', $data['code'])->first();
        if (!is_null($user)) {
            if ($user->wallet != null) {
                abort(ResponseStatus::BAD_REQUEST->value, 'Please complete the previous payment first');
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
        }

        $user->reserveWallet($wallet, $data['amount']);

        return response()->json(['wallet' =>  $wallet->base58_check]);
    }

    public function cancelDeposit(Request $request)
    {
        $agent = Agent::current($request);
        $data = $request->validate([
            'code' => ['required', Rule::exists('users', 'code')->where('agent_id', $agent->id)],
        ]);
        $user = $agent->users()->where('code', $data['code'])->first();

        if ($user->wallet == null) abort(ResponseStatus::BAD_REQUEST->value, 'No wallet is reserved for this user');

        $user->wallet->removeReservation();

        return response()->json(['message' => 'success']);
    }

    public function confirmDeposit(Request $request)
    {
        $agent = Agent::current($request);
        $data = $request->validate([
            'code' => ['required', Rule::exists('users', 'code')->where('agent_id', $agent->id)],
        ]);
        $user = $agent->users()->where('code', $data['code'])->first();

        if ($user->wallet == null) abort(ResponseStatus::BAD_REQUEST->value, 'No wallet is reserved for this user');
    }
}
