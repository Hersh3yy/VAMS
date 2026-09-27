<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'image',
            'path' => 'media/'.$this->faker->uuid.'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(100000, 2000000),
            'metadata' => [
                'width' => $this->faker->numberBetween(800, 2000),
                'height' => $this->faker->numberBetween(600, 1500),
                'original_name' => $this->faker->word().'.jpg',
                'uploaded_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Indicate that this is a video media.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'video',
            'path' => 'media/'.$this->faker->uuid.'.mp4',
            'mime_type' => 'video/mp4',
            'size' => $this->faker->numberBetween(5000000, 50000000),
            'metadata' => [
                'duration' => $this->faker->numberBetween(30, 300),
                'width' => $this->faker->numberBetween(720, 1920),
                'height' => $this->faker->numberBetween(480, 1080),
                'original_name' => $this->faker->word().'.mp4',
                'uploaded_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Indicate that this is a WebP image.
     */
    public function webp(): static
    {
        return $this->state(fn (array $attributes): array => [
            'path' => 'media/'.$this->faker->uuid.'.webp',
            'mime_type' => 'image/webp',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'webp_optimized' => true,
                'original_format' => 'jpeg',
            ]),
        ]);
    }
}
