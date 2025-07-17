<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use App\Models\Album;
use App\Models\Mosaic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjectTypes = [Album::class, Mosaic::class];
        $subjectType = $this->faker->randomElement($subjectTypes);
        
        return [
            'type' => $this->faker->randomElement(['created', 'updated', 'deleted', 'restored']),
            'description' => $this->faker->sentence(),
            'user_id' => User::factory(),
            'subject_type' => $subjectType,
            'subject_id' => $subjectType::factory(),
            'properties' => [
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
                'changes' => [
                    'old' => ['title' => 'Old Title'],
                    'new' => ['title' => 'New Title'],
                ],
            ],
        ];
    }

    /**
     * Indicate that this is a creation activity.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'created',
            'description' => 'Created new ' . class_basename($attributes['subject_type']),
        ]);
    }

    /**
     * Indicate that this is an update activity.
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'updated',
            'description' => 'Updated ' . class_basename($attributes['subject_type']),
        ]);
    }

    /**
     * Indicate that this is a deletion activity.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deleted',
            'description' => 'Deleted ' . class_basename($attributes['subject_type']),
        ]);
    }

    /**
     * Indicate that the activity belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
} 