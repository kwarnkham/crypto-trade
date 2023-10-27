<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Enums\WithdrawStatus;
use App\Models\Agent;
use App\Models\Wallet;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WithdrawController extends Controller
{
    public function store(Request $request)
    {
        $agent = Agent::current($request);
        $data = $request->validate([
            'code' => ['required', Rule::exists('users', 'code')->where('agent_id', $agent->id)],
            'amount' => ['required', 'numeric', 'integer', 'gt:1'],
            'to' => ['required', 'string'],
        ]);
        abort_unless(Str::startsWith($data['to'], 'T') && Wallet::validate($data['to']), ResponseStatus::BAD_REQUEST->value, 'Wallet is invalid');
        $user = $agent->users()->where('code', $data['code'])->first();

        $fee = 1;
        $amount = $data['amount'];
        $deductibleAmount = $amount + $user->withdrawingAmount();

        if ($user->balance < $deductibleAmount) {
            abort(ResponseStatus::BAD_REQUEST->value, 'User has not enough balance');
        }

        $withdraw = Withdraw::create([
            'user_id' => $user->id,
            'to' => $data['to'],
            'amount' => $amount,
            'fee' => $fee,
        ]);

        return response()->json(['withdraw' => $withdraw]);
    }

    public function confirm(Withdraw $withdraw)
    {
        if ($withdraw->status != WithdrawStatus::PENDING->value) {
            abort(ResponseStatus::BAD_REQUEST->value, 'Can only confirm a pending withdraw');
        }
        $result = $withdraw->confirm();
        if ($result == null) {
            abort(ResponseStatus::BAD_REQUEST->value, 'Send usdt failed');
        }

        return response()->json([
            'withdraw' => $withdraw,
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['sometimes'],
        ]);
        $query = Withdraw::query()->filter($filters)->with(['user.agent', 'wallet']);

        return response()->json($query->paginate($request->per_page ?? 10));
    }

    public function cancel(Withdraw $withdraw)
    {
        abort_if($withdraw->status != WithdrawStatus::PENDING->value, ResponseStatus::BAD_REQUEST->value, 'Can only cancel a pending withdraw');
        $withdraw->update([
            'status' => WithdrawStatus::CANCELED->value,
        ]);

        return response()->json(['withdraw' => $withdraw]);
    }
}
