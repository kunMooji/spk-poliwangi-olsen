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

    /** Presisi nilai rapor, mengikuti kolom decimal(5,2) tempat nilainya disimpan. */
    public const SCORE_PRECISION = 2;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Terima koma sebagai pemisah desimal, bukan hanya titik.
     *
     * Responden Indonesia menulis "82,5" — itu penulisan yang benar dalam bahasa
     * Indonesia, tetapi aturan `numeric` menolaknya karena is_numeric() hanya
     * mengenal titik. Tanpa normalisasi ini, nilai desimal yang sah tertolak
     * sebagai "bukan angka" dan responden tidak punya petunjuk apa yang salah.
     *
     * Yang dinormalisasi hanya kolom nilai; kolom lain dibiarkan apa adanya.
     */
    protected function prepareForValidation(): void
    {
        $normalize = static function ($value) {
            if (! is_string($value)) {
                return $value;
            }

            $value = trim($value);

            // Hanya bentuk "angka koma angka" yang diubah. String lain dibiarkan
            // supaya tetap gagal validasi dengan pesan yang benar.
            return preg_match('/^-?\d+,\d+$/', $value) === 1
                ? str_replace(',', '.', $value)
                : $value;
        };

        $payload = [];

        foreach (['rapor_semesters', 'subject_scores'] as $field) {
            $input = $this->input($field);

            if (is_array($input)) {
                $payload[$field] = array_map($normalize, $input);
            }
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
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
        $scores = $this->raporSemesters();

        return round(array_sum($scores) / count($scores), self::SCORE_PRECISION);
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
            // Dibulatkan di sini, bukan dibiarkan dibulatkan diam-diam oleh kolom
            // decimal(5,2), supaya angka yang tersimpan sama dengan angka yang
            // dipakai menghitung rerata.
            $rows[$semester] = round((float) $input[$semester], self::SCORE_PRECISION);
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
            $rows[$subjectId] = ($value === null || $value === '')
                ? null
                : round((float) $value, self::SCORE_PRECISION);
        }

        foreach ($input as $subjectId => $value) {
            $subjectId = (int) $subjectId;

            if (array_key_exists($subjectId, $rows) || $value === null || $value === '') {
                continue;
            }

            $rows[$subjectId] = round((float) $value, self::SCORE_PRECISION);
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
