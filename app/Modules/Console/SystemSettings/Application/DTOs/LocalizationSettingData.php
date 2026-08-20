<?php

namespace App\Modules\Console\SystemSettings\Application\DTOs;

final readonly class LocalizationSettingData
{
    public function __construct(
        public string $timezone,
        public string $dateFormat,
        public string $timeFormat,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            timezone: $data['timezone'],
            dateFormat: $data['date_format'],
            timeFormat: $data['time_format'],
        );
    }
}
