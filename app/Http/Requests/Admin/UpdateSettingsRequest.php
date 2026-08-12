<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
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
            'settings' => ['required', 'array'],
            'settings.threshold' => ['required', 'numeric', 'between:0,100'],
            'settings.threshold_mode' => ['required', Rule::in(['normal', 'raw'])],
            'settings.lambda' => ['required', 'numeric', 'between:0,1'],
            // Epsilon menahan nilai ternormalisasi agar tidak nol; terlalu besar
            // akan mengaburkan selisih antar alternatif.
            'settings.epsilon' => ['required', 'numeric', 'between:0.0000001,0.01'],
            'settings.unselected_priority_score' => ['required', 'numeric', 'between:0,100'],
            'settings.likert_min' => ['required', 'integer', 'between:0,5'],
            'settings.likert_max' => ['required', 'integer', 'between:2,10', 'gt:settings.likert_min'],
            'settings.min_priorities' => ['required', 'integer', 'between:1,10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'settings.threshold' => 'ambang batas rekomendasi',
            'settings.threshold_mode' => 'mode pembanding threshold',
            'settings.lambda' => 'nilai lambda',
            'settings.epsilon' => 'epsilon',
            'settings.unselected_priority_score' => 'skor C8 prodi tak dipilih',
            'settings.likert_min' => 'skala Likert minimum',
            'settings.likert_max' => 'skala Likert maksimum',
            'settings.min_priorities' => 'jumlah minimal prioritas',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settings.likert_max.gt' => 'Skala Likert maksimum harus lebih besar daripada minimum.',
        ];
    }
}
