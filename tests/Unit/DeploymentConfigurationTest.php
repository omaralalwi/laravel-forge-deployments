<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Tests\Unit;

use Omaralalwi\LaravelForgeDeployments\Support\DeploymentConfiguration;
use Omaralalwi\LaravelForgeDeployments\Tests\TestCase;

final class DeploymentConfigurationTest extends TestCase
{
    public function test_configuration_returns_fixed_target(): void
    {
        $target = $this->app->make(DeploymentConfiguration::class)->target();

        $this->assertSame('Staging', $target->label);
        $this->assertSame('acme', $target->organizationSlug);
        $this->assertSame(123, $target->serverId);
        $this->assertSame(456, $target->siteId);
        $this->assertSame('develop', $target->branch);
    }

    public function test_missing_identifier_is_incomplete(): void
    {
        config()->set('forge-deployments.target.site_id', null);

        $this->assertSame('incomplete', $this->app->make(DeploymentConfiguration::class)->problem());
    }

    public function test_disabled_configuration_is_not_ready(): void
    {
        config()->set('forge-deployments.enabled', false);

        $this->assertSame('disabled', $this->app->make(DeploymentConfiguration::class)->problem());
    }
}
