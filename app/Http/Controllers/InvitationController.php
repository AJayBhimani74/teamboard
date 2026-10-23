<?php

namespace App\Http\Controllers;

use App\CurrentWorkspace;
use App\Models\Invitation;
use App\Notifications\WorkspaceInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvitationController extends Controller
{
    public function store(Request $request, CurrentWorkspace $current): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        $workspace = $current->get();
        $email = strtolower($data['email']);

        if ($workspace->members()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'This person is already a member.']);
        }

        if ($workspace->invitations()->pending()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'This person has already been invited.']);
        }

        if (! $workspace->canAddMember()) {
            throw ValidationException::withMessages(['email' => 'Your plan has reached its member limit. Upgrade to invite more people.']);
        }

        $invitation = $workspace->invitations()->create([
            'invited_by' => $request->user()->id,
            'email' => $email,
            'role' => $data['role'],
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $email)->notify(new WorkspaceInvitation($invitation));

        return back()->with('status', "Invitation sent to {$email}.");
    }

    public function destroy(Invitation $invitation, CurrentWorkspace $current): RedirectResponse
    {
        abort_unless($invitation->workspace_id === $current->id(), 404);

        $invitation->delete();

        return back()->with('status', 'Invitation cancelled.');
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isUsable()) {
            return redirect()->route('login')->withErrors(['email' => 'This invitation has expired or was already used.']);
        }

        if (! $request->user()) {
            session(['invite_token' => $token]);

            return redirect()->route('register')->with('status', "Create an account with {$invitation->email} to join.");
        }

        abort_unless(strtolower($request->user()->email) === $invitation->email, 403, 'This invitation was sent to a different email address.');

        $invitation->workspace->members()->syncWithoutDetaching([$request->user()->id => ['role' => $invitation->role]]);
        $invitation->update(['accepted_at' => now()]);

        session()->forget('invite_token');
        session(['workspace_id' => $invitation->workspace_id]);

        return redirect()->route('projects.index')->with('status', "You joined {$invitation->workspace->name}.");
    }
}
