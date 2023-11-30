<?php

namespace App\Http\Controllers;

use App\Enums\DepositStatus;
use App\Enums\ResponseStatus;
use App\Http\Requests\FilterDepositRequest;
use App\Http\Requests\StoreDepositRequest;
use App\Models\Agent;
use App\Models\Deposit;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function store(StoreDepositRequest $request)
    {
        [$wallet, $deposit] = Deposit::produce([...$request->validated(), ...['agent' => $request->agent, 'wallet' => $request->wallet]]);
        return response()->json(['wallet' =>  $wallet->base58_check, 'deposit' => $deposit]);
    }

    public function index(FilterDepositRequest $request)
    {
        $filters = $request->validated();
        $agent = Agent::current($request);

        $query = Deposit::query()->filter($filters)->latest('id')->with(['wallet', 'user.agent']);
        if ($agent) $query->whereRelation('user', 'agent_id', '=', $agent->id);
        return response()->json($query->paginate($request->per_page ?? 10));
    }

    public function confirm(Deposit $deposit)
    {
        abort_unless(
            $deposit->status == DepositStatus::PENDING->value,
            ResponseStatus::BAD_REQUEST->value,
            'Can only confirm a pending deposit'
        );

        $deposit->update(['status' => DepositStatus::CONFIRMED->value]);

        return response()->json(['deposit' => $deposit]);
    }

    public function cancel(Deposit $deposit)
    {
        if ($deposit->status != DepositStatus::PENDING->value) abort(ResponseStatus::BAD_REQUEST->value, 'Can only cancel a pending deposit');

        $deposit->update(['status' => DepositStatus::CANCELED->value]);

        return response()->json(['deposit' => $deposit->load(['user.agent', 'wallet'])]);
    }

    public function find(Request $request, Deposit $deposit)
    {
        $agent = Agent::current($request);
        if ($agent) {
            abort_unless($deposit->user->agent_id == $agent->id, ResponseStatus::NOT_FOUND->value);
        }
        return response()->json(['deposit' => $deposit->load(['user.agent', 'wallet'])]);
    }
}
