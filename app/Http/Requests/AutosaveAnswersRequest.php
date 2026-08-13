<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Penyimpanan sebagian jawaban kuesioner.
 *
 * Berbeda dengan StoreAnswersRequest, di sini kelengkapan justru tidak boleh
 * diwajibkan — yang disimpan memang jawaban yang baru terisi sebagian.
 */
class AutosaveAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('assessment')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => [
                'nullable',
                'integer',
                'between:'.Setting::get('likert_min').','.Setting::get('likert_max'),
            ],
        ];
    }

    /**
     * Butir yang belum dijawab dibuang di sini, bukan di controller, supaya
     * controller hanya menerima pasangan pertanyaan-nilai yang benar-benar ada.
     *
     * @return array<int, int>
     */
    public function answers(): array
    {
        $answers = [];

        foreach ($this->validated('answers') as $questionId => $score) {
            if ($score === null || $score === '') {
                continue;
            }

            $answers[(int) $questionId] = (int) $score;
        }

        return $answers;
    }
}
