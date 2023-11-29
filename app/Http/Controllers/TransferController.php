<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Agent;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $agent = Agent::current($request);

        $data = $request->validate([
            'from' => ['required', Rule::exists('users', 'code')->where('agent_id', $agent->id)],
            'to' => ['required', Rule::exists('users', 'code')->where('agent_id', $agent->id)],
            'amount' => ['required', 'numeric', 'gt:1'],
            'agent_transaction_id' => ['required', 'unique:extracts,agent_transaction_id']
        ]);

        $from = User::where('code', $data['from'])->first();
        if (
            $data['amount'] >
            $from->balance - $from->withdrawingAmount()
        )
            abort(ResponseStatus::BAD_REQUEST->value, 'User does not have enough balance');
        $transfer = DB::transaction(function () use ($data, $from) {
            $to = User::where('code', $data['to'])->first();
            $fee = 1;
            $transfer = Transfer::create([
                'user_id' => $from->id,
                'recipient_id' => $to->id,
                'amount' => $data['amount'],
                'fee' => $fee,
                'agent_transaction_id' => $data['agent_transaction_id']
            ]);

            $from->update(['balance' => $from->balance - $data['amount']]);

            $to->update(['balance' => $to->balance + $data['amount'] - $fee]);

            $transfer->charge()->create(['amount' => $fee]);

            $transfer->balanceLogs()->create([
                'user_id' => $transfer->user_id,
                'amount' => $transfer->amount * -1
            ]);

            $transfer->balanceLogs()->create([
                'user_id' => $transfer->recipient_id,
                'amount' => $transfer->amount - $fee
            ]);

            return $transfer;
        });


        return response()->json([
            'transfer' => $transfer
        ]);
    }

    public function index(Request $request)
    {
        $query = Transfer::query()->with(['user', 'recipient'])->latest('id');
        return response()->json($query->paginate($request->per_page ?? 10));
    }
}
