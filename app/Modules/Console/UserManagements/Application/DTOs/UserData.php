<?php

namespace App\Modules\Console\UserManagements\Application\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class UserData
{
    /**
     * @param  array<int, string>  $roles
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public string $name,
        public string $email,
        public bool $sendPasswordResetLink,
        public array $roles,
        public array $permissions,
        public ?UploadedFile $avatar,
        public bool $removeAvatar,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, ?UploadedFile $avatar = null): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            sendPasswordResetLink: (bool) ($data['send_password_reset_link'] ?? false),
            roles: $data['roles'] ?? [],
            permissions: $data['permissions'] ?? [],
            avatar: $avatar,
            removeAvatar: (bool) ($data['remove_avatar'] ?? false),
        );
    }
}
