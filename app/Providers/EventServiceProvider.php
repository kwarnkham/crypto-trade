<?php

namespace App\Providers;

use App\Models\Deposit;
use App\Models\Extract;
use App\Models\Withdraw;
use App\Observers\DepositOberver;
use App\Observers\ExtractObserver;
use App\Observers\WithdrawObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Deposit::observe(DepositOberver::class);
        Withdraw::observe(WithdrawObserver::class);
        Extract::observe(ExtractObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
