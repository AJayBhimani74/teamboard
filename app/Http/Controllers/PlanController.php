<?php

namespace App\Http\Controllers;

use App\CurrentWorkspace;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function show(CurrentWorkspace $current)
    {
        return view('plan.show', ['plans' => Workspace::PLANS]);
    }

    public function update(Request $request, CurrentWorkspace $current): RedirectResponse
    {
        $data = $request->validate(['plan' => ['required', Rule::in(array_keys(Workspace::PLANS))]]);

        $workspace = $current->get();

        if ($data['plan'] === 'free') {
            $max = Workspace::PLANS['free'];
            $projects = $workspace->projects()->count();
            $members = $workspace->members()->count();

            if ($projects > $max['projects'] || $members > $max['members']) {
                return back()->withErrors(['plan' => "To go back to Free you need at most {$max['projects']} projects and {$max['members']} members."]);
            }
        }

        $workspace->update($data);

        return back()->with('status', 'Plan changed.');
    }
}
