<?php

namespace Tests\Unit;

use App\Models\Deposit;
use App\Models\Wallet;
use App\Models\Withdraw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Services\Tron;

use Tests\TestCase;

class WalletModelTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    }

    public function tearDown(): void
    {
        // Re-enable foreign key checks after the test
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        parent::tearDown();
    }

    public function test_find_available_return_wallet_that_is_activated(): void
    {
        Wallet::factory()->state(['activated_at' => null])->create();
        $this->assertDatabaseCount('wallets', 1);
        $this->assertNull(Wallet::findAvailable());

        Wallet::factory()->state(['activated_at' => now()])->create();

        $this->assertDatabaseCount('wallets', 2);
        $this->assertNotNull(Wallet::findAvailable());
    }

    public function test_find_available_return_wallet_that_does_not_have_pending_or_confirmed_deposit_associated(): void
    {
        Wallet::factory()->state(['activated_at' => now()])->create();
        $this->assertDatabaseCount('wallets', 1);

        $pendingDeposit = Deposit::factory()->state(['status' => 1])->create();
        $this->assertDatabaseCount('deposits', 1);

        $this->assertNull(Wallet::findAvailable());

        $confirmDeposit = Deposit::where('id', $pendingDeposit->id)->update(['status' => 2]);
        $this->assertNull(Wallet::findAvailable());

        $otherStatusDeposit = Deposit::where('id', $pendingDeposit->id)->update(['status' => rand(3,5)]);
        $this->assertNotNull(Wallet::findAvailable());
    }

    public function test_find_witdrawable_return_wallet_that_is_activated(): void
    {
        Wallet::factory()->state(['activated_at' => null])->create();
        $this->assertDatabaseCount('wallets', 1);
        $this->assertNull(Wallet::withdrawable(5));
    }

    // public function test_withdrawable_return_wallet_that_have_enough_balance(): void
    // {
    //     $wallet = Wallet::factory()->state(['activated_at' => null])->create();
    //     $wallet->update([
    //         'activated_at' => now(),
    //         'trx' => (config('app')['min_trx_for_transaction']) * Tron::DIGITS,
    //         'energy' => config('app')['min_energy_for_transaction'],
    //         'bandwidth' => 5000,
    //         'balance'   => 5000000
    //     ]);

    //     $this->assertDatabaseCount('wallets', 1);
    //     $withdraw = Withdraw::factory()->state(['amount' => 1000000, 'wallet_id'=>$wallet->id,'to'=>$wallet->base58_check])->create();
    //     $this->assertDatabaseCount('withdraws', 1);

    //     $this->assertNull(Wallet::withdrawable(1000000));
    // }
}
