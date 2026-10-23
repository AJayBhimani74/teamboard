<?php

namespace App\Http\Controllers;

use App\CurrentWorkspace;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function store(Request $request, Project $project, CurrentWorkspace $current): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', Rule::in($current->get()->members()->pluck('users.id'))],
        ]);

        $project->tasks()->create($data);

        return back();
    }

    public function update(Request $request, Task $task, CurrentWorkspace $current): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(array_keys(Task::STATUSES))],
            'assigned_to' => ['sometimes', 'nullable', Rule::in($current->get()->members()->pluck('users.id'))],
        ]);

        $task->update($data);

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return back();
    }
}
