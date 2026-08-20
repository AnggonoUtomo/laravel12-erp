<?php

namespace App\Modules\Console\AccessControls\Application\DTOs;

final readonly class RoleData
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public string $name,
        public array $permissions,
        public string $guardName = 'web',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            permissions: $data['permissions'] ?? [],
            guardName: $data['guard_name'] ?? 'web',
        );
    }
}
