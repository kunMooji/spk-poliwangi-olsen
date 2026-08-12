<?php

namespace App\Http\Requests\Admin;

use App\Models\Criteria;
use App\Support\Riasec;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriteriaRequest extends FormRequest
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
        $id = $this->route('criteria')?->id;

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('criteria', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'between:0,1'],
            'type' => ['required', Rule::in(['benefit', 'cost'])],
            'source' => ['required', Rule::in(array_keys(Criteria::SOURCES))],
            // Kriteria bersumber nilai rapor wajib menunjuk mata pelajaran,
            // karena DecisionMatrixBuilder membaca kolom {subject}_score.
            'subject' => [
                Rule::requiredIf(fn () => $this->input('source') === 'subject_score'),
                'nullable',
                Rule::in(array_keys(Riasec::SUBJECTS)),
            ],
            'unit' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
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
            'code' => 'kode kriteria',
            'name' => 'nama kriteria',
            'weight' => 'bobot',
            'type' => 'jenis kriteria',
            'source' => 'sumber nilai',
            'subject' => 'mata pelajaran',
            'sort_order' => 'urutan',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->safe()->all();

        // Mata pelajaran hanya bermakna untuk sumber nilai rapor.
        $data['subject'] = $data['source'] === 'subject_score' ? $data['subject'] : null;
        $data['is_active'] = $this->boolean('is_active');

        return $data;
    }
}
