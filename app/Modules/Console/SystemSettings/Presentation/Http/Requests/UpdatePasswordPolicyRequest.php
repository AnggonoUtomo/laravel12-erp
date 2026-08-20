<?php

namespace App\Modules\Console\SystemSettings\Presentation\Http\Requests;

use App\Modules\Console\SystemSettings\Application\DTOs\PasswordPolicyData;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('system-settings.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'min_length' => ['required', 'integer', 'min:8', 'max:128'],
            'require_uppercase' => ['boolean'],
            'require_lowercase' => ['boolean'],
            'require_numbers' => ['boolean'],
            'require_symbols' => ['boolean'],
            'uncompromised' => ['boolean'],
            'expiry_days' => ['required', 'integer', 'min:0', 'max:365'],
            'history_count' => ['required', 'integer', 'min:0', 'max:24'],
        ];
    }

    public function toDto(): PasswordPolicyData
    {
        return PasswordPolicyData::fromArray($this->validated());
    }
}
