<?php

namespace App\Modules\Console\SystemSettings\Presentation\Http\Requests;

use App\Modules\Console\SystemSettings\Application\DTOs\MaintenanceModeData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceModeRequest extends FormRequest
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
            'message' => ['nullable', 'string', 'max:500'],
            'page_style' => ['nullable', 'string', 'in:aurora,operations,minimal'],
            'retry_seconds' => ['nullable', 'integer', 'min:30', 'max:2592000'],
            'refresh_seconds' => ['nullable', 'integer', 'min:5', 'max:3600'],
            'secret' => ['nullable', 'string', 'alpha_dash', 'min:8', 'max:80'],
        ];
    }

    public function toDto(): MaintenanceModeData
    {
        return MaintenanceModeData::fromArray($this->validated());
    }
}
