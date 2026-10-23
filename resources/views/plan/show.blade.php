@extends('layouts.app')
@section('title', 'Plan')
@section('content')
<h1>Plan</h1>
@error('plan') <div class="flash flash-error">{{ $message }}</div> @enderror
<div class="grid">
    @foreach ($plans as $name => $limits)
        <div class="card">
            <h1>{{ ucfirst($name) }} @if ($workspace->plan === $name)<span class="badge">current</span>@endif</h1>
            <p>Up to {{ $limits['members'] }} members</p>
            <p>{{ $limits['projects'] ? 'Up to '.$limits['projects'].' projects' : 'Unlimited projects' }}</p>
            @if ($workspace->plan !== $name)
                <form method="POST" action="{{ route('plan.update') }}">@csrf @method('PATCH')
                    <input type="hidden" name="plan" value="{{ $name }}">
                    <button class="btn" type="submit">Switch to {{ ucfirst($name) }}</button>
                </form>
            @endif
        </div>
    @endforeach
</div>
<p class="muted">Switching plans here only changes the limits. There is no payment step in this demo.</p>
@endsection
