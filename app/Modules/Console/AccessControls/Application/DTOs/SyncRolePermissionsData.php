<?php

namespace App\Modules\Console\AccessControls\Application\DTOs;

final readonly class SyncRolePermissionsData
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public array $permissions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            permissions: $data['permissions'] ?? [],
        );
    }
}
