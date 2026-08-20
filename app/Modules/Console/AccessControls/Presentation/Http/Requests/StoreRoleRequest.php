<?php

namespace App\Modules\Console\AccessControls\Presentation\Http\Requests;

use App\Models\User;
use App\Modules\Console\AccessControls\Application\DTOs\RoleData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
        $guardName = $this->input('guard_name', 'web');

        return [
            'name' => ['required', 'string', 'max:255', Rule::notIn([User::SUPER_SYSTEM_ROLE]), Rule::unique('roles', 'name')->where('guard_name', $guardName)],
            'guard_name' => ['nullable', 'string', 'max:50'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', $guardName)],
        ];
    }

    public function toDto(): RoleData
    {
        return RoleData::fromArray($this->validated());
    }
}
