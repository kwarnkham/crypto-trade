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
use Illuminate\Support\Facades\Log;

class ProcessConfirmedWithdraw implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $txid, public int $withdrawId)
    {
        //
    }

    public function uniqueId(): string
    {
        return $this->withdrawId;
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 10;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 10;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $maxAttempts = 5;
        $withdraw = Withdraw::find($this->withdrawId);
        $withdraw->increment('attempts');
        Log::info("Attempt to complete a confirmed withdraw (id => $withdraw->id / agent_transaction_id=> $withdraw->agent_transaction_id)");
        if ($withdraw->status != WithdrawStatus::CONFIRMED->value) return;

        $response = Tron::getSolidityTransactionInfoById($this->txid);
        $receipt = $response->receipt ?? null;

        if ($receipt != null && $receipt->result === "SUCCESS") {
            $tx = [];
            $tx['id'] = $response->id;
            $tx['block_timestamp'] = $response->blockTimeStamp;
            $tx['token_address'] = Conversion::hexString2Base58check($response->contract_address);
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
                    $withdraw->complete($tx);
                    return;
                }
            }
        }
        if ($withdraw->attempts < $maxAttempts)
            ProcessConfirmedWithdraw::dispatch($this->txid, $this->withdrawId)->delay(now()->addMinute());
        else $withdraw->update(['status' => WithdrawStatus::CANCELED->value]);
    }
}
