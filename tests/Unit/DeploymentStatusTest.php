<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Tests\Unit;

use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeploymentStatusTest extends TestCase
{
    #[DataProvider('providerStatuses')]
    public function test_provider_statuses_are_normalized(?string $provider, DeploymentStatus $expected): void
    {
        self::assertSame($expected, DeploymentStatus::fromProvider($provider));
    }

    /** @return iterable<string, array{?string, DeploymentStatus}> */
    public static function providerStatuses(): iterable
    {
        yield 'missing means ready' => [null, DeploymentStatus::Ready];
        yield 'whitespace and case are normalized' => ['  DEPLOYING ', DeploymentStatus::Deploying];
        yield 'unknown values fail closed' => ['new-provider-state', DeploymentStatus::Unknown];
    }

    public function test_unknown_status_is_treated_as_active(): void
    {
        self::assertTrue(DeploymentStatus::Unknown->isActive());
        self::assertFalse(DeploymentStatus::Unknown->isTerminal());
    }
}
