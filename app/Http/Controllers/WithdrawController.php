<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Enums\WithdrawStatus;
use App\Http\Requests\FilterWithdrawRequest;
use App\Http\Requests\StoreWithdrawRequest;
use App\Models\Agent;
use App\Models\Withdraw;
use Illuminate\Http\Request;

class WithdrawController extends Controller
{
    public function store(StoreWithdrawRequest $request)
    {
        $fee = 1;
        $data = $request->validated();
        $withdraw = Withdraw::create([
            'user_id' => $request->agent->users()->where('code', $data['code'])->first()->id,
            'to' => $data['to'],
            'amount' => $data['amount'],
            'fee' => $fee,
            'agent_transaction_id' => $data['agent_transaction_id']
        ]);

        return response()->json(['withdraw' => $withdraw->fresh()]);
    }

    public function confirm(Withdraw $withdraw)
    {
        if ($withdraw->status != WithdrawStatus::PENDING->value) abort(ResponseStatus::BAD_REQUEST->value, 'Can only confirm a pending withdraw');
        $result = $withdraw->confirm();
        if ($result == null) abort(ResponseStatus::BAD_REQUEST->value, 'Send usdt failed');
        return response()->json([
            'withdraw' => $withdraw
        ]);
    }

    public function index(FilterWithdrawRequest $request)
    {
        $filters = $request->validated();
        $query = Withdraw::query()->filter($filters)->with(['user.agent', 'wallet']);
        return response()->json($query->paginate($request->per_page ?? 10));
    }

    public function cancel(Withdraw $withdraw)
    {
        abort_if($withdraw->status != WithdrawStatus::PENDING->value, ResponseStatus::BAD_REQUEST->value, 'Can only cancel a pending withdraw');
        $withdraw->update([
            'status' => WithdrawStatus::CANCELED->value
        ]);
        return response()->json(['withdraw' => $withdraw]);
    }

    public function find(Request $request, Withdraw $withdraw)
    {
        $agent = Agent::current($request);
        if ($agent) {
            abort_unless($withdraw->user->agent_id == $agent->id, ResponseStatus::NOT_FOUND->value);
        }
        return response()->json(['withdraw' => $withdraw->load(['user.agent', 'wallet'])]);
    }
}
