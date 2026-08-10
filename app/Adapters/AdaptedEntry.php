<?php

declare(strict_types=1);

namespace App\Adapters;

/**
 * The Adapter's target type: the shape the rest of the kingdom understands.
 *
 * @see https://refactoring.guru/design-patterns/adapter
 */
final readonly class AdaptedEntry
{
    /**
     * @param  array<string, mixed>  $content
     */
    public function __construct(
        public string $title,
        public array $content,
        public string $status = 'published',
    ) {}

    /**
     * @param  array<string, mixed>  $content
     */
    public function withContent(array $content): self
    {
        return new self($this->title, $content, $this->status);
    }

    /**
     * @return array{title: string, content: array<string, mixed>, status: string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
        ];
    }
}
