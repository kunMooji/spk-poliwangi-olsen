<?php

namespace App\Http\Requests;

use App\Models\Setting;
use App\Models\Subject;
use App\Support\Rapor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentRequest extends FormRequest
{
    /**
     * Daftar mapel pendukung dibaca beberapa kali dalam satu permintaan —
     * saat validasi dan saat menyusun baris nilai — sehingga diingat di sini.
     *
     * @var array<int, int>|null
     */
    private ?array $supportSubjectIds = null;

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

            // Wajib diisi, berbeda dari biodata lain yang sekadar melengkapi
            // lembar hasil: jenjang menentukan mata pelajaran mana yang
            // ditanyakan pada form nilai rapor.
            'education_level' => ['required', Rule::in(array_keys(Rapor::STUDENT_LEVELS))],
            'school_major' => ['nullable', 'string', 'max:50'],
            'graduation_year' => ['nullable', 'integer', 'between:2000,'.(date('Y') + 1)],
            'phone' => ['nullable', 'string', 'max:25'],

            'rapor_semesters' => ['required', 'array'],

            // Mapel pendukung boleh dikosongkan seluruhnya: responden yang rapornya
            // tidak memuat satu pun mapel tersebut tetap dapat menyelesaikan tes.
            'subject_scores' => ['nullable', 'array'],
            'subject_scores.*' => ['nullable', 'numeric', 'between:0,100'],

            'priorities' => ['required', 'array', 'min:'.$minPriorities],
            'priorities.*' => [
                'nullable',
                'distinct',
                Rule::exists('study_programs', 'id')->where('is_active', true),
            ],
        ];

        foreach (Rapor::SEMESTERS as $semester) {
            $rules["rapor_semesters.{$semester}"] = ['required', 'numeric', 'between:0,100'];
        }

        // Kunci harus merujuk mata pelajaran yang benar-benar ada dan aktif.
        //
        // Yang diterima bukan hanya mapel pendukung yang ditanyakan: responden boleh
        // menambahkan mapel miliknya sendiri — terutama peserta didik SMK dengan
        // mapel konsentrasi keahlian. Ini tidak dapat dipakai menyiasati hasil,
        // karena mapel pendukung tiap prodi tetap ditetapkan admin; nilai tambahan
        // hanya terpakai bila kebetulan ada prodi yang memang memakainya.
        $rules['subject_scores'][] = function (string $attribute, mixed $value, callable $fail): void {
            if (! is_array($value) || $value === []) {
                return;
            }

            $submitted = array_map('intval', array_keys($value));
            $known = Subject::query()->active()->whereIn('id', $submitted)->pluck('id')->all();

            if (array_diff($submitted, $known) !== []) {
                $fail('Terdapat mata pelajaran yang tidak dikenali pada isian nilai pendukung.');
            }
        };

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
            'education_level' => 'jenjang sekolah',
            'school_major' => 'jurusan sekolah',
            'graduation_year' => 'tahun lulus',
            'phone' => 'nomor HP',
        ];

        foreach (Rapor::SEMESTERS as $semester) {
            $attributes["rapor_semesters.{$semester}"] = 'rerata rapor semester '.$semester;
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
            'subject_scores.*.between' => 'Nilai mata pelajaran pendukung harus berada di antara 0 dan 100.',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function supportSubjectIds(): array
    {
        return $this->supportSubjectIds ??= Rapor::supportSubjectIds();
    }

    /**
     * Rerata seluruh semester — komponen pertama SNBP.
     */
    public function raporAverage(): float
    {
        $scores = array_map('floatval', $this->validated('rapor_semesters'));

        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Nilai per semester, sudah rapi terkunci pada semester yang diminta.
     *
     * @return array<int, float>
     */
    public function raporSemesters(): array
    {
        $input = $this->validated('rapor_semesters');

        $rows = [];
        foreach (Rapor::SEMESTERS as $semester) {
            $rows[$semester] = (float) $input[$semester];
        }

        return $rows;
    }

    /**
     * Nilai mapel pendukung untuk seluruh mapel yang ditanyakan, ditambah mapel
     * yang ditambahkan sendiri responden.
     *
     * Mapel yang ditanyakan tetap disimpan meski dikosongkan — nilainya null,
     * sebagai catatan bahwa responden memang tidak menempuhnya. Sebaliknya mapel
     * tambahan yang dibiarkan kosong tidak disimpan sama sekali: ia tidak pernah
     * ditanyakan, jadi tidak ada yang perlu dicatat.
     *
     * @return array<int, float|null>
     */
    public function subjectScores(): array
    {
        $input = $this->validated('subject_scores') ?? [];

        $rows = [];
        foreach ($this->supportSubjectIds() as $subjectId) {
            $value = $input[$subjectId] ?? null;
            $rows[$subjectId] = ($value === null || $value === '') ? null : (float) $value;
        }

        foreach ($input as $subjectId => $value) {
            $subjectId = (int) $subjectId;

            if (array_key_exists($subjectId, $rows) || $value === null || $value === '') {
                continue;
            }

            $rows[$subjectId] = (float) $value;
        }

        return $rows;
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
