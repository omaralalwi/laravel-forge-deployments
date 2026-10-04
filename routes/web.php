<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Omaralalwi\LaravelForgeDeployments\Http\Controllers\DeploymentController;
use Omaralalwi\LaravelForgeDeployments\Http\Middleware\AuthorizeDeploymentAccess;

$prefix = trim((string) config('forge-deployments.route.prefix', 'forge-deployments'), '/');
$namePrefix = (string) config('forge-deployments.route.name_prefix', 'forge-deployments.');
$middleware = config('forge-deployments.route.middleware', ['web', 'auth']);

Route::prefix($prefix)
    ->name($namePrefix)
    ->middleware(array_merge((array) $middleware, [AuthorizeDeploymentAccess::class]))
    ->group(function (): void {
        Route::get('/', [DeploymentController::class, 'index'])->name('index');
        Route::post('/', [DeploymentController::class, 'store'])->middleware('throttle:6,1')->name('store');
        Route::get('/current', [DeploymentController::class, 'current'])->name('current');
        Route::get('/history', [DeploymentController::class, 'history'])->name('history');
        Route::get('/runs/{deploymentRun}/status', [DeploymentController::class, 'status'])->name('status');
        Route::get('/deployments/{deploymentId}/output', [DeploymentController::class, 'output'])
            ->whereNumber('deploymentId')
            ->name('output');
    });
