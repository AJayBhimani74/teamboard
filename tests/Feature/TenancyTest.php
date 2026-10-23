<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    private function memberOf(Workspace $workspace, string $role = 'member'): User
    {
        $user = User::factory()->create();
        $workspace->members()->attach($user, ['role' => $role]);

        return $user;
    }

    public function test_user_without_workspace_is_sent_to_create_one(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/projects')->assertRedirect(route('workspaces.create'));
    }

    public function test_creating_a_workspace_makes_the_user_its_owner(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/workspaces', ['name' => 'Acme'])->assertRedirect('/projects');

        $workspace = Workspace::first();
        $this->assertSame('owner', $workspace->roleOf($user));
        $this->assertSame('free', $workspace->plan);
    }

    public function test_projects_list_only_shows_the_current_workspace(): void
    {
        $mine = Workspace::factory()->create();
        $other = Workspace::factory()->create();
        Project::factory()->create(['workspace_id' => $mine->id, 'name' => 'Our roadmap']);
        Project::factory()->create(['workspace_id' => $other->id, 'name' => 'Secret plans']);

        $this->actingAs($this->memberOf($mine))->get('/projects')
            ->assertOk()->assertSee('Our roadmap')->assertDontSee('Secret plans');
    }

    public function test_another_workspaces_project_and_task_are_not_found(): void
    {
        $mine = Workspace::factory()->create();
        $other = Workspace::factory()->create();
        $project = Project::factory()->create(['workspace_id' => $other->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);
        $user = $this->memberOf($mine, 'owner');

        $this->actingAs($user)->get("/projects/{$project->id}")->assertNotFound();
        $this->actingAs($user)->post("/projects/{$project->id}/tasks", ['title' => 'x'])->assertNotFound();
        $this->actingAs($user)->patch("/tasks/{$task->id}", ['status' => 'done'])->assertNotFound();
        $this->actingAs($user)->delete("/tasks/{$task->id}")->assertNotFound();
        $this->actingAs($user)->delete("/projects/{$project->id}")->assertNotFound();

        $this->assertSame('todo', $task->fresh()->status);
    }

    public function test_new_records_get_the_current_workspace_id(): void
    {
        $workspace = Workspace::factory()->create();
        $user = $this->memberOf($workspace, 'owner');

        $this->actingAs($user)->post('/projects', ['name' => 'Launch']);
        $project = Project::withoutGlobalScopes()->first();
        $this->assertSame($workspace->id, $project->workspace_id);

        $this->actingAs($user)->post("/projects/{$project->id}/tasks", ['title' => 'Write copy']);
        $this->assertSame($workspace->id, Task::withoutGlobalScopes()->first()->workspace_id);
    }

    public function test_user_in_two_workspaces_can_switch_and_sees_each_separately(): void
    {
        $a = Workspace::factory()->create();
        $b = Workspace::factory()->create();
        $user = $this->memberOf($a);
        $b->members()->attach($user, ['role' => 'member']);
        Project::factory()->create(['workspace_id' => $a->id, 'name' => 'In A']);
        Project::factory()->create(['workspace_id' => $b->id, 'name' => 'In B']);

        $this->actingAs($user)->post("/workspaces/{$b->id}/switch");
        $this->get('/projects')->assertSee('In B')->assertDontSee('In A');

        $this->post("/workspaces/{$a->id}/switch");
        $this->get('/projects')->assertSee('In A')->assertDontSee('In B');
    }

    public function test_cannot_switch_to_a_workspace_you_do_not_belong_to(): void
    {
        $theirs = Workspace::factory()->create();
        $user = $this->memberOf(Workspace::factory()->create());

        $this->actingAs($user)->post("/workspaces/{$theirs->id}/switch")->assertNotFound();
    }

    public function test_assignee_must_be_a_member_of_the_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $user = $this->memberOf($workspace, 'owner');
        $project = Project::factory()->create(['workspace_id' => $workspace->id]);
        $outsider = User::factory()->create();

        $this->actingAs($user)->post("/projects/{$project->id}/tasks", ['title' => 'x', 'assigned_to' => $outsider->id])
            ->assertSessionHasErrors('assigned_to');
        $this->assertSame(0, Task::withoutGlobalScopes()->count());
    }
}
