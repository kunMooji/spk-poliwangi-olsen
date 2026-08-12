<?php

namespace App\Http\Requests;

use App\Models\RiasecQuestion;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAnswersRequest extends FormRequest
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
                'required',
                'integer',
                'between:'.Setting::get('likert_min').','.Setting::get('likert_max'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $required = RiasecQuestion::query()->active()->pluck('id');
            $answered = array_keys($this->input('answers', []));

            $missing = $required->diff($answered);

            if ($missing->isNotEmpty()) {
                $validator->errors()->add(
                    'answers',
                    "Masih ada {$missing->count()} pernyataan yang belum dijawab. Mohon lengkapi seluruh pernyataan."
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'Kuesioner belum diisi sama sekali.',
            'answers.*.between' => 'Jawaban harus berada pada skala :min sampai :max.',
        ];
    }
}
