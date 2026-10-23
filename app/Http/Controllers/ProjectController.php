<?php

namespace App\Http\Controllers;

use App\CurrentWorkspace;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return view('projects.index', ['projects' => Project::withCount('tasks')->latest()->get()]);
    }

    public function store(Request $request, CurrentWorkspace $current): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $current->get()->canAddProject()) {
            return back()->withErrors(['name' => 'Your plan has reached its project limit. Upgrade to add more.']);
        }

        $project = Project::create($data);

        return redirect()->route('projects.show', $project);
    }

    public function show(Project $project, CurrentWorkspace $current)
    {
        return view('projects.show', [
            'project' => $project,
            'tasks' => $project->tasks()->with('assignee')->orderBy('id')->get()->groupBy('status'),
            'members' => $current->get()->members,
        ]);
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }
}
