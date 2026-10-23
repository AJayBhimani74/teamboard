@extends('layouts.app')
@section('title', 'Workspaces')
@section('content')
@if ($workspaces->isNotEmpty())
    <h1>Your workspaces</h1>
    <table>
        <tr><th>Name</th><th>Plan</th><th>Your role</th><th></th></tr>
        @foreach ($workspaces as $w)
            <tr>
                <td>{{ $w->name }}</td>
                <td>{{ ucfirst($w->plan) }}</td>
                <td>{{ $w->pivot->role }}</td>
                <td class="actions">
                    <form method="POST" action="{{ route('workspaces.switch', $w) }}">@csrf
                        <button class="btn" type="submit">Open</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
@endif

<div class="card narrow">
    <h1>New workspace</h1>
    <form method="POST" action="{{ route('workspaces.store') }}">
        @csrf
        <label>Name <input name="name" value="{{ old('name') }}" required></label>
        @error('name') <p class="error">{{ $message }}</p> @enderror
        <button class="btn" type="submit">Create workspace</button>
    </form>
</div>
@endsection
