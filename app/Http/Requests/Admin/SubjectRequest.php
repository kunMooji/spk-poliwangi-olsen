<?php

namespace App\Http\Requests\Admin;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Kode dipakai sebagai penanda stabil di seeder dan ekspor. Admin yang
     * menambah mapel produktif SMK tidak perlu memikirkannya, jadi kode
     * diturunkan dari namanya bila dikosongkan.
     *
     * Diturunkan sebelum validasi, bukan sesudah, supaya aturan unik menguji
     * kode yang benar-benar akan tersimpan.
     */
    protected function prepareForValidation(): void
    {
        $code = trim((string) $this->input('code'));

        $this->merge([
            'code' => Str::slug($code !== '' ? $code : (string) $this->input('name')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('subject')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', Rule::unique('subjects', 'code')->ignore($id)],
            'education_level' => ['required', Rule::in(array_keys(Subject::EDUCATION_LEVELS))],
            'group' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama mata pelajaran',
            'code' => 'kode',
            'education_level' => 'jenjang',
            'group' => 'kelompok',
            'sort_order' => 'urutan',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->safe()->all();

        $data['is_active'] = $this->boolean('is_active');

        return $data;
    }
}
