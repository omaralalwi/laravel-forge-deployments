<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Laravel\Forge\Forge;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentTarget;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentFailureReason;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentStatus;
use Omaralalwi\LaravelForgeDeployments\Exceptions\DeploymentGatewayException;
use Omaralalwi\LaravelForgeDeployments\Services\ForgeDeploymentGateway;
use PHPUnit\Framework\TestCase;

final class ForgeDeploymentGatewayTest extends TestCase
{
    public function test_current_status_is_mapped_from_forge_response(): void
    {
        $gateway = $this->gateway(new Response(200, [], $this->json([
            'data' => [
                'attributes' => [
                    'status' => 'deploying',
                    'started_at' => '2026-10-05T12:00:00+00:00',
                ],
            ],
        ])));

        $status = $gateway->currentStatus($this->target());

        self::assertSame(DeploymentStatus::Deploying, $status->status);
        self::assertSame('2026-10-05T12:00:00+00:00', $status->startedAt?->format('c'));
    }

    public function test_trigger_maps_deployment_and_commit_details(): void
    {
        $gateway = $this->gateway(new Response(202, [], $this->json([
            'data' => [
                'id' => '987',
                'attributes' => [
                    'status' => 'pending',
                    'type' => 'manual',
                    'commit' => [
                        'hash' => 'abcdef123456',
                        'author' => 'Developer',
                        'message' => 'Ship it',
                        'branch' => 'develop',
                    ],
                ],
            ],
        ])));

        $deployment = $gateway->trigger($this->target());

        self::assertSame(987, $deployment->id);
        self::assertSame(DeploymentStatus::Pending, $deployment->status);
        self::assertSame('abcdef123456', $deployment->commitHash);
        self::assertSame('Developer', $deployment->commitAuthor);
        self::assertSame('Ship it', $deployment->commitMessage);
        self::assertSame('develop', $deployment->branch);
    }

    public function test_rate_limit_response_is_translated_with_retry_timestamp(): void
    {
        $gateway = $this->gateway(new Response(429, ['x-ratelimit-reset' => '1800000000']));

        try {
            $gateway->currentStatus($this->target());
            self::fail('Expected the Forge rate limit response to be translated.');
        } catch (DeploymentGatewayException $exception) {
            self::assertSame(DeploymentFailureReason::RateLimited, $exception->reason);
            self::assertSame(1800000000, $exception->retryAt);
        }
    }

    public function test_incomplete_deployment_response_fails_closed(): void
    {
        $gateway = $this->gateway(new Response(202, [], $this->json([
            'data' => ['attributes' => ['status' => 'pending']],
        ])));

        $this->expectException(DeploymentGatewayException::class);

        $gateway->trigger($this->target());
    }

    private function gateway(Response ...$responses): ForgeDeploymentGateway
    {
        $handler = HandlerStack::create(new MockHandler($responses));
        $client = new Client([
            'handler' => $handler,
            'base_uri' => 'https://forge.laravel.com/api/',
            'http_errors' => false,
        ]);

        return new ForgeDeploymentGateway(new Forge('test-token', $client));
    }

    private function target(): DeploymentTarget
    {
        return new DeploymentTarget('Staging', 'acme', 123, 456, 'develop');
    }

    /** @param  array<string, mixed>  $payload */
    private function json(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
