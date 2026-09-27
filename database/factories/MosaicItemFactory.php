<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Mosaic;
use App\Models\MosaicItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MosaicItem>
 */
class MosaicItemFactory extends Factory
{
    protected $model = MosaicItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mosaic_id' => Mosaic::factory(),
            'column_index' => $this->faker->numberBetween(0, 4),
            'type' => 'album',
            'content' => [],
            'album_id' => Album::factory(),
            'properties' => [
                'show_title' => $this->faker->boolean(),
                'show_caption' => $this->faker->boolean(),
                'custom_title' => null,
                'custom_caption' => null,
            ],
            'order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that this is a media type item.
     */
    public function mediaType(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'media',
            'album_id' => null,
            'content' => [
                'albums/media/'.$this->faker->uuid.'.jpg',
                'albums/media/'.$this->faker->uuid.'.jpg',
            ],
        ]);
    }

    /**
     * Indicate that this is a text type item.
     */
    public function textType(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'text',
            'album_id' => null,
            'content' => [
                'text' => $this->faker->paragraph(),
                'style' => 'default',
            ],
        ]);
    }

    /**
     * Indicate that the item belongs to a specific mosaic.
     */
    public function forMosaic(Mosaic $mosaic): static
    {
        return $this->state(fn (array $attributes): array => [
            'mosaic_id' => $mosaic->id,
        ]);
    }

    /**
     * Indicate that the item is in a specific column.
     */
    public function inColumn(int $columnIndex): static
    {
        return $this->state(fn (array $attributes): array => [
            'column_index' => $columnIndex,
        ]);
    }

    /**
     * Indicate that the item has specific order.
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'order' => $order,
        ]);
    }
}
