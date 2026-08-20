<?php

namespace App\Modules\Console\SystemSettings\Application\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class BrandingSettingData
{
    public function __construct(
        public string $appName,
        public ?UploadedFile $logo,
        public ?UploadedFile $favicon,
        public bool $removeLogo,
        public bool $removeFavicon,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, ?UploadedFile $logo, ?UploadedFile $favicon): self
    {
        return new self(
            appName: $data['app_name'],
            logo: $logo,
            favicon: $favicon,
            removeLogo: (bool) ($data['remove_logo'] ?? false),
            removeFavicon: (bool) ($data['remove_favicon'] ?? false),
        );
    }
}
