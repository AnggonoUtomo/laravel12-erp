<?php

namespace App\Modules\Console\SystemSettings\Application\DTOs;

final readonly class PaginationSettingData
{
    /**
     * @param  array<int, int>  $perPageOptions
     */
    public function __construct(
        public int $defaultPerPage,
        public array $perPageOptions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $options = collect($data['per_page_options'] ?? [])
            ->map(fn ($option) => (int) $option)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return new self(
            defaultPerPage: (int) $data['default_per_page'],
            perPageOptions: $options,
        );
    }
}
