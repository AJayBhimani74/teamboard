@extends('layouts.app')
@section('title', $project->name)
@section('content')
<div class="head">
    <h1>{{ $project->name }}</h1>
    @if (in_array($workspaceRole, ['owner', 'admin']))
        <form method="POST" action="{{ route('projects.destroy', $project) }}">@csrf @method('DELETE')
            <button class="btn btn-danger" type="submit">Delete project</button>
        </form>
    @endif
</div>

<div class="card narrow">
    <form method="POST" action="{{ route('tasks.store', $project) }}">
        @csrf
        <label>New task <input name="title" required></label>
        @error('title') <p class="error">{{ $message }}</p> @enderror
        <div class="row">
            <label>Assign to
                <select name="assigned_to">
                    <option value="">Nobody</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Due <input type="date" name="due_date"></label>
        </div>
        <button class="btn" type="submit">Add task</button>
    </form>
</div>

<div class="grid">
    @foreach (App\Models\Task::STATUSES as $status => $label)
        <div class="card">
            <h1>{{ $label }}</h1>
            @foreach ($tasks[$status] ?? [] as $task)
                <p>
                    {{ $task->title }}
                    <br><span class="muted">{{ $task->assignee?->name ?? 'Unassigned' }}@if ($task->due_date) &middot; due {{ $task->due_date->format('d M') }}@endif</span>
                </p>
                <form method="POST" action="{{ route('tasks.update', $task) }}" class="row">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()">
                        @foreach (App\Models\Task::STATUSES as $value => $text)
                            <option value="{{ $value }}" @selected($task->status === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </form>
            @endforeach
        </div>
    @endforeach
</div>
@endsection
