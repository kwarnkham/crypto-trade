<?php

namespace App\Jobs;

use App\Enums\WithdrawStatus;
use App\Models\Withdraw;
use App\Services\Tron;
use App\Utility\Conversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessConfirmWithdraw implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $txid, public int $withdrawId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $withdraw = Withdraw::find($this->withdrawId);
        if ($withdraw->status == WithdrawStatus::CONFIRMED->value) {
            $response = Tron::getSolidityTransactionInfoById($this->txid);
            $receipt = $response->receipt ?? null;
            if ($receipt == null || $receipt->result !== "SUCCESS") ProcessConfirmWithdraw::dispatch($this->txid, $this->withdrawId)->delay(now()->addMinute());
            else if ($receipt->result === "SUCCESS") {
                $tx = [];
                $tx['id'] = $response->id;
                $tx['block_timestamp'] = $response->blockTimeStamp;
                $tx['token_address'] = Conversion::hexString2Base58check($response->contract_address);
                $tx['fee'] = $response->fee;
                $tx['receipt'] = json_encode($response->receipt);
                $response = Tron::getSolidityTransactionById($this->txid);
                $ret = $response->ret ?? null;
                if ($ret == null) ProcessConfirmWithdraw::dispatch($this->txid, $this->withdrawId)->delay(now()->addMinute());
                else {
                    if (is_array($ret)) {
                        $result = $ret[0]->contractRet ?? false;
                    } else {
                        $result = $ret->contractRet ?? false;
                    }
                    if ($result !== "SUCCESS") ProcessConfirmWithdraw::dispatch($this->txid, $this->withdrawId)->delay(now()->addMinute());
                    else if ($result === "SUCCESS") {
                        $withdraw->complete($tx);
                    }
                }
            }
            // if ($ret);
            // if()
        }
    }
}
