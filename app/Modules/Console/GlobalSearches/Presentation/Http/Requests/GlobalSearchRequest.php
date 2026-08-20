<?php

namespace App\Modules\Console\GlobalSearches\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('global-search.search');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'term' => ['required', 'string', 'min:2', 'max:80'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'term' => trim($this->string('term')->toString()),
        ]);
    }
}
