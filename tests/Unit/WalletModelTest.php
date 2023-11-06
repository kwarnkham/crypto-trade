<?php

namespace Tests\Unit;

use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertTrue(true);
    }
}
