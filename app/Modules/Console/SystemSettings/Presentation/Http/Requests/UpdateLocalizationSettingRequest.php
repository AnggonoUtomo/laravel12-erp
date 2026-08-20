<?php

namespace App\Modules\Console\SystemSettings\Presentation\Http\Requests;

use App\Modules\Console\SystemSettings\Application\DTOs\LocalizationSettingData;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocalizationSettingRequest extends FormRequest
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
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'date_format' => ['required', 'string', Rule::in(['d M Y', 'd/m/Y', 'Y-m-d', 'm/d/Y', 'l, d F Y'])],
            'time_format' => ['required', 'string', Rule::in(['H:i', 'H:i:s', 'h:i A', 'h:i:s A'])],
        ];
    }

    public function toDto(): LocalizationSettingData
    {
        return LocalizationSettingData::fromArray($this->validated());
    }
}
