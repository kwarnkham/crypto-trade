<?php

namespace App\Jobs;

use App\Enums\WithdrawStatus;
use App\Models\Withdraw;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWithdrawForExpire implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $withdarwId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $withdraw =  Withdraw::find($this->withdarwId);
        if ($withdraw->status == WithdrawStatus::CONFIRMED->value) $withdraw->update(['status' => WithdrawStatus::EXPIRED->value]);
    }
}
