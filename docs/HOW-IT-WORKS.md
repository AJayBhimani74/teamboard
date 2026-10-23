# How Teamboard works

Read this before an interview. It explains the project in plain words.

## The idea

This is "multi-tenancy": many customers (workspaces) use the same app and the same database, but each one only sees its own data. Most SaaS products work like this.

There are three ways to do it: one database per customer, one schema per customer, or one shared database with a `workspace_id` column. This project uses the last one because it is the simplest and fine for small and medium apps. The risk is forgetting a `where workspace_id = ?` somewhere and leaking data, so the project removes that risk by doing the filtering in one place.

## Where things live

| File | What it does |
|---|---|
| `app/Models/Concerns/BelongsToWorkspace.php` | The trait that makes a model tenant-aware. |
| `app/CurrentWorkspace.php` | A tiny holder for "the workspace we are working in right now". |
| `app/Http/Middleware/SetWorkspace.php` | Finds the workspace for this request and checks membership. |
| `app/Http/Middleware/EnsureWorkspaceAdmin.php` | Allows only owners (or owners and admins) on a route. |
| `app/Models/Workspace.php` | Plan limits (`PLANS`, `canAddMember`, `canAddProject`) and the member list. |
| `app/Http/Controllers/InvitationController.php` | Sending and accepting invitations. |
| `workspace_user` table | Who belongs to which workspace, and with what role. |

## Following one request: open a project

1. `GET /projects/7`.
2. `auth` makes sure you are logged in.
3. `SetWorkspace` reads `workspace_id` from the session. It looks for that workspace *among the user's own workspaces*. If it isn't one of theirs, it falls back to their first workspace. Nobody can pick someone else's workspace by editing the session.
4. The workspace is stored in `CurrentWorkspace` and the user's role is shared with the views.
5. Laravel loads Project 7. The global scope adds `where workspace_id = <current>`. If Project 7 belongs to another workspace, the query finds nothing and the answer is **404**, not 403. We don't even confirm that it exists.
6. The page loads the project's tasks, also scoped.

## The trap worth knowing

Laravel loads `{project}` from the URL in a middleware called `SubstituteBindings`. If that runs *before* `SetWorkspace`, the current workspace isn't known yet, the scope does nothing, and any project can be opened by id. That is a real data leak and it would look fine in normal use.

The fix is one line in `bootstrap/app.php`: `prependToPriorityList(before: SubstituteBindings::class, prepend: SetWorkspace::class)`. I tested it by removing the line, and the isolation tests fail. Good thing to say in an interview: "I broke it on purpose to make sure the test would catch it."

## Creating records

The trait also fills `workspace_id` on `creating`, so controllers just write `Project::create($data)`. Nobody has to remember to pass the workspace, and a user can't pass someone else's id because `workspace_id` isn't taken from the form.

## Roles

Stored on the `workspace_user` pivot (`owner`, `admin`, `member`), so a role belongs to a person *in a workspace*. The middleware reads it and routes are grouped by who may use them. The owner can't be removed or demoted (422).

## Invitations

1. An admin enters an email. We check: not already a member, not already invited, and the plan still has room (members + pending invites).
2. A row is saved with a random 40-character token and an expiry of 7 days. A queued email carries the link.
3. When someone opens the link:
   - not logged in: the token is kept in the session and they go to register, then straight back to the link;
   - logged in with a different email: refused (403);
   - expired or already used: refused;
   - otherwise they are added with the invited role and the invitation is marked accepted.

## Plan limits

`Workspace::PLANS` is a plain array. Controllers ask `canAddProject()` or `canAddMember()` before creating. Going back to Free is blocked if the workspace is over the Free limits, so nobody ends up above their plan.

## Questions you may get

**Why a shared database and not one per tenant?** Simplicity and cost. One database per tenant isolates better and is easier for per-customer backups, but migrations and connections get harder to manage.

**What if a developer forgets the trait on a new model?** That model would leak. Mitigations: a test that lists every model with a `workspace_id` and checks it uses the trait, code review (see the checklist in team-playbook), or a database-level rule like row-level security in Postgres.

**What about queue jobs and artisan commands?** There is no request, so no current workspace and the scope does nothing. A job that touches tenant data must set the workspace itself or pass the id in. This is also why seeders and tests use `withoutGlobalScopes()` when they need to look across workspaces.

**Why 404 and not 403 for another workspace's project?** To avoid confirming that the id exists.

**How would real billing work?** Use Stripe through Laravel Cashier on the workspace (not the user), update `plan` from the Stripe webhook instead of a button, and handle failed payments by downgrading. The `paywebhook` project in my portfolio shows the webhook part.

## Try it yourself

Run the seeder, log in as `owner@example.com`, open the board, then go to Members and invite someone. Look in `storage/logs/laravel.log` for the invitation email and open the link in a private window. Then read `tests/Feature/TenancyTest.php` and `MembersAndPlanTest.php`; the test names are the rules.
