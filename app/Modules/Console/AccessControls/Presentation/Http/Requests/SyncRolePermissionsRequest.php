<?php

namespace App\Modules\Console\AccessControls\Presentation\Http\Requests;

use App\Modules\Console\AccessControls\Application\DTOs\SyncRolePermissionsData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Role;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');

        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                'distinct',
                Rule::exists('permissions', 'name')->where('guard_name', $role?->guard_name ?? 'web'),
            ],
        ];
    }

    public function toDto(): SyncRolePermissionsData
    {
        return SyncRolePermissionsData::fromArray($this->validated());
    }
}
