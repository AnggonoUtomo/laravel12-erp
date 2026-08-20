<?php

namespace App\Modules\Console\SystemSettings\Presentation\Http\Requests;

use App\Modules\Console\SystemSettings\Application\DTOs\SecurityPolicyData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSecurityPolicyRequest extends FormRequest
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
            'require_email_verification' => ['boolean'],
            'audit_sensitive_actions' => ['boolean'],
            'single_session_per_user' => ['boolean'],
            'allow_account_deletion' => ['boolean'],
            'session_lifetime_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'login_max_attempts' => ['required', 'integer', 'min:3', 'max:20'],
            'login_decay_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'password_confirmation_timeout_seconds' => ['required', 'integer', 'min:60', 'max:10800'],
        ];
    }

    public function toDto(): SecurityPolicyData
    {
        return SecurityPolicyData::fromArray($this->validated());
    }
}
