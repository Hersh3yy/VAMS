<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Album>
 */
class AlbumFactory extends Factory
{
    protected $model = Album::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'order' => $this->faker->numberBetween(0, 100),
            'cover_image_path' => null,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the album has a cover image.
     */
    public function withCoverImage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cover_image_path' => 'albums/covers/'.$this->faker->uuid.'.jpg',
        ]);
    }

    /**
     * Indicate that the album belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
