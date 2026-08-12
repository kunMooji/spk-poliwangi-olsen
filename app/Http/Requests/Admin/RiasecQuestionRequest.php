<?php

namespace App\Http\Requests\Admin;

use App\Support\Riasec;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RiasecQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'statement' => ['required', 'string', 'max:500'],
            'dimension' => ['required', Rule::in(Riasec::DIMENSIONS)],
            'sort_order' => ['required', 'integer', 'between:0,255'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'statement' => 'pernyataan',
            'dimension' => 'dimensi RIASEC',
            'sort_order' => 'urutan',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return array_merge($this->safe()->all(), ['is_active' => $this->boolean('is_active')]);
    }
}
