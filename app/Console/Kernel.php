<?php

namespace App\Console;

use App\Enums\DepositStatus;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Tron;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            Wallet::all()->each(function (Wallet $wallet) {
                $deposit = $wallet->deposits()->where('status', DepositStatus::CONFIRMED->value)->first();
                if ($deposit != null) {
                    $transactions = collect(Tron::getTRC20TransactionInfoByAccountAddress($wallet->base58_check, [
                        'only_confirmed' => true,
                        'limit' => 10,
                        'contract_address' => 'TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs',
                        'only_to' => true
                    ])->data);
                    $transactions->each(function ($tx) use ($deposit, $wallet) {
                        if (Transaction::query()->where('transaction_id', $tx->transaction_id)->doesntExist()) {
                            $transaction = Transaction::create([
                                'from' => $tx->from,
                                'to' => $tx->to,
                                'transaction_id' => $tx->transaction_id,
                                'token_address' => $tx->token_info->address,
                                'block_timestamp' => $tx->block_timestamp,
                                'value' => $tx->value,
                                'type' => $tx->type
                            ]);

                            if ($deposit->amount == $transaction->value) {
                                $deposit->complete($transaction);

                                $usdt = collect(
                                    Tron::getAccountInfoByAddress($wallet->base58_check)->data[0]->trc20
                                )->first(fn ($v) => property_exists($v, 'TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs'));
                                $key = 'TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs';
                                if ($usdt != null) $wallet->update(['balance' => $usdt->$key]);
                            }
                        }
                    });
                }
            });
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
