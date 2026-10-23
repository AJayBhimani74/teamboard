<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function create()
    {
        return view('workspaces.create', ['workspaces' => auth()->user()->workspaces]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $workspace = Workspace::create($data);
        $workspace->members()->attach($request->user(), ['role' => 'owner']);

        session(['workspace_id' => $workspace->id]);

        return redirect()->route('projects.index')->with('status', 'Workspace created.');
    }

    public function switch(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($request->user()->workspaces()->whereKey($workspace->id)->exists(), 404);

        session(['workspace_id' => $workspace->id]);

        return redirect()->route('projects.index');
    }
}
