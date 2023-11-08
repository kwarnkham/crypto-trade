<?php

namespace App\Http\Controllers;

use App\Enums\ExtractStatus;
use App\Enums\ExtractType;
use App\Enums\ResponseStatus;
use App\Jobs\ProcessConfirmedExtract;
use App\Models\Agent;
use App\Models\Extract;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExtractController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gte:0.000001'],
            'type' => ['required', Rule::in(ExtractType::toArray())],
            'to' => ['required', 'string'],
            'wallet_id' => ['required', Rule::exists('wallets', 'id')],
            'agent_extract_id' => ['required', 'unique:extracts,agent_extract_id']
        ]);
        $wallet = Wallet::find($data['wallet_id']);
        abort_unless(
            Str::startsWith($data['to'], 'T') && Wallet::validate($data['to']),
            ResponseStatus::BAD_REQUEST->value,
            'Wallet is invalid'
        );

        if ($data['type'] == ExtractType::USDT->value)
            abort_if(
                $data['amount'] > $wallet->balance,
                ResponseStatus::BAD_REQUEST->value,
                'Not enough USDT'
            );
        else if ($data['type'] == ExtractType::TRX->value)
            abort_if(
                $data['amount'] > $wallet->trx,
                ResponseStatus::BAD_REQUEST->value,
                'Not enough TRX'
            );


        $extract = DB::transaction(function () use ($wallet, $request, $data) {
            $agent = Agent::current($request);
            $extract = $wallet->extracts()->create([...$data, 'agent_id' => $agent->id]);
            if ($extract->type == ExtractType::USDT->value)
                $response = $wallet->sendUSDT($extract->to, $extract->amount);
            else if ($extract->type == ExtractType::TRX->value)
                $response = $wallet->sendTRX($extract->to, $extract->amount);

            abort_unless(
                ($response->code ?? null) == null,
                ResponseStatus::BAD_REQUEST->value,
                $response->code ?? json_encode($response)
            );

            $result = $response->result ?? false;
            $txid = $response->txid ?? false;
            if ($response != null && $result == true && $txid != false) {
                $extract->update([
                    'status' => ExtractStatus::CONFIRMED->value,
                    'txid' => $txid
                ]);

                ProcessConfirmedExtract::dispatch($txid, $extract->id)->delay(now()->addMinute());
            }
            return $extract;
        });

        return response()->json([
            'extract' => $extract
        ]);
    }

    public function find(Request $request, Extract $extract)
    {
        return response()->json(['extract' => $extract]);
    }
}
