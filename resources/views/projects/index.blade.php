@extends('layouts.app')
@section('title', 'Projects')
@section('content')
<div class="head"><h1>Projects</h1></div>

@forelse ($projects as $project)
    <div class="card">
        <a href="{{ route('projects.show', $project) }}"><strong>{{ $project->name }}</strong></a>
        <span class="muted">{{ $project->tasks_count }} {{ Str::plural('task', $project->tasks_count) }}</span>
        @if ($project->description) <p class="muted">{{ $project->description }}</p> @endif
    </div>
@empty
    <p class="muted">No projects yet.</p>
@endforelse

@if (in_array($workspaceRole, ['owner', 'admin']))
<div class="card narrow">
    <h1>New project</h1>
    <form method="POST" action="{{ route('projects.store') }}">
        @csrf
        <label>Name <input name="name" value="{{ old('name') }}" required></label>
        @error('name') <p class="error">{{ $message }}</p> @enderror
        <label>Description <textarea name="description" rows="2">{{ old('description') }}</textarea></label>
        <button class="btn" type="submit">Add project</button>
    </form>
</div>
@endif
@endsection
