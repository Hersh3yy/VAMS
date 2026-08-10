<?php

declare(strict_types=1);

namespace App\Data;

/**
 * DTO for one entry about to be previewed or created via JSON import.
 */
final readonly class ImportEntryData
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
