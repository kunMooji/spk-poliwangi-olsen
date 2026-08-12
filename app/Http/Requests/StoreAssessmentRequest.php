<?php

namespace App\Http\Requests;

use App\Models\Setting;
use App\Support\Riasec;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $minPriorities = (int) Setting::get('min_priorities');

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'school_name' => ['nullable', 'string', 'max:255'],
            'school_major' => ['nullable', 'string', 'max:50'],
            'graduation_year' => ['nullable', 'integer', 'between:2000,'.(date('Y') + 1)],
            'phone' => ['nullable', 'string', 'max:25'],

            'priorities' => ['required', 'array', 'min:'.$minPriorities],
            'priorities.*' => [
                'nullable',
                'distinct',
                Rule::exists('study_programs', 'id')->where('is_active', true),
            ],
        ];

        foreach (array_keys(Riasec::SUBJECTS) as $subject) {
            $rules[$subject.'_score'] = ['required', 'numeric', 'between:0,100'];
        }

        // Slot prioritas sebanyak minimal yang diwajibkan bersifat wajib isi,
        // sisanya opsional sehingga calon mahasiswa boleh menambah pilihan cadangan.
        for ($i = 0; $i < $minPriorities; $i++) {
            $rules["priorities.{$i}"] = [
                'required',
                'distinct',
                Rule::exists('study_programs', 'id')->where('is_active', true),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'full_name' => 'nama lengkap',
            'gender' => 'jenis kelamin',
            'school_name' => 'asal sekolah',
            'school_major' => 'jurusan sekolah',
            'graduation_year' => 'tahun lulus',
            'phone' => 'nomor HP',
        ];

        foreach (Riasec::SUBJECTS as $key => $label) {
            $attributes[$key.'_score'] = 'nilai '.$label;
        }

        foreach (range(0, 9) as $index) {
            $attributes["priorities.{$index}"] = 'prioritas ke-'.($index + 1);
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'priorities.*.distinct' => 'Program studi pada :attribute sudah dipilih di urutan lain.',
            'priorities.*.exists' => 'Program studi pada :attribute tidak tersedia.',
        ];
    }

    /**
     * Daftar prioritas yang benar-benar diisi, sudah rapi terurut 1..N.
     *
     * @return array<int, int>
     */
    public function priorityIds(): array
    {
        return array_values(array_filter(
            array_map('intval', $this->input('priorities', [])),
        ));
    }
}
