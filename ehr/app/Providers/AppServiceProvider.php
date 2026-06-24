<?php

namespace App\Providers;

use App\Services\Integrations\Hie\FhirClientInterface;
use App\Services\Integrations\Hie\StubFhirClient;
use App\Services\Integrations\Lab\LabInterfaceProvider;
use App\Services\Integrations\Lab\StubLabInterfaceProvider;
use App\Services\Integrations\Surescripts\LiveSurescriptsProvider;
use App\Services\Integrations\Surescripts\StubSurescriptsProvider;
use App\Services\Integrations\Surescripts\SurescriptsProviderInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(SurescriptsProviderInterface::class, function () {
            return match (config('surescripts.driver', 'stub')) {
                'live' => new LiveSurescriptsProvider(),
                default => new StubSurescriptsProvider(),
            };
        });

        $this->app->bind(LabInterfaceProvider::class, fn () => new StubLabInterfaceProvider());

        $this->app->bind(FhirClientInterface::class, fn () => new StubFhirClient());
    }

    public function boot()
    {
        //
    }
}
