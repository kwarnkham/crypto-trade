<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Transfer;
use App\Models\User;
use App\Services\Tron;
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




        $transfer = DB::transaction(function () use ($data) {
            $from = User::where('code', $data['from'])->first();
            $to = User::where('code', $data['to'])->first();
            $transfer = Transfer::create([
                'user_id' => $from->id,
                'recipient_id' => $to->id,
                'amount' => $data['amount'] * Tron::DIGITS,
                'fee' => Tron::DIGITS
            ]);

            $from->decrement('balance', $transfer->amount);

            $to->increment('balance', $transfer->amount - $transfer->fee);

            return $transfer;
        });


        return response()->json([
            'transfer' => $transfer
        ]);
    }
}
