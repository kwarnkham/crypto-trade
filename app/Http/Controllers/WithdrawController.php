<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Enums\WithdrawStatus;
use App\Models\Agent;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WithdrawController extends Controller
{
    public function store(Request $request)
    {
        $agent = Agent::current($request);
        $data = $request->validate([
            'code' => ['required', Rule::exists('users', 'code')->where('agent_id', $agent->id)],
            'amount' => ['required', 'numeric', 'integer', 'min:2'],
            'to' => ['required', 'string'],
        ]);
        abort_unless(Wallet::validate($data['to']), ResponseStatus::BAD_REQUEST->value, 'Wallet is invalid');
        $user = $agent->users()->where('code', $data['code'])->first();
        $withdrawAmount = $user->withdraws()->whereIn('status', [WithdrawStatus::PENDING->value, WithdrawStatus::CONFIRMED->value])->sum('amount');
        if ($user->balance < (($data['amount'] * 1000000) + $withdrawAmount)) abort(ResponseStatus::BAD_REQUEST->value, 'User has not enough balance');

        $user = User::query()->where('code', $data['code'])->where('agent_id', $agent->id)->first();
        $fees = 1000000;
        $amount = $data['amount'] * 1000000;
        if ($user->balance < ($amount + $fees)) abort(ResponseStatus::BAD_REQUEST->value, $user->balance . ' is not enough balance');

        $withdraw = Withdraw::create([
            'user_id' => $user->id,
            'to' => $data['to'],
            'amount' => $amount,
            'fees' => $fees
        ]);

        return response()->json(['withdraw' => $withdraw]);
    }

    public function confirm(Withdraw $withdraw)
    {
        if ($withdraw->status != WithdrawStatus::PENDING->value) abort(ResponseStatus::BAD_REQUEST->value, 'Can only confirm a pending withdraw');
        $result = $withdraw->confirm();
        if ($result == null) abort(ResponseStatus::BAD_REQUEST->value, 'No wallet have enough balance or send usdt failed');
        return response()->json([
            'withdraw' => $withdraw
        ]);
    }
}
