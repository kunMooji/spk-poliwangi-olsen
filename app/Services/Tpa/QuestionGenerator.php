<?php

namespace App\Services\Tpa;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\PaymentRequiredException;
use Anthropic\Core\Exceptions\RateLimitException;
use RuntimeException;

/**
 * Menyusun satu set soal latihan SKD (15 soal + hint + pembahasan) lewat
 * Claude API dengan structured output, lalu memvalidasi hasilnya.
 */
class QuestionGenerator
{
    private const KEYS = ['A', 'B', 'C', 'D', 'E'];

    /**
     * @return list<array{sub_topic:string,question:string,options:list<array{key:string,text:string,score:int}>,correct_key:?string,hint:string,explanation:string}>
     */
    public function generate(string $category, int $difficulty): array
    {
        $apiKey = config('tpa.anthropic_api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('ANTHROPIC_API_KEY belum diisi di file .env.');
        }

        $client = new Client(apiKey: $apiKey);

        try {
            $message = $client->messages->create(
                model: config('tpa.model'),
                maxTokens: 32000,
                system: $this->systemPrompt($category),
                messages: [
                    ['role' => 'user', 'content' => $this->userPrompt($category, $difficulty)],
                ],
                outputConfig: [
                    'effort' => config('tpa.effort'),
                    'format' => [
                        'type' => 'json_schema',
                        'schema' => $this->schema(),
                    ],
                ],
                requestOptions: ['timeout' => config('tpa.timeout'), 'maxRetries' => 1],
            );
        } catch (AuthenticationException) {
            throw new RuntimeException('API key Claude tidak valid. Periksa ANTHROPIC_API_KEY.');
        } catch (PaymentRequiredException) {
            throw new RuntimeException('Saldo kredit API Claude habis. Isi ulang di console.anthropic.com.');
        } catch (RateLimitException) {
            throw new RuntimeException('Terlalu banyak permintaan ke Claude. Tunggu sebentar lalu coba lagi.');
        } catch (APIStatusException $e) {
            report($e);
            $detail = $this->apiErrorMessage($e);

            if (str_contains(strtolower($detail), 'credit balance')) {
                throw new RuntimeException('Saldo kredit API Claude kurang. Isi ulang di console.anthropic.com → Billing.');
            }

            throw new RuntimeException("Claude API menolak permintaan ({$e->status}): {$detail}");
        } catch (APIConnectionException $e) {
            report($e);
            throw new RuntimeException('Gagal terhubung ke Claude API (koneksi/timeout). Coba lagi.');
        }

        if ($message->stopReason === 'refusal') {
            throw new RuntimeException('Claude menolak menyusun soal ini. Coba lagi.');
        }

        if ($message->stopReason === 'max_tokens') {
            throw new RuntimeException('Jawaban Claude terpotong. Coba lagi.');
        }

        $json = null;
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json = json_decode($block->text, true);
                break;
            }
        }

        return $this->validate($json['questions'] ?? null, $category === 'tkp');
    }

    /** Ambil pesan error asli dari body respons API, tanpa membocorkan detail request. */
    private function apiErrorMessage(APIStatusException $e): string
    {
        $body = json_decode((string) $e->response?->getBody(), true);
        $message = $body['error']['message'] ?? 'tanpa keterangan';

        return mb_strimwidth((string) $message, 0, 300, '…');
    }

    private function systemPrompt(string $category): string
    {
        $cat = config("tpa.categories.{$category}");
        $subtopics = collect($cat['subtopics'])
            ->map(fn ($desc, $name) => "- {$name}: {$desc}")
            ->implode("\n");
        $context = config('tpa.current_context');

        $answerRules = $category === 'tkp'
            ? <<<'TXT'
Aturan TKP: tidak ada jawaban salah. Kelima opsi WAJIB memiliki skor berbeda 1, 2, 3, 4, dan 5 (masing-masing tepat
satu kali, urutan diacak, jangan selalu opsi A yang bernilai 5). Isi correct_key dengan string kosong "".
Skor 5 = sikap paling ideal ASN profesional berintegritas; skor 1 = paling tidak tepat. Opsi harus sama-sama terdengar
wajar sehingga peserta perlu menimbang, bukan sekadar membuang opsi yang jelas buruk.
Pembahasan menjelaskan alasan skor tiap opsi (urutkan dari skor 5 ke 1).
TXT
            : <<<'TXT'
Aturan TWK/TIU: tepat satu opsi benar. Isi correct_key dengan huruf opsi yang benar (A-E). Isi score opsi benar = 5,
opsi lain = 0. Sebarkan posisi jawaban benar secara acak di antara A-E (jangan berpola).
Pembahasan menjelaskan langkah penyelesaian lengkap dan mengapa setiap distraktor salah. Untuk soal hitungan,
tulis langkah perhitungan dan pastikan jawabannya benar-benar ada di salah satu opsi.
TXT;

        return <<<TXT
Anda adalah penyusun soal Seleksi Kompetensi Dasar (SKD) CPNS Indonesia yang berpengalaman, mengikuti kisi-kisi resmi
PermenPANRB tentang Nilai Ambang Batas SKD. Kategori: {$cat['name']} ({$cat['short']}). Penilaian: {$cat['scoring']}

Kisi-kisi sub-materi {$cat['short']}:
{$subtopics}

Konteks terkini:
{$context}

{$answerRules}

Ketentuan umum:
- Semua teks dalam Bahasa Indonesia baku yang baik dan benar.
- Setiap soal memiliki tepat 5 opsi dengan key A, B, C, D, E berurutan.
- Soal tidak boleh bergantung pada gambar. Untuk materi figural, gunakan pola simbol/huruf/angka yang ditulis sebagai teks.
- Hint adalah petunjuk singkat (1-2 kalimat) yang mengarahkan cara berpikir tanpa membocorkan jawaban.
- Jangan mengulang soal yang mirip dalam satu set; variasikan sub-materi dan konteks.
- Isi sub_topic dengan salah satu nama sub-materi kisi-kisi di atas.
TXT;
    }

    private function userPrompt(string $category, int $difficulty): string
    {
        $count = config('tpa.questions_per_tryout');
        $rubric = $this->rubricFor($difficulty);
        $subtopics = array_keys(config("tpa.categories.{$category}.subtopics"));
        shuffle($subtopics);
        $focus = implode(', ', $subtopics);
        $seed = random_int(1000, 9999);
        $label = config("tpa.categories.{$category}.short");

        return <<<TXT
Susun {$count} soal {$label} dengan tingkat kesulitan {$difficulty}% (skala 10-100%).

Pedoman tingkat kesulitan {$difficulty}%: {$rubric}
Kesulitan diukur dari cara memahami soal, kedalaman analisis, jumlah langkah penalaran, dan kerumitan stem/opsi.

Bagi soal ke seluruh sub-materi secara merata, dengan urutan prioritas: {$focus}.
Kode variasi set: {$seed} (gunakan untuk memilih konteks dan angka yang berbeda dari set lain).
TXT;
    }

    private function rubricFor(int $difficulty): string
    {
        $rubric = config('tpa.difficulty_rubric');

        foreach ($rubric as $max => $text) {
            if ($difficulty <= $max) {
                return $text;
            }
        }

        return end($rubric);
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'questions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'sub_topic' => ['type' => 'string'],
                            'question' => ['type' => 'string'],
                            'options' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'key' => ['type' => 'string', 'enum' => self::KEYS],
                                        'text' => ['type' => 'string'],
                                        'score' => ['type' => 'integer'],
                                    ],
                                    'required' => ['key', 'text', 'score'],
                                    'additionalProperties' => false,
                                ],
                            ],
                            'correct_key' => ['type' => 'string'],
                            'hint' => ['type' => 'string'],
                            'explanation' => ['type' => 'string'],
                        ],
                        'required' => ['sub_topic', 'question', 'options', 'correct_key', 'hint', 'explanation'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['questions'],
            'additionalProperties' => false,
        ];
    }

    private function validate(mixed $questions, bool $isTkp): array
    {
        $expected = config('tpa.questions_per_tryout');

        if (! is_array($questions) || count($questions) < $expected) {
            throw new RuntimeException('Jumlah soal dari Claude kurang. Coba lagi.');
        }

        $result = [];
        foreach (array_slice($questions, 0, $expected) as $q) {
            $options = collect($q['options'] ?? [])
                ->filter(fn ($o) => in_array($o['key'] ?? null, self::KEYS, true))
                ->keyBy('key')
                ->sortKeys();

            if ($options->count() !== 5 || blank($q['question'] ?? null)) {
                throw new RuntimeException('Format soal dari Claude tidak lengkap. Coba lagi.');
            }

            if ($isTkp) {
                $scores = $options->pluck('score')->map(fn ($s) => (int) $s)->sort()->values()->all();
                if ($scores !== [1, 2, 3, 4, 5]) {
                    // Normalisasi: urutkan ulang skor agar tetap 1..5 unik.
                    $rank = 1;
                    $options = $options->sortBy('score')->map(function ($o) use (&$rank) {
                        $o['score'] = $rank++;

                        return $o;
                    })->sortKeys();
                }
                $correct = null;
            } else {
                $correct = strtoupper(trim($q['correct_key'] ?? ''));
                if (! in_array($correct, self::KEYS, true)) {
                    throw new RuntimeException('Kunci jawaban dari Claude tidak valid. Coba lagi.');
                }
                $options = $options->map(fn ($o) => [...$o, 'score' => $o['key'] === $correct ? 5 : 0]);
            }

            $result[] = [
                'sub_topic' => (string) ($q['sub_topic'] ?? ''),
                'question' => (string) $q['question'],
                'options' => $options->map(fn ($o) => [
                    'key' => $o['key'],
                    'text' => (string) $o['text'],
                    'score' => (int) $o['score'],
                ])->values()->all(),
                'correct_key' => $correct,
                'hint' => (string) ($q['hint'] ?? ''),
                'explanation' => (string) ($q['explanation'] ?? ''),
            ];
        }

        return $result;
    }
}
