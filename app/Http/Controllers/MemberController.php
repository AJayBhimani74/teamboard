<?php

namespace App\Http\Controllers;

use App\CurrentWorkspace;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(CurrentWorkspace $current)
    {
        $workspace = $current->get();

        return view('members.index', [
            'members' => $workspace->members()->orderBy('name')->get(),
            'invitations' => $workspace->invitations()->pending()->latest()->get(),
        ]);
    }

    public function update(Request $request, User $user, CurrentWorkspace $current): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(['admin', 'member'])]]);

        $workspace = $current->get();
        $member = $workspace->members()->findOrFail($user->id);

        abort_if($member->pivot->role === 'owner', 422, 'The owner role cannot be changed.');

        $workspace->members()->updateExistingPivot($user->id, $data);

        return back()->with('status', 'Role updated.');
    }

    public function destroy(User $user, CurrentWorkspace $current): RedirectResponse
    {
        $workspace = $current->get();
        $member = $workspace->members()->findOrFail($user->id);

        abort_if($member->pivot->role === 'owner', 422, 'The owner cannot be removed.');

        $workspace->members()->detach($user->id);
        $workspace->tasks()->where('assigned_to', $user->id)->update(['assigned_to' => null]);

        return back()->with('status', 'Member removed.');
    }
}
