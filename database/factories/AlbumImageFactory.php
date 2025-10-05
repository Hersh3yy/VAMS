<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\AlbumImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlbumImage>
 */
class AlbumImageFactory extends Factory
{
    protected $model = AlbumImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'album_id' => Album::factory(),
            'path' => 'albums/'.$this->faker->uuid.'.jpg',
            'title' => $this->faker->sentence(2),
            'caption' => $this->faker->sentence(),
            'author' => $this->faker->name(),
            'order' => $this->faker->numberBetween(0, 100),
            'properties' => [
                'type' => 'image',
                'width' => $this->faker->numberBetween(800, 2000),
                'height' => $this->faker->numberBetween(600, 1500),
                'file_size' => $this->faker->numberBetween(100000, 2000000),
            ],
        ];
    }

    /**
     * Indicate that this is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'path' => 'https://www.youtube.com/watch?v='.$this->faker->regexify('[A-Za-z0-9_-]{11}'),
            'properties' => [
                'type' => 'video',
                'is_video' => true,
                'video_url' => 'https://www.youtube.com/watch?v='.$this->faker->regexify('[A-Za-z0-9_-]{11}'),
                'thumbnail_url' => 'https://img.youtube.com/vi/'.$this->faker->regexify('[A-Za-z0-9_-]{11}').'/maxresdefault.jpg',
            ],
        ]);
    }

    /**
     * Indicate that the image belongs to a specific album.
     */
    public function forAlbum(Album $album): static
    {
        return $this->state(fn (array $attributes) => [
            'album_id' => $album->id,
        ]);
    }

    /**
     * Indicate that the image has specific order.
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }
}
