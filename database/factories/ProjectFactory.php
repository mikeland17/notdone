<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(rand(2, 4), true),
            'priority' => fake()->numberBetween(1, 5),
            'sort_order' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'completed_at' => now(),
        ]);
    }

    public function priority(int $priority): static
    {
        return $this->state(fn () => [
            'priority' => $priority,
        ]);
    }
}
