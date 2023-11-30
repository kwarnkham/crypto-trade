<?php

namespace App\Jobs;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ProcessConfirmedDeposit implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct(public int $depositId)
    {
        //
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
        $deposit = Deposit::find($this->depositId);
        $deposit->attemptToComplete();
        if ($deposit->refresh()->status == DepositStatus::CONFIRMED->value && $deposit->attempts < $maxAttempts)
            ProcessConfirmedDeposit::dispatch($deposit->id)->delay(now()->addMinute());
        else if ($deposit->attempts >= $maxAttempts) {
            $deposit->update(['status' => DepositStatus::EXPIRED->value]);
        }
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("deposit:{$this->depositId}"))->shared(),
        ];
    }

    public function uniqueId(): string
    {
        return $this->depositId;
    }
}
