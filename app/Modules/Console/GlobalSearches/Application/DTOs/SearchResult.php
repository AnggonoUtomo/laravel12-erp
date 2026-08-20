<?php

namespace App\Modules\Console\GlobalSearches\Application\DTOs;

final readonly class SearchResult
{
    /**
     * @param  array<int, string>  $badges
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $id,
        public string $provider,
        public string $type,
        public string $title,
        public string $group,
        public string $url,
        public ?string $description = null,
        public array $badges = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'type' => $this->type,
            'title' => $this->title,
            'group' => $this->group,
            'url' => $this->url,
            'description' => $this->description,
            'badges' => $this->badges,
            'meta' => $this->meta,
        ];
    }
}
