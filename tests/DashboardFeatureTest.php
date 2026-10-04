<?php

declare(strict_types=1);

namespace Omaralwi\LaravelForgeDeployments\Tests\Feature;

use Omaralalwi\LaravelForgeDeployments\Contracts\DeploymentGateway;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentSiteStatus;
use Omaralalwi\LaravelForgeDeployments\Data\RemoteDeployment;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentFailureReason;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentStatus;
use Omaralalwi\LaravelForgeDeployments\Exceptions\DeploymentGatewayException;
use Omaralalwi\LaravelForgeDeployments\Models\DeploymentRun;
use Omaralalwi\LaravelForgeDeployments\Tests\FakeDeploymentGateway;
use Omaralalwi\LaravelForgeDeployments\Tests\TestCase;
use Omaralalwi\LaravelForgeDeployments\Tests\TestUser;

final class DashboardFeatureTest extends TestCase
{
    private FakeDeploymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakeDeploymentGateway;
        $this->app->instance(DeploymentGateway::class, $this->gateway);
    }

    public function test_guest_cannot_open_dashboard(): void
    {
        $this->getJson('/forge-deployments')->assertUnauthorized();
    }

    public function test_authenticated_user_denied_by_gate_receives_forbidden(): void
    {
        $this->actingAs($this->user(canDeploy: false))
            ->get('/forge-deployments')
            ->assertForbidden();
    }

    public function test_missing_gate_fails_closed(): void
    {
        config()->set('forge-deployments.authorization.gate', 'undefinedDeploymentGate');

        $this->actingAs($this->user())
            ->get('/forge-deployments')
            ->assertForbidden();
    }

    public function test_authorized_user_can_open_dashboard(): void
    {
        $this->actingAs($this->user())
            ->get('/forge-deployments')
            ->assertOk()
            ->assertSee('Forge deployments')
            ->assertSee('Staging')
            ->assertDontSee('test-token');
    }

    public function test_authorized_user_can_trigger_fixed_target_and_actor_is_recorded(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->postJson('/forge-deployments')
            ->assertAccepted()
            ->assertJsonPath('deployment.id', 999)
            ->assertJsonPath('deployment.actor_name', 'Release Operator');

        $this->assertSame(1, $this->gateway->triggerCount);
        $this->assertDatabaseHas('forge_deployment_runs', [
            'actor_type' => TestUser::class,
            'actor_id' => (string) $user->getKey(),
            'actor_email' => 'operator@example.test',
            'forge_server_id' => 123,
            'forge_site_id' => 456,
            'forge_deployment_id' => 999,
            'status' => 'pending',
        ]);
    }

    public function test_active_remote_deployment_blocks_trigger(): void
    {
        $this->gateway->current = new DeploymentSiteStatus(DeploymentStatus::Deploying);

        $this->actingAs($this->user())
            ->postJson('/forge-deployments')
            ->assertConflict();

        $this->assertSame(0, $this->gateway->triggerCount);
        $this->assertDatabaseCount('forge_deployment_runs', 0);
    }

    public function test_ambiguous_trigger_is_recorded_and_not_retried(): void
    {
        $this->gateway->triggerException = new DeploymentGatewayException(DeploymentFailureReason::Unavailable);
        $user = $this->user();

        $this->actingAs($user)
            ->postJson('/forge-deployments')
            ->assertServiceUnavailable()
            ->assertJsonPath('reason', 'unavailable');

        $this->actingAs($user)
            ->postJson('/forge-deployments')
            ->assertConflict();

        $this->assertSame(1, $this->gateway->triggerCount);
        $this->assertDatabaseHas('forge_deployment_runs', [
            'forge_deployment_id' => null,
            'status' => 'unknown',
            'failure_code' => 'unavailable',
        ]);
    }

    public function test_history_cursor_is_validated(): void
    {
        $this->actingAs($this->user())
            ->getJson('/forge-deployments/history?cursor='.str_repeat('x', 1025))
            ->assertUnprocessable();
    }

    public function test_history_combines_forge_data_with_local_actor(): void
    {
        $user = $this->user();
        $this->actingAs($user)->postJson('/forge-deployments')->assertAccepted();
        $this->gateway->deployments = [new RemoteDeployment(
            id: 999,
            status: DeploymentStatus::Finished,
            commitHash: 'abcdef1234567890',
            commitMessage: 'Ship release',
            branch: 'develop',
        )];

        $this->actingAs($user)
            ->getJson('/forge-deployments/history')
            ->assertOk()
            ->assertJsonPath('history.items.0.actor_name', 'Release Operator')
            ->assertJsonPath('history.items.0.commit_message', 'Ship release')
            ->assertJsonPath('history.items.0.status.value', 'finished');
    }

    public function test_status_route_rejects_a_run_from_another_target(): void
    {
        $run = DeploymentRun::query()->create([
            'uuid' => '33e2e22e-b2ec-4418-bff1-2dc37cb40b56',
            'actor_name' => 'Operator',
            'source' => 'dashboard',
            'forge_deployment_id' => 88,
            'forge_server_id' => 900,
            'forge_site_id' => 901,
            'status' => DeploymentStatus::Pending,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->user())
            ->getJson("/forge-deployments/runs/{$run->uuid}/status")
            ->assertNotFound();
    }

    public function test_output_is_returned_as_json_text(): void
    {
        $this->gateway->outputText = '<script>alert("x")</script>';

        $this->actingAs($this->user())
            ->getJson('/forge-deployments/deployments/999/output')
            ->assertOk()
            ->assertJsonPath('output', '<script>alert("x")</script>');
    }
}
