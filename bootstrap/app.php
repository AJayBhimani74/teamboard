<?php

use App\Http\Middleware\EnsureWorkspaceAdmin;
use App\Http\Middleware\SetWorkspace;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'workspace' => SetWorkspace::class,
            'workspace.admin' => EnsureWorkspaceAdmin::class,
        ]);

        // route model binding must run after the workspace is known, or the tenant scope is skipped
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: SetWorkspace::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
