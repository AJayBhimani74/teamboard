@extends('layouts.app')
@section('title', 'Members')
@section('content')
<h1>Members</h1>
<table>
    <tr><th>Name</th><th>Email</th><th>Role</th><th></th></tr>
    @foreach ($members as $member)
        <tr>
            <td>{{ $member->name }}</td>
            <td>{{ $member->email }}</td>
            <td>
                @if ($workspaceRole === 'owner' && $member->pivot->role !== 'owner')
                    <form method="POST" action="{{ route('members.update', $member) }}">@csrf @method('PATCH')
                        <select name="role" onchange="this.form.submit()">
                            @foreach (['admin', 'member'] as $role)
                                <option value="{{ $role }}" @selected($member->pivot->role === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    {{ $member->pivot->role }}
                @endif
            </td>
            <td class="actions">
                @if ($member->pivot->role !== 'owner')
                    <form method="POST" action="{{ route('members.destroy', $member) }}">@csrf @method('DELETE')
                        <button class="btn btn-danger" type="submit">Remove</button>
                    </form>
                @endif
            </td>
        </tr>
    @endforeach
</table>

@if ($invitations->isNotEmpty())
    <h1>Pending invitations</h1>
    <table>
        @foreach ($invitations as $invitation)
            <tr>
                <td>{{ $invitation->email }}</td>
                <td>{{ $invitation->role }}</td>
                <td class="muted">expires {{ $invitation->expires_at->format('d M') }}</td>
                <td class="actions">
                    <form method="POST" action="{{ route('invitations.destroy', $invitation) }}">@csrf @method('DELETE')
                        <button class="btn btn-danger" type="submit">Cancel</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
@endif

<div class="card narrow">
    <h1>Invite someone</h1>
    <form method="POST" action="{{ route('invitations.store') }}">
        @csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
        @error('email') <p class="error">{{ $message }}</p> @enderror
        <label>Role
            <select name="role"><option value="member">Member</option><option value="admin">Admin</option></select>
        </label>
        <button class="btn" type="submit">Send invitation</button>
    </form>
</div>
@endsection
