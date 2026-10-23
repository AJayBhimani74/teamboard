<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MembersAndPlanTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->workspace = Workspace::factory()->create();
    }

    private function member(string $role = 'member', ?Workspace $workspace = null): User
    {
        $user = User::factory()->create();
        ($workspace ?? $this->workspace)->members()->attach($user, ['role' => $role]);

        return $user;
    }

    public function test_plain_members_cannot_manage_projects_members_or_plan(): void
    {
        $user = $this->member();
        $project = Project::factory()->create(['workspace_id' => $this->workspace->id]);

        $this->actingAs($user);
        $this->post('/projects', ['name' => 'New'])->assertForbidden();
        $this->delete("/projects/{$project->id}")->assertForbidden();
        $this->get('/members')->assertForbidden();
        $this->post('/invitations', ['email' => 'a@b.com', 'role' => 'member'])->assertForbidden();
        $this->get('/plan')->assertForbidden();
    }

    public function test_admin_can_manage_projects_but_not_the_plan(): void
    {
        $this->actingAs($this->member('admin'));

        $this->post('/projects', ['name' => 'New'])->assertRedirect();
        $this->get('/members')->assertOk();
        $this->patch('/plan', ['plan' => 'pro'])->assertForbidden();
    }

    public function test_free_plan_stops_at_three_projects(): void
    {
        $this->actingAs($this->member('owner'));
        Project::factory()->count(3)->create(['workspace_id' => $this->workspace->id]);

        $this->post('/projects', ['name' => 'Fourth'])->assertSessionHasErrors('name');
        $this->assertSame(3, $this->workspace->projects()->count());

        $this->workspace->update(['plan' => 'pro']);
        $this->post('/projects', ['name' => 'Fourth'])->assertRedirect();
        $this->assertSame(4, $this->workspace->projects()->count());
    }

    public function test_free_plan_member_limit_counts_pending_invitations(): void
    {
        $owner = $this->member('owner');
        $this->member();
        $this->actingAs($owner);

        $this->post('/invitations', ['email' => 'one@example.com', 'role' => 'member'])->assertSessionHasNoErrors();
        $this->post('/invitations', ['email' => 'two@example.com', 'role' => 'member'])->assertSessionHasErrors('email');
        $this->assertSame(1, Invitation::count());
    }

    public function test_cannot_downgrade_while_over_the_free_limits(): void
    {
        $owner = $this->member('owner');
        $this->workspace->update(['plan' => 'pro']);
        Project::factory()->count(4)->create(['workspace_id' => $this->workspace->id]);

        $this->actingAs($owner)->patch('/plan', ['plan' => 'free'])->assertSessionHasErrors('plan');
        $this->assertSame('pro', $this->workspace->fresh()->plan);
    }

    public function test_invitation_is_sent_by_email_and_cannot_be_duplicated(): void
    {
        $this->actingAs($this->member('owner'));

        $this->post('/invitations', ['email' => 'New@Example.com', 'role' => 'admin'])->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(WorkspaceInvitation::class);
        $this->assertSame('new@example.com', Invitation::first()->email);

        $this->post('/invitations', ['email' => 'new@example.com', 'role' => 'member'])->assertSessionHasErrors('email');
    }

    public function test_existing_member_cannot_be_invited_again(): void
    {
        $this->actingAs($this->member('owner'));
        $other = $this->member();

        $this->post('/invitations', ['email' => $other->email, 'role' => 'member'])->assertSessionHasErrors('email');
    }

    public function test_invited_person_joins_with_the_invited_role(): void
    {
        $invitation = Invitation::factory()->create([
            'workspace_id' => $this->workspace->id, 'email' => 'new@example.com', 'role' => 'admin',
        ]);
        $user = User::factory()->create(['email' => 'new@example.com']);

        $this->actingAs($user)->get("/invitations/{$invitation->token}")->assertRedirect('/projects');

        $this->assertSame('admin', $this->workspace->roleOf($user));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_guest_is_sent_to_register_and_then_joins_after_signing_up(): void
    {
        $invitation = Invitation::factory()->create(['workspace_id' => $this->workspace->id, 'email' => 'new@example.com']);

        $this->get("/invitations/{$invitation->token}")->assertRedirect('/register');
        $this->post('/register', [
            'name' => 'Newbie', 'email' => 'new@example.com',
            'password' => 'password1', 'password_confirmation' => 'password1',
        ])->assertRedirect(route('invitations.accept', $invitation->token));

        $this->get("/invitations/{$invitation->token}")->assertRedirect('/projects');
        $this->assertSame(1, $this->workspace->members()->where('email', 'new@example.com')->count());
    }

    public function test_invitation_for_a_different_email_is_refused(): void
    {
        $invitation = Invitation::factory()->create(['workspace_id' => $this->workspace->id, 'email' => 'new@example.com']);

        $this->actingAs(User::factory()->create())->get("/invitations/{$invitation->token}")->assertForbidden();
        $this->assertSame(0, $this->workspace->members()->count());
    }

    public function test_expired_or_used_invitations_do_not_work(): void
    {
        $user = User::factory()->create(['email' => 'new@example.com']);
        $expired = Invitation::factory()->create([
            'workspace_id' => $this->workspace->id, 'email' => 'new@example.com', 'expires_at' => now()->subDay(),
        ]);
        $used = Invitation::factory()->create([
            'workspace_id' => $this->workspace->id, 'email' => 'new@example.com', 'accepted_at' => now(),
        ]);

        foreach ([$expired, $used] as $invitation) {
            $this->actingAs($user)->get("/invitations/{$invitation->token}")->assertRedirect('/login');
        }
        $this->assertSame(0, $this->workspace->members()->count());
    }

    public function test_owner_can_change_roles_and_remove_members_but_not_the_owner(): void
    {
        $owner = $this->member('owner');
        $member = $this->member();
        $this->actingAs($owner);

        $this->patch("/members/{$member->id}", ['role' => 'admin'])->assertSessionHasNoErrors();
        $this->assertSame('admin', $this->workspace->roleOf($member));

        $this->patch("/members/{$owner->id}", ['role' => 'member'])->assertStatus(422);
        $this->delete("/members/{$owner->id}")->assertStatus(422);

        $this->delete("/members/{$member->id}")->assertSessionHasNoErrors();
        $this->assertNull($this->workspace->roleOf($member));
    }

    public function test_admin_cannot_remove_members_of_another_workspace(): void
    {
        $admin = $this->member('admin');
        $stranger = $this->member('member', Workspace::factory()->create());

        $this->actingAs($admin)->delete("/members/{$stranger->id}")->assertNotFound();
    }

    public function test_admin_cannot_cancel_another_workspaces_invitation(): void
    {
        $admin = $this->member('admin');
        $foreign = Invitation::factory()->create();

        $this->actingAs($admin)->delete("/invitations/{$foreign->id}")->assertNotFound();
        $this->assertModelExists($foreign);
    }
}
