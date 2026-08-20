<?php

namespace App\Modules\Console\GlobalSearches\Application\DTOs;

use App\Models\User;

final readonly class SearchContext
{
    /**
     * @param  array<string, bool>  $permissions
     * @param  array<string, bool>  $roles
     */
    public function __construct(
        public int $userId,
        public array $permissions,
        public array $roles,
        public string $guard = 'web',
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            userId: $user->id,
            permissions: $user->getUserPermissions(),
            roles: $user->getUserRoles(),
        );
    }

    public function can(string $permission): bool
    {
        return (bool) ($this->permissions[$permission] ?? false);
    }

    public function hasRole(string $role): bool
    {
        return (bool) ($this->roles[$role] ?? false);
    }
}
