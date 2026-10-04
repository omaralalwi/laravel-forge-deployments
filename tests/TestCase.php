<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Omaralalwi\LaravelForgeDeployments\LaravelForgeDeploymentsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LaravelForgeDeploymentsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('forge-deployments.enabled', true);
        $app['config']->set('forge-deployments.forge.api_token', 'test-token');
        $app['config']->set('forge-deployments.target', [
            'label' => 'Staging',
            'organization_slug' => 'acme',
            'server_id' => 123,
            'site_id' => 456,
            'branch' => 'develop',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->boolean('can_deploy')->default(false);
            $table->timestamps();
        });

        $migration = require dirname(__DIR__).'/database/migrations/2026_01_01_000000_create_forge_deployment_runs_table.php';
        $migration->up();

        Gate::define('manageForgeDeployments', fn (TestUser $user): bool => $user->can_deploy);
    }

    protected function user(bool $canDeploy = true): TestUser
    {
        return TestUser::query()->create([
            'name' => 'Release Operator',
            'email' => 'operator@example.test',
            'can_deploy' => $canDeploy,
        ]);
    }
}

final class TestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    protected $casts = ['can_deploy' => 'boolean'];
}
