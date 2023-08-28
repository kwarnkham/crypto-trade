<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Tron;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        $wallets = Wallet::all();
        $wallets->each(function ($wallet) {
            $response = Tron::getTRC20TransactionInfoByAccountAddress($wallet->base58_check, [
                'only_confirmed' => true,
                'limit' => 200,
                'contract_address' => config('app')['trc20_address'],
            ]);

            Log::info('Getting wallet data');
            Log::info(json_encode($response));


            $transactions = collect($response->data);

            $transactions->each(function ($tx) {
                if (Transaction::query()->where('transaction_id', $tx->transaction_id)->doesntExist()) {
                    Log::info('Creating tx from');
                    Log::info(json_encode($tx));
                    Transaction::create([
                        'from' => $tx->from,
                        'to' => $tx->to,
                        'transaction_id' => $tx->transaction_id,
                        'token_address' => $tx->token_info->address,
                        'block_timestamp' => $tx->block_timestamp,
                        'value' => $tx->value,
                        'type' => $tx->type,
                    ]);
                }
            });

            $links = $response->meta->links ?? null;
            if ($links != null && $links->next) {
                Log::info('There are still data need to get');
                Log::info($links->next);
            }
        });
    }
}
