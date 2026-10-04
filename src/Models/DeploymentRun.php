<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Models;

use Illuminate\Database\Eloquent\Model;
use Omaralalwi\LaravelForgeDeployments\Data\RemoteDeployment;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentStatus;

final class DeploymentRun extends Model
{
    protected $table = 'forge_deployment_runs';

    protected $casts = [
        'status' => DeploymentStatus::class,
        'requested_at' => 'immutable_datetime',
        'started_at' => 'immutable_datetime',
        'ended_at' => 'immutable_datetime',
        'last_synced_at' => 'immutable_datetime',
    ];

    protected $fillable = [
        'uuid',
        'actor_type',
        'actor_id',
        'actor_name',
        'actor_email',
        'source',
        'forge_deployment_id',
        'forge_server_id',
        'forge_site_id',
        'status',
        'deployment_type',
        'commit_hash',
        'commit_author',
        'commit_message',
        'branch',
        'requested_at',
        'started_at',
        'ended_at',
        'last_synced_at',
        'failure_code',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function applyRemoteDeployment(RemoteDeployment $deployment): void
    {
        $this->forceFill([
            'forge_deployment_id' => $deployment->id,
            'status' => $deployment->status,
            'deployment_type' => $deployment->type,
            'commit_hash' => $deployment->commitHash,
            'commit_author' => $deployment->commitAuthor,
            'commit_message' => $deployment->commitMessage,
            'branch' => $deployment->branch ?? $this->branch,
            'started_at' => $deployment->startedAt,
            'ended_at' => $deployment->endedAt,
            'last_synced_at' => now(),
            'failure_code' => null,
        ])->save();
    }
}
