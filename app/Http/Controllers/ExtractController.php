<?php

namespace App\Http\Controllers;

use App\Enums\ExtractStatus;
use App\Enums\ExtractType;
use App\Enums\ResponseStatus;
use App\Http\Requests\StoreExtractRequest;
use App\Jobs\ProcessConfirmedExtract;
use App\Models\Agent;
use App\Models\Extract;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExtractController extends Controller
{
    public function store(StoreExtractRequest $request)
    {
        $data = $request->all();
        $wallet = Wallet::find($data['wallet_id']);

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
