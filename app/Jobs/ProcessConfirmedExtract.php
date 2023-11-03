<?php

namespace App\Jobs;

use App\Enums\ExtractStatus;
use App\Enums\ExtractType;
use App\Models\Extract;
use App\Services\Tron;
use App\Utility\Conversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessConfirmedExtract implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $txid, public int $extractId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $maxAttempts = 5;
        $extract = Extract::find($this->extractId);
        $extract->increment('attempts');
        if ($extract->status != ExtractStatus::CONFIRMED->value) return;

        $response = Tron::getSolidityTransactionInfoById($this->txid);
        $receipt = $response->receipt ?? null;
        if ($extract->type == ExtractType::TRX->value)
            $receipt->result = $response->result ?? "SUCCESS";

        if ($receipt != null && $receipt->result === "SUCCESS") {
            $tx = [];
            $tx['id'] = $response->id;
            $tx['block_timestamp'] = $response->blockTimeStamp;
            $tx['token_address'] = property_exists($response, 'contract_address') ? Conversion::hexString2Base58check($response->contract_address) : 'TRX';
            $tx['fee'] = $response->fee ?? 0;
            $tx['receipt'] = $response->receipt;
            $response = Tron::getSolidityTransactionById($this->txid);
            $ret = $response->ret ?? null;
            if ($ret != null) {
                if (is_array($ret)) {
                    $result = $ret[0]->contractRet ?? false;
                } else {
                    $result = $ret->contractRet ?? false;
                }

                if ($result === "SUCCESS") {
                    $extract->complete($tx);
                    return;
                }
            }
        }
        if ($extract->attempts < $maxAttempts)
            ProcessConfirmedExtract::dispatch($this->txid, $this->extractId)->delay(now()->addMinute());
        else $extract->update(['status' => ExtractStatus::CANCELED->value]);
    }
}