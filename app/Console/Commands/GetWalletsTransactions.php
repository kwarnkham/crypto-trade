<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use Illuminate\Console\Command;

class GetWalletsTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-wallets-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all wallets transactions from the newtwork';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $wallets = Wallet::query()->whereNotNull('activated_at')->get();
        $wallets->each(function (Wallet $wallet) {
            $wallet->syncTrc20Txs();
        });
    }
}
