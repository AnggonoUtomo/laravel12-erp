<?php

namespace App\Modules\Console\SystemSettings\Presentation\Http\Requests;

use App\Modules\Console\SystemSettings\Application\DTOs\BrandingSettingData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingSettingRequest extends FormRequest
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
            'app_name' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,webp,svg', 'max:1024'],
            'remove_logo' => ['boolean'],
            'remove_favicon' => ['boolean'],
        ];
    }

    public function toDto(): BrandingSettingData
    {
        return BrandingSettingData::fromArray(
            $this->validated(),
            $this->file('logo'),
            $this->file('favicon'),
        );
    }
}
