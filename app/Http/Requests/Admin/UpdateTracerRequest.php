<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTracerRequest extends FormRequest
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
            'programs' => ['required', 'array'],
            'programs.*.alumni_count' => ['required', 'integer', 'min:0'],
            'programs.*.employed_count' => ['required', 'integer', 'min:0'],
            'programs.*.tracer_year' => ['nullable', 'integer', 'between:2000,'.date('Y')],
        ];
    }

    /**
     * Alumni terserap tidak boleh melebihi total alumni — diperiksa per baris
     * supaya pesan kesalahan menunjuk prodi yang bermasalah.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            foreach ((array) $this->input('programs', []) as $id => $row) {
                if ((int) ($row['employed_count'] ?? 0) > (int) ($row['alumni_count'] ?? 0)) {
                    $validator->errors()->add(
                        "programs.{$id}.employed_count",
                        'Jumlah alumni terserap kerja tidak boleh melebihi jumlah alumni.'
                    );
                }
            }
        });
    }
}
