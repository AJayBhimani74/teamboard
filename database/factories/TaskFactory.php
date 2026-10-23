<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'workspace_id' => fn (array $attrs) => Project::withoutGlobalScopes()->find($attrs['project_id'])->workspace_id,
            'title' => fake()->sentence(4),
            'status' => 'todo',
        ];
    }
}
