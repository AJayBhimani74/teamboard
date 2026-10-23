<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    public const PLANS = [
        'free' => ['members' => 3, 'projects' => 3],
        'pro' => ['members' => 25, 'projects' => null],
    ];

    protected $fillable = ['name', 'plan'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function roleOf(User $user): ?string
    {
        return $this->members()->where('users.id', $user->id)->first()?->pivot->role;
    }

    public function limit(string $what): ?int
    {
        return self::PLANS[$this->plan][$what];
    }

    public function canAddMember(): bool
    {
        $taken = $this->members()->count() + $this->invitations()->pending()->count();

        return $taken < $this->limit('members');
    }

    public function canAddProject(): bool
    {
        $max = $this->limit('projects');

        return $max === null || Project::withoutGlobalScopes()->where('workspace_id', $this->id)->count() < $max;
    }
}
