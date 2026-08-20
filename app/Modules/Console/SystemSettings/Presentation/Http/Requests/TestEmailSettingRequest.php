<?php

namespace App\Modules\Console\SystemSettings\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestEmailSettingRequest extends FormRequest
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
            'recipient' => ['required', 'email', 'max:255'],
        ];
    }
}
