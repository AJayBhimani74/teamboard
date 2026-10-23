<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::factory()->create(['name' => 'Priya Shah', 'email' => 'owner@example.com']);
        $admin = User::factory()->create(['name' => 'Rahul Mehta', 'email' => 'admin@example.com']);
        $member = User::factory()->create(['name' => 'Neha Patel', 'email' => 'member@example.com']);

        $acme = Workspace::create(['name' => 'Acme Studio']);
        $acme->members()->attach($owner, ['role' => 'owner']);
        $acme->members()->attach($admin, ['role' => 'admin']);
        $acme->members()->attach($member, ['role' => 'member']);

        $other = Workspace::create(['name' => 'Northwind Labs']);
        $other->members()->attach($owner, ['role' => 'member']);

        $website = Project::create(['workspace_id' => $acme->id, 'name' => 'Website redesign', 'description' => 'New marketing site']);
        $app = Project::create(['workspace_id' => $acme->id, 'name' => 'Mobile app', 'description' => 'First release']);
        Project::create(['workspace_id' => $other->id, 'name' => 'Data pipeline']);

        $tasks = [
            [$website, 'Collect brand assets', 'done', $member],
            [$website, 'Design home page', 'doing', $admin],
            [$website, 'Write copy for pricing', 'todo', $member],
            [$website, 'Set up analytics', 'todo', null],
            [$app, 'Login screen', 'doing', $admin],
            [$app, 'Push notifications', 'todo', null],
        ];

        foreach ($tasks as [$project, $title, $status, $user]) {
            Task::create([
                'workspace_id' => $project->workspace_id,
                'project_id' => $project->id,
                'title' => $title,
                'status' => $status,
                'assigned_to' => $user?->id,
                'due_date' => now()->addDays(rand(3, 20)),
            ]);
        }
    }
}
