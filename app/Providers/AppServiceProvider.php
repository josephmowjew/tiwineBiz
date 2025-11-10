<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(
            \App\Repositories\Contracts\ProductRepositoryInterface::class,
            \App\Repositories\ProductRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\SubscriptionRepositoryInterface::class,
            \App\Repositories\SubscriptionRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\EfdTransactionRepositoryInterface::class,
            \App\Repositories\EfdTransactionRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\MobileMoneyTransactionRepositoryInterface::class,
            \App\Repositories\MobileMoneyTransactionRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\SubscriptionPaymentRepositoryInterface::class,
            \App\Repositories\SubscriptionPaymentRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
