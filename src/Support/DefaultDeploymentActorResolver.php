<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Omaralalwi\LaravelForgeDeployments\Contracts\ResolvesDeploymentActor;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentActor;

final class DefaultDeploymentActorResolver implements ResolvesDeploymentActor
{
    public function resolve(Authenticatable $user): DeploymentActor
    {
        $email = $this->value($user, 'email');
        $name = $this->value($user, 'name') ?? $email ?? 'Authenticated user';
        $identifier = $user->getAuthIdentifier();

        return new DeploymentActor(
            type: $user::class,
            id: is_scalar($identifier) ? (string) $identifier : null,
            name: preg_replace('/\s+/', ' ', trim($name)) ?: 'Authenticated user',
            email: $email !== null ? mb_strtolower($email) : null,
        );
    }

    private function value(Authenticatable $user, string $key): ?string
    {
        $value = data_get($user, $key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
