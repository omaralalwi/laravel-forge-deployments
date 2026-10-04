<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class AuthorizeDeploymentAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $ability = trim((string) config('forge-deployments.authorization.gate', ''));

        if ($user === null || $ability === '' || ! Gate::forUser($user)->allows($ability)) {
            abort(403);
        }

        return $next($request);
    }
}
