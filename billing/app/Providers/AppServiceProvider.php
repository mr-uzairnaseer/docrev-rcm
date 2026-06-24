<?php

namespace App\Providers;

use App\Services\Billing\AvailityClearinghouse;
use App\Services\Billing\ChangeHealthcareClearinghouse;
use App\Services\Billing\ClearinghouseInterface;
use App\Services\Billing\EligibilityProviderInterface;
use App\Services\Billing\SftpClearinghouse;
use App\Services\Billing\StubClearinghouse;
use App\Services\Billing\StubEligibilityProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ClearinghouseInterface::class, function () {
            return match (config('clearinghouse.driver', 'stub')) {
                'availity' => new AvailityClearinghouse(),
                'change_healthcare' => new ChangeHealthcareClearinghouse(),
                'sftp' => new SftpClearinghouse(),
                default => new StubClearinghouse(),
            };
        });

        $this->app->bind(EligibilityProviderInterface::class, function () {
            // Live eligibility adapters plug in here when credentials are provided.
            return new StubEligibilityProvider();
        });
    }

    public function boot()
    {
        //
    }
}
