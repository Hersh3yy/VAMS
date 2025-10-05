<?php

namespace Database\Factories;

use App\Models\Mosaic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mosaic>
 */
class MosaicFactory extends Factory
{
    protected $model = Mosaic::class;

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
            'columns' => $this->faker->numberBetween(2, 5),
            'display_settings' => [
                'show_titles' => $this->faker->boolean(),
                'show_captions' => $this->faker->boolean(),
                'lazy_loading' => true,
                'hover_effects' => $this->faker->boolean(),
            ],
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the mosaic has specific column count.
     */
    public function withColumns(int $columns): static
    {
        return $this->state(fn (array $attributes) => [
            'columns' => $columns,
        ]);
    }

    /**
     * Indicate that the mosaic belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
