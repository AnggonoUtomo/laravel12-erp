<?php

namespace App\Modules\Console\SystemSettings\Presentation\Http\Requests;

use App\Modules\Console\SystemSettings\Application\DTOs\EmailSettingData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailSettingRequest extends FormRequest
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
            'enabled' => ['boolean'],
            'mailer' => ['required', 'string', Rule::in(['smtp', 'log', 'array'])],
            'host' => ['nullable', 'required_if:mailer,smtp', 'string', 'max:255'],
            'port' => ['nullable', 'required_if:mailer,smtp', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'send_credentials_on_create' => ['boolean'],
            'send_credentials_on_password_update' => ['boolean'],
            'credential_subject' => ['nullable', 'string', 'max:255'],
            'credential_intro' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toDto(): EmailSettingData
    {
        $validated = $this->validated();

        if (($validated['encryption'] ?? null) === 'none') {
            $validated['encryption'] = null;
        }

        return EmailSettingData::fromArray($validated);
    }
}
