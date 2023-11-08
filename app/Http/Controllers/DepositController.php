<?php

namespace App\Http\Controllers;

use App\Enums\DepositStatus;
use App\Enums\ResponseStatus;
use App\Models\Agent;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function store(Request $request)
    {
        $agent = Agent::current($request);
        $data = $request->validate([
            'code' => ['required'],
            'name' => ['required'],
            'amount' => ['required', 'numeric', 'gte:0.000001']
        ]);

        $user = $agent->users()->where('code', $data['code'])->first();
        if ($user != null && $user->getActiveDeposit() != null) {
            abort(ResponseStatus::BAD_REQUEST->value, 'Please pay and wait for previous deposit to complete');
        }

        $wallet = Wallet::findAvailable();

        if ($wallet == null) abort(ResponseStatus::BAD_REQUEST->value, 'There is no avaliable wallet to handle deposit.');

        if ($user == null)
            $user = User::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'agent_id' => $agent->id
            ]);

        $deposit = $user->deposits()->create([
            'wallet_id' => $wallet->id,
            'amount' => $data['amount']
        ]);
        return response()->json(['wallet' =>  $wallet->base58_check, 'deposit' => $deposit]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['sometimes']
        ]);
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
