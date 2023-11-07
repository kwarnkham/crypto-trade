<?php

namespace Tests\Unit;

use App\Enums\DepositStatus;
use App\Enums\WithdrawStatus;
use App\Models\Deposit;
use App\Models\Wallet;
use App\Models\Withdraw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Tron;
use App\Models\Agent;
use App\Models\User;
use Tests\TestCase;

class WalletModelTest extends TestCase
{
    use RefreshDatabase;

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
        $wallet = Wallet::factory()->state(['activated_at' => now()])->create();
        $this->assertDatabaseCount('wallets', 1);

        $pendingDeposit = Deposit::factory()->for(User::factory()->for(Agent::factory()->create())->create())->for(Wallet::factory()->create())->state(['status' => DepositStatus::PENDING, 'wallet_id' => $wallet->id])->create();
        $this->assertDatabaseCount('deposits', 1);

        $this->assertNull(Wallet::findAvailable());

        $confirmDeposit = Deposit::where('id', $pendingDeposit->id)->update(['status' => DepositStatus::CONFIRMED]);
        $this->assertNull(Wallet::findAvailable());

        $otherStatusDeposit = Deposit::where('id', $pendingDeposit->id)->update(['status' => rand(3, 5)]);
        $this->assertNotNull(Wallet::findAvailable());
    }

    public function test_witdrawable_return_wallet_that_is_activated(): void
    {

        $wallet = Wallet::factory()->create();
        $wallet->update([
            'balance' => 5000000,
            'activated_at' => now(),
            'trx' => (config('app')['min_trx_for_transaction']) * Tron::DIGITS,
            'energy' => config('app')['min_energy_for_transaction'],
            'bandwidth' => 5000
        ]);
        $withdrawAmount = $wallet->getRawOriginal('balance');
        $this->assertNotNull(Wallet::withdrawable($withdrawAmount));

        $wallet->update(['activated_at' => null]);

        $this->assertNull(Wallet::withdrawable($withdrawAmount));
    }

    public function test_withdrawable_return_wallet_that_have_enough_balance(): void
    {
        $wallet = Wallet::factory()->create();
        $wallet->update([
            'balance' => 5000000,
            'activated_at' => now(),
            'trx' => (config('app')['min_trx_for_transaction']) * Tron::DIGITS,
            'energy' => config('app')['min_energy_for_transaction'],
            'bandwidth' => 5000
        ]);

        $walletBalance = $wallet->balance;
        $withdrawAmount = $wallet->getRawOriginal('balance');

        $this->assertNotNull(Wallet::withdrawable($withdrawAmount));

        Withdraw::factory()->for(User::factory()->for(Agent::factory()->create())->create())->create(['amount' => $walletBalance, 'status' => WithdrawStatus::PENDING->value, 'wallet_id' => $wallet->id]);
        $this->assertDatabaseCount('withdraws', 1);

        $this->assertNull(Wallet::withdrawable(rand(1, 5) * Tron::DIGITS));
    }
}
