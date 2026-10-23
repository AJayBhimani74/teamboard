<?php

namespace App\Http\Middleware;

use App\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetWorkspace
{
    public function __construct(private CurrentWorkspace $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $workspace = $user->workspaces()->find(session('workspace_id'))
            ?? $user->workspaces()->orderBy('workspaces.id')->first();

        if (! $workspace) {
            return redirect()->route('workspaces.create');
        }

        session(['workspace_id' => $workspace->id]);
        $this->current->set($workspace);

        $request->attributes->set('workspace_role', $workspace->pivot->role);
        View::share('workspace', $workspace);
        View::share('workspaceRole', $workspace->pivot->role);

        return $next($request);
    }
}
