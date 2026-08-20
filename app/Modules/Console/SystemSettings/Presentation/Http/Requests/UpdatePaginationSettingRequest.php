<?php

namespace App\Modules\Console\SystemSettings\Presentation\Http\Requests;

use App\Modules\Console\SystemSettings\Application\DTOs\PaginationSettingData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaginationSettingRequest extends FormRequest
{
    /**
     * @var array<int, int>
     */
    private const AVAILABLE_OPTIONS = [5, 10, 15, 25, 50, 100];

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
            'default_per_page' => ['required', 'integer', Rule::in(self::AVAILABLE_OPTIONS)],
            'per_page_options' => ['required', 'array', 'min:1'],
            'per_page_options.*' => ['required', 'integer', 'distinct', Rule::in(self::AVAILABLE_OPTIONS)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $options = collect($this->input('per_page_options', []))
                ->map(fn ($option) => (int) $option)
                ->all();

            if (! in_array((int) $this->input('default_per_page'), $options, true)) {
                $validator->errors()->add('default_per_page', 'Default rows harus termasuk dalam opsi jumlah row.');
            }
        });
    }

    public function toDto(): PaginationSettingData
    {
        return PaginationSettingData::fromArray($this->validated());
    }
}
