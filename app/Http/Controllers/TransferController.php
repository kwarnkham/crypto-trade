<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Enums\WithdrawStatus;
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
            'amount' => ['required', 'numeric', 'integer', 'gt:1'],
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
                'fee' => $fee
            ]);

            $from->update(['balance' => $from->balance - $data['amount']]);

            $to->update(['balance' => $to->balance + $data['amount'] - $fee]);

            $transfer->charge()->create(['amount' => $fee]);

            return $transfer;
        });


        return response()->json([
            'transfer' => $transfer
        ]);
    }
}
