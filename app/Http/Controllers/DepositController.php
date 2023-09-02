<?php

namespace App\Http\Controllers;

use App\Enums\DepositStatus;
use App\Enums\ResponseStatus;
use App\Jobs\ProcessConfirmedDeposit;
use App\Models\Agent;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Tron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    public function store(Request $request)
    {
        $agent = Agent::current($request);
        $data = $request->validate([
            'code' => ['required'],
            'name' => ['required'],
            'amount' => ['required', 'numeric', 'integer', 'gt:0']
        ]);

        $user = $agent->users()->where('code', $data['code'])->first();
        if ($user != null && $user->getActiveDeposit() != null) {
            abort(ResponseStatus::BAD_REQUEST->value, 'Please pay and wait for previous deposit to complete');
        }

        $wallet = Wallet::findAvailable();

        if ($wallet == null) return response()->json(['wallet' => '']);

        if ($user == null)
            $user = User::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'agent_id' => $agent->id
            ]);

        $deposit = DB::transaction(function () use ($wallet, $user, $data) {
            $wallet->update(['reserved_at' => now()]);
            return $user->deposits()->create([
                'wallet_id' => $wallet->id,
                'amount' => $data['amount']
            ]);
        });

        return response()->json(['wallet' =>  $wallet->base58_check, 'deposit' => $deposit]);
    }

    public function index(Request $request)
    {
        $query = Deposit::query()->latest('id')->with(['wallet', 'user.agent']);
        return response()->json($query->paginate($request->per_page ?? 10));
    }

    public function confirm(Deposit $deposit)
    {
        abort_if($deposit->status != DepositStatus::PENDING->value, ResponseStatus::BAD_REQUEST->value, 'Can only confirm a pending deposit');

        $deposit->update(['status' => DepositStatus::CONFIRMED->value]);

        ProcessConfirmedDeposit::dispatch($deposit->id);

        return response()->json(['deposit' => $deposit]);
    }

    public function cancel(Deposit $deposit)
    {

        if ($deposit->status != DepositStatus::PENDING->value) abort(ResponseStatus::BAD_REQUEST->value, 'Can only cancel a pending deposit');

        $deposit->update(['status' => DepositStatus::CANCELED->value]);

        return response()->json(['deposit' => $deposit->load(['user.agent', 'wallet'])]);
    }
}
