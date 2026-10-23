<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::get('/invitations/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/workspaces/new', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('/workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');

    Route::middleware('workspace')->group(function () {
        Route::redirect('/', '/projects');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

        Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        Route::middleware('workspace.admin')->group(function () {
            Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
            Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

            Route::get('/members', [MemberController::class, 'index'])->name('members.index');
            Route::delete('/members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');
            Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
            Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
        });

        Route::middleware('workspace.admin:owner')->group(function () {
            Route::patch('/members/{user}', [MemberController::class, 'update'])->name('members.update');
            Route::get('/plan', [PlanController::class, 'show'])->name('plan.show');
            Route::patch('/plan', [PlanController::class, 'update'])->name('plan.update');
        });
    });
});
