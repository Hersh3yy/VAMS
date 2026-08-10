<?php

declare(strict_types=1);

namespace App\Adapters;

use InvalidArgumentException;

/**
 * Adapter: turns "whatever the user/LLM pasted" into the kingdom's entry create shape.
 *
 * Incompatible inputs this adapts:
 * - a single object OR a list of objects
 * - nested `content` OR flat field keys matching the entry type's field_config
 *
 * Target interface (what EntryService expects per item):
 *
 * @phpstan-type AdaptedEntry array{title: string, content: array<string, mixed>, status: string}
 *
 * @see https://refactoring.guru/design-patterns/adapter
 */
final readonly class EntryJsonPayloadAdapter
{
    private const META_KEYS = ['title', 'content', 'status'];

    /**
     * @param  array<mixed>  $payload  Decoded JSON object or list
     * @param  list<array<string, mixed>>  $fieldConfig
     * @return list<AdaptedEntry>
     */
    public function adapt(array $payload, array $fieldConfig): array
    {
        $items = $this->normalizeToList($payload);

        if ($items === []) {
            throw new InvalidArgumentException('JSON payload must contain at least one entry object.');
        }

        return array_values(array_map(
            fn (array $item): array => $this->adaptItem($item, $fieldConfig),
            $items,
        ));
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function normalizeToList(array $payload): array
    {
        if ($payload === []) {
            return [];
        }

        if ($this->isList($payload)) {
            $items = [];

            foreach ($payload as $index => $item) {
                if (! is_array($item) || $this->isList($item)) {
                    throw new InvalidArgumentException(
                        "Entry at index {$index} must be a JSON object."
                    );
                }

                /** @var array<string, mixed> $item */
                $items[] = $item;
            }

            return $items;
        }

        /** @var array<string, mixed> $payload */
        return [$payload];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<array<string, mixed>>  $fieldConfig
     * @return AdaptedEntry
     */
    private function adaptItem(array $item, array $fieldConfig): array
    {
        $title = $item['title'] ?? null;

        if (! is_string($title) || trim($title) === '') {
            throw new InvalidArgumentException('Each entry must include a non-empty string "title".');
        }

        $status = $item['status'] ?? 'published';

        if (! is_string($status) || ! in_array($status, ['draft', 'published'], true)) {
            throw new InvalidArgumentException('Entry status must be either "draft" or "published".');
        }

        return [
            'title' => $title,
            'content' => $this->adaptContent($item, $fieldConfig),
            'status' => $status,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<array<string, mixed>>  $fieldConfig
     * @return array<string, mixed>
     */
    private function adaptContent(array $item, array $fieldConfig): array
    {
        if (array_key_exists('content', $item)) {
            $content = $item['content'];

            if (is_string($content)) {
                return ['statement' => $content];
            }

            if (! is_array($content) || $this->isList($content)) {
                throw new InvalidArgumentException('Entry "content" must be a JSON object (or a string for legacy simple entries).');
            }

            /** @var array<string, mixed> $content */
            return $content;
        }

        $fieldNames = array_values(array_filter(array_map(
            static fn (array $field): ?string => isset($field['name']) && is_string($field['name'])
                ? $field['name']
                : null,
            $fieldConfig,
        )));

        $content = [];

        foreach ($fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $item)) {
                $content[$fieldName] = $item[$fieldName];
            }
        }

        // If field_config is empty (legacy I AM), treat non-meta keys as content.
        if ($fieldNames === []) {
            foreach ($item as $key => $value) {
                if (is_string($key) && ! in_array($key, self::META_KEYS, true)) {
                    $content[$key] = $value;
                }
            }
        }

        return $content;
    }

    /**
     * @param  array<mixed>  $value
     */
    private function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_is_list($value);
    }
}
