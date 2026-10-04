<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments;

use Illuminate\Support\ServiceProvider;
use Laravel\Forge\Forge;
use Omaralalwi\LaravelForgeDeployments\Commands\CheckForgeDeploymentCommand;
use Omaralalwi\LaravelForgeDeployments\Commands\DiscoverForgeTargetsCommand;
use Omaralalwi\LaravelForgeDeployments\Contracts\DeploymentGateway;
use Omaralalwi\LaravelForgeDeployments\Contracts\ResolvesDeploymentActor;
use Omaralalwi\LaravelForgeDeployments\Services\ForgeDeploymentGateway;
use Omaralalwi\LaravelForgeDeployments\Support\DefaultDeploymentActorResolver;
use Omaralalwi\LaravelForgeDeployments\Support\ForgeClientFactory;

final class LaravelForgeDeploymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/forge-deployments.php', 'forge-deployments');

        $this->app->singleton(Forge::class, fn (): Forge => $this->app->make(ForgeClientFactory::class)->make());
        $this->app->singleton(DeploymentGateway::class, ForgeDeploymentGateway::class);
        $this->app->singleton(ResolvesDeploymentActor::class, DefaultDeploymentActorResolver::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-forge-deployments');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-forge-deployments');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/forge-deployments.php' => config_path('forge-deployments.php'),
        ], 'laravel-forge-deployments-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-forge-deployments'),
        ], 'laravel-forge-deployments-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/laravel-forge-deployments'),
        ], 'laravel-forge-deployments-translations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckForgeDeploymentCommand::class,
                DiscoverForgeTargetsCommand::class,
            ]);
        }
    }
}
