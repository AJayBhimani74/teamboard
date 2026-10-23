<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAdmin
{
    public function handle(Request $request, Closure $next, string $level = 'admin'): Response
    {
        $allowed = $level === 'owner' ? ['owner'] : ['owner', 'admin'];

        abort_unless(in_array($request->attributes->get('workspace_role'), $allowed), 403);

        return $next($request);
    }
}
