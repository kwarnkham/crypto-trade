<?php

namespace App\Jobs;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Attributes\WithoutRelations;

class ProcessConfirmedDeposit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(10);
    }

    /**
     * Create a new job instance.
     */
    public function __construct(#[WithoutRelations] public Deposit $deposit)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->deposit->attemptToComplete();
        $deposit = $this->deposit->fresh();
        if ($deposit->status == DepositStatus::CONFIRMED->value)
            ProcessConfirmedDeposit::dispatch($deposit)->delay(now()->addMinute());
    }
}
