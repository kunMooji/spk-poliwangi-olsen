<?php

namespace Tests\Feature;

use App\Models\TpaBankQuestion;
use App\Models\TpaTryout;
use App\Services\Tpa\BankImporter;
use App\Models\TpaUser;
use App\Models\User;
use App\Services\Tpa\QuestionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TpaTest extends TestCase
{
    use RefreshDatabase;

    private function tpaUser(): TpaUser
    {
        return TpaUser::create(['username' => 'brilian', 'password' => 'rahasia123']);
    }

    private function fakeQuestions(bool $tkp): array
    {
        return collect(range(1, 15))->map(fn ($i) => [
            'sub_topic' => 'Uji',
            'question' => "Soal nomor {$i}?",
            'options' => collect(['A', 'B', 'C', 'D', 'E'])->map(fn ($k, $idx) => [
                'key' => $k,
                'text' => "Opsi {$k}",
                'score' => $tkp ? $idx + 1 : ($k === 'B' ? 5 : 0),
            ])->all(),
            'correct_key' => $tkp ? null : 'B',
            'hint' => 'Petunjuk',
            'explanation' => 'Pembahasan',
        ])->all();
    }

    private function mockGenerator(bool $tkp = false): void
    {
        $this->mock(QuestionGenerator::class)
            ->shouldReceive('generate')
            ->andReturn($this->fakeQuestions($tkp));
    }

    public function test_login_page_is_reachable_and_not_linked_from_home(): void
    {
        $this->get('/logintpa')->assertOk()->assertSee('Latihan SKD CPNS');
        $this->get('/')->assertDontSee('logintpa');
    }

    public function test_tpa_login_works_and_dashboard_requires_tpa_guard(): void
    {
        $this->tpaUser();

        $this->get('/logintpa/dashboard')->assertRedirect(route('tpa.login'));

        // Akun SPK biasa tidak membuka area TPA.
        $this->actingAs(User::factory()->create())->get('/logintpa/dashboard')->assertRedirect(route('tpa.login'));

        $this->post('/logintpa', ['username' => 'brilian', 'password' => 'salah'])->assertSessionHasErrors('username');
        $this->post('/logintpa', ['username' => 'Brilian', 'password' => 'rahasia123'])->assertRedirect(route('tpa.dashboard'));
        $this->get('/logintpa/dashboard')->assertOk()->assertSee('Ketepatan menjawab');
    }

    public function test_full_twk_tryout_flow_scores_correctly(): void
    {
        $user = $this->tpaUser();
        $this->mockGenerator();

        $redirect = $this->actingAs($user, 'tpa')
            ->postJson('/logintpa/tryout/twk/generate', ['difficulty' => 70, 'source' => 'ai'])
            ->assertOk()->json('redirect');

        $tryout = TpaTryout::firstOrFail();
        $this->assertSame(route('tpa.tryout.quiz', $tryout), $redirect);

        // Halaman kuis tidak membocorkan pembahasan.
        $this->get($redirect)->assertOk()->assertDontSee('Pembahasan');

        $questions = $tryout->questions;
        foreach ($questions->take(10) as $q) {
            $this->postJson(route('tpa.tryout.answer', $tryout), [
                'question_id' => $q->id, 'answer' => 'B', 'elapsed_ms' => 20000, 'hint_used' => $q->number === 1,
            ])->assertOk();
        }
        foreach ($questions->slice(10, 3) as $q) {
            $this->postJson(route('tpa.tryout.answer', $tryout), [
                'question_id' => $q->id, 'answer' => 'A', 'elapsed_ms' => 10000,
            ])->assertOk();
        }

        $this->post(route('tpa.tryout.finish', $tryout))->assertRedirect(route('tpa.tryout.result', $tryout));

        $tryout->refresh();
        $this->assertSame(50, $tryout->score);
        $this->assertSame(75, $tryout->max_score);
        $this->assertSame(10, $tryout->correct_count);
        $this->assertSame(230000, $tryout->total_time_ms);
        $this->assertTrue($tryout->questions()->where('number', 1)->value('hint_used'));

        $this->get(route('tpa.tryout.result', $tryout))->assertOk()->assertSee('Pembahasan');

        $this->get('/logintpa/dashboard')->assertOk()->assertSee('66,7%');
    }

    public function test_tkp_uses_option_scores(): void
    {
        $user = $this->tpaUser();
        $this->mockGenerator(tkp: true);

        $this->actingAs($user, 'tpa')->postJson('/logintpa/tryout/tkp/generate', ['difficulty' => 40, 'source' => 'ai'])->assertOk();
        $tryout = TpaTryout::firstOrFail();

        foreach ($tryout->questions as $q) {
            $this->postJson(route('tpa.tryout.answer', $tryout), [
                'question_id' => $q->id, 'answer' => $q->number <= 5 ? 'E' : 'C', 'elapsed_ms' => 5000,
            ])->assertOk();
        }
        $this->post(route('tpa.tryout.finish', $tryout));

        $tryout->refresh();
        $this->assertSame(5 * 5 + 10 * 3, $tryout->score);
        $this->assertSame(5, $tryout->correct_count);
    }

    public function test_generator_error_is_reported_as_json(): void
    {
        $user = $this->tpaUser();
        $this->mock(QuestionGenerator::class)
            ->shouldReceive('generate')
            ->andThrow(new RuntimeException('Saldo kredit API Claude habis.'));

        $this->actingAs($user, 'tpa')
            ->postJson('/logintpa/tryout/tiu/generate', ['difficulty' => 50, 'source' => 'ai'])
            ->assertStatus(502)->assertJson(['message' => 'Saldo kredit API Claude habis.']);

        $this->assertSame(0, TpaTryout::count());
    }

    public function test_other_users_tryout_is_hidden(): void
    {
        $owner = $this->tpaUser();
        $other = TpaUser::create(['username' => 'chodijah', 'password' => 'rahasia123']);
        $this->mockGenerator();

        $this->actingAs($owner, 'tpa')->postJson('/logintpa/tryout/twk/generate', ['difficulty' => 50, 'source' => 'ai']);
        $tryout = TpaTryout::firstOrFail();

        $this->actingAs($other, 'tpa')->get(route('tpa.tryout.quiz', $tryout))->assertNotFound();
    }

    public function test_bank_file_is_valid_and_imported(): void
    {
        foreach (glob(database_path('data/tpa/*.json')) as $file) {
            $this->assertGreaterThan(0, app(BankImporter::class)->import($file), basename($file));
        }

        $this->assertSame(45, TpaBankQuestion::where('category', 'tiu')->count());
    }

    public function test_bank_tryout_prefers_unseen_questions_near_the_chosen_level(): void
    {
        $user = $this->tpaUser();
        $this->actingAs($user, 'tpa');

        // Tryout pertama: tanpa memanggil API, semua soal dari bank dan dari level 3±1.
        $this->postJson('/logintpa/tryout/tiu/generate', ['difficulty' => 70])->assertOk();
        $first = TpaTryout::latest('id')->firstOrFail();

        $this->assertSame('bank', $first->source);
        $firstIds = $first->questions->pluck('tpa_bank_question_id');
        $this->assertCount(15, $firstIds->unique());
        $this->assertTrue(
            TpaBankQuestion::whereIn('id', $firstIds)->pluck('level')->every(fn ($l) => abs($l - 3) <= 1)
        );

        // Kunci jawaban tidak dikirim ke halaman kuis.
        $this->get(route('tpa.tryout.quiz', $first))->assertOk()->assertDontSee('"correct_key"', false);

        $this->post(route('tpa.tryout.finish', $first));

        // Tryout kedua di level sama: tidak mengulang soal selama stok baru masih ada.
        $this->postJson('/logintpa/tryout/tiu/generate', ['difficulty' => 70])->assertOk();
        $secondIds = TpaTryout::latest('id')->firstOrFail()->questions->pluck('tpa_bank_question_id');

        $unseenNear = TpaBankQuestion::where('category', 'tiu')->whereBetween('level', [2, 4])->count() - 15;
        $this->assertSame(min(15, $unseenNear), $secondIds->diff($firstIds)->count());

        $this->get(route('tpa.tryout.start', 'tiu'))->assertOk()->assertSee('Mulai tryout');
    }

    public function test_bank_tryout_reports_empty_bank(): void
    {
        $this->actingAs($this->tpaUser(), 'tpa')
            ->postJson('/logintpa/tryout/tkp/generate', ['difficulty' => 50])
            ->assertStatus(422)->assertJsonFragment(['message' => 'Bank soal TKP belum cukup (baru 0 soal, butuh 15). Minta tambahan soal lewat Claude Code, atau pakai "Buat soal baru dengan AI".']);
    }
}
