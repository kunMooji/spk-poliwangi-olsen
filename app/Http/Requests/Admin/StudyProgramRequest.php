<?php

namespace App\Http\Requests\Admin;

use App\Models\Subject;
use App\Support\Rapor;
use App\Support\Riasec;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudyProgramRequest extends FormRequest
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
        $id = $this->route('studyProgram')?->id;

        $rules = [
            'code' => ['required', 'string', 'max:20', Rule::unique('study_programs', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', Rule::in(['D2', 'D3', 'D4', 'S1'])],
            'department' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],

            'alumni_count' => ['required', 'integer', 'min:0'],
            'employed_count' => ['required', 'integer', 'min:0', 'lte:alumni_count'],
            'tracer_year' => ['nullable', 'integer', 'between:2000,'.date('Y')],

            'is_active' => ['boolean'],
        ];

        // Mata pelajaran pendukung (C2). Batas dua mapel SNBP ditegakkan per
        // asal sekolah pendaftar lewat withValidator(), bukan lewat jumlah
        // seluruh mapel di sini — satu prodi boleh melayani SMA dan beberapa
        // rumpun keahlian SMK sekaligus. Slot boleh dikosongkan sehingga prodi
        // tanpa mapel pendukung tetap dapat disimpan.
        $rules['support_subjects'] = ['nullable', 'array'];
        $rules['support_subjects.*'] = [
            'nullable',
            'distinct',
            Rule::exists('subjects', 'id')->where('is_active', true),
        ];

        // Profil RIASEC prodi (C3): pembanding cosine similarity, skala 0-100.
        foreach (Riasec::DIMENSIONS as $dimension) {
            $rules['riasec_'.strtolower($dimension)] = ['required', 'integer', 'between:0,100'];
        }

        return $rules;
    }

    /**
     * Tegakkan batas dua mapel pendukung SNBP menurut asal sekolah pendaftar.
     *
     * Yang dihitung bukan berapa mapel yang ditautkan prodi, melainkan berapa
     * yang berlaku bagi satu peserta didik. Prodi boleh memakai Matematika dan
     * Fisika untuk pendaftar SMA sekaligus Teknik Kendaraan Ringan untuk
     * pendaftar SMK Teknologi Manufaktur dan Rekayasa — tetapi tidak boleh
     * membuat salah satu kelompok dinilai dengan lebih dari dua mapel.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ids = array_filter(array_map('intval', (array) $this->input('support_subjects', [])));

            if ($ids === []) {
                return;
            }

            $overloaded = Rapor::overloadedCombinations(Subject::query()->findMany($ids));

            if ($overloaded !== []) {
                $validator->errors()->add('support_subjects', sprintf(
                    'Mata pelajaran pendukung paling banyak %d untuk tiap asal sekolah, '
                    .'tetapi kombinasi berikut melebihinya: %s.',
                    Rapor::MAX_SUPPORT_SUBJECTS,
                    implode('; ', $overloaded),
                ));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'code' => 'kode prodi',
            'name' => 'nama prodi',
            'level' => 'jenjang',
            'department' => 'jurusan',
            'alumni_count' => 'jumlah alumni',
            'employed_count' => 'alumni terserap kerja',
            'tracer_year' => 'tahun tracer study',
        ];

        // Jumlah slot mengikuti yang benar-benar dikirim form, karena banyaknya
        // mapel pendukung tidak lagi dibatasi angka tetap.
        foreach (array_keys((array) $this->input('support_subjects', [])) as $index) {
            $attributes["support_subjects.{$index}"] = 'mata pelajaran pendukung '.((int) $index + 1);
        }

        foreach (Riasec::DIMENSIONS as $dimension) {
            $attributes['riasec_'.strtolower($dimension)] = 'profil '.Riasec::name($dimension);
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employed_count.lte' => 'Jumlah alumni terserap kerja tidak boleh melebihi jumlah alumni.',
            'support_subjects.*.distinct' => 'Mata pelajaran pendukung tidak boleh dipilih dua kali.',
        ];
    }

    /**
     * Data siap simpan: employment_rate selalu diturunkan dari data tracer
     * supaya tidak pernah berbeda dengan alumni_count & employed_count.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->safe()->except('support_subjects');

        $alumni = (int) $data['alumni_count'];
        $data['employment_rate'] = $alumni > 0 ? round($data['employed_count'] / $alumni, 3) : 0;
        $data['is_active'] = $this->boolean('is_active');
        $data['tracer_updated_at'] = now();

        return $data;
    }

    /**
     * Mapel pendukung siap dipakai `sync()`, dengan `position` mengikuti urutan
     * slot pada form. Slot kosong dibuang sehingga admin boleh mengisi hanya
     * satu mapel, atau melewati slot pertama.
     *
     * @return array<int, array{position: int}>
     */
    public function supportSubjectSync(): array
    {
        $ids = array_filter(array_map('intval', $this->validated('support_subjects') ?? []));

        $sync = [];
        foreach (array_values($ids) as $index => $subjectId) {
            $sync[$subjectId] = ['position' => $index + 1];
        }

        return $sync;
    }
}
