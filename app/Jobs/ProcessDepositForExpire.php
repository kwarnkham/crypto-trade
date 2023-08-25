<?php

namespace App\Jobs;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDepositForExpire implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $this->deposit = $this->deposit->fresh();
        if ($this->deposit->status == DepositStatus::CONFIRMED->value);
        $this->deposit->update(['status' => DepositStatus::EXPIRED->value]);
    }
}
