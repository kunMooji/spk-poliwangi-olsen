/**
 * Area latihan SKD CPNS (/logintpa).
 *
 * - tpaStart: memilih tingkat kesulitan lalu meminta server menyusun soal (AJAX,
 *   bisa 1-3 menit) dengan layar tunggu dan tombol coba lagi saat gagal.
 * - tpaQuiz : satu soal per layar, timer per soal, hint yang bisa dibuka, dan
 *   penyimpanan jawaban + waktu ke server setiap berpindah soal.
 * - renderTpaCharts: grafik akurasi & kecepatan 30 hari di dasbor.
 */

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf(),
        },
        body: JSON.stringify(body),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(data.message || `Terjadi kesalahan (${response.status}).`);
    }
    return data;
}

const LEVEL_LABELS = [
    [30, 'Mudah', 'Soal pendek, satu langkah penalaran, pilihan jawaban mudah dibedakan.'],
    [60, 'Sedang', 'Setara SKD sebenarnya: kasus singkat, 2-3 langkah penalaran.'],
    [80, 'Sulit', 'Stem panjang, analisis bertahap, pilihan jawaban sangat mirip.'],
    [100, 'Sangat sulit', 'Kasus kompleks multi-konsep, jebakan halus, hitungan berat.'],
];

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.data('tpaStart', (url, stock = {}) => ({
        difficulty: 50,
        loading: false,
        error: '',
        elapsed: 0,
        timer: null,
        source: 'bank',

        get level() {
            return LEVEL_LABELS.find(([max]) => this.difficulty <= max);
        },

        /** Stok bank untuk level slider (indeks level 1-4 sama dengan LEVEL_LABELS). */
        get levelStock() {
            const level = LEVEL_LABELS.findIndex(([max]) => this.difficulty <= max) + 1;
            return stock[level] ?? { total: 0, unseen: 0 };
        },

        async generate(source = 'bank') {
            this.source = source;
            this.loading = true;
            this.error = '';
            this.elapsed = 0;
            this.timer = setInterval(() => this.elapsed++, 1000);
            try {
                const data = await postJson(url, { difficulty: Number(this.difficulty), source });
                window.location.href = data.redirect;
            } catch (e) {
                this.error = e.message === 'Failed to fetch'
                    ? 'Koneksi terputus atau server terlalu lama merespons. Coba lagi.'
                    : e.message;
                this.loading = false;
            } finally {
                clearInterval(this.timer);
            }
        },
    }));

    Alpine.data('tpaQuiz', (config) => ({
        questions: config.questions,
        index: Math.max(0, config.questions.findIndex((q) => !q.answer)),
        showHint: false,
        startedAt: performance.now(),
        now: performance.now(),
        saving: false,
        finishing: false,
        error: '',
        ticker: null,

        init() {
            if (this.index < 0) this.index = 0;
            this.ticker = setInterval(() => { this.now = performance.now(); }, 500);

            // Timer berhenti saat tab disembunyikan agar waktu tetap jujur.
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.flush();
                } else {
                    this.startedAt = performance.now();
                }
            });
            window.addEventListener('pagehide', () => this.flush(true));
        },

        get current() {
            return this.questions[this.index];
        },

        get answeredCount() {
            return this.questions.filter((q) => q.answer).length;
        },

        get currentSeconds() {
            const running = document.hidden ? 0 : this.now - this.startedAt;
            return Math.floor((this.current.time_ms + Math.max(0, running)) / 1000);
        },

        formatTime(sec) {
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return `${m}:${String(s).padStart(2, '0')}`;
        },

        /** Kirim waktu yang berjalan (dan jawaban) soal aktif ke server. */
        flush(beacon = false) {
            const q = this.current;
            const elapsed = Math.max(0, Math.round(performance.now() - this.startedAt));
            this.startedAt = performance.now();
            if (elapsed < 200 && !q._dirty) return Promise.resolve();

            q.time_ms += elapsed;
            const payload = {
                question_id: q.id,
                answer: q.answer,
                elapsed_ms: Math.min(elapsed, 15 * 60 * 1000),
                hint_used: q.hint_used,
            };
            q._dirty = false;

            if (beacon && navigator.sendBeacon) {
                const form = new FormData();
                Object.entries({ ...payload, _token: csrf() }).forEach(([k, v]) => {
                    if (v !== null) form.append(k, typeof v === 'boolean' ? (v ? 1 : 0) : v);
                });
                navigator.sendBeacon(config.answerUrl, form);
                return Promise.resolve();
            }

            this.saving = true;
            return postJson(config.answerUrl, payload)
                .then(() => { this.error = ''; })
                .catch((e) => { this.error = `Jawaban belum tersimpan: ${e.message}`; q._dirty = true; })
                .finally(() => { this.saving = false; });
        },

        choose(key) {
            this.current.answer = key;
            this.current._dirty = true;
            this.flush();
        },

        toggleHint() {
            this.showHint = !this.showHint;
            if (this.showHint && !this.current.hint_used) {
                this.current.hint_used = true;
                this.current._dirty = true;
            }
        },

        go(i) {
            if (i < 0 || i >= this.questions.length || i === this.index) return;
            this.flush();
            this.index = i;
            this.showHint = false;
            this.startedAt = performance.now();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async finish() {
            const empty = this.questions.length - this.answeredCount;
            const msg = empty > 0
                ? `Masih ada ${empty} soal kosong. Selesaikan tryout sekarang?`
                : 'Selesaikan tryout dan lihat pembahasan?';
            if (!confirm(msg)) return;

            this.finishing = true;
            await this.flush();
            clearInterval(this.ticker);
            this.$refs.finishForm.submit();
        },
    }));
});

const CATEGORY_COLORS = { twk: '#2390aa', tiu: '#2e5866', tkp: '#d29a3f' };

export function renderTpaCharts() {
    document.querySelectorAll('canvas[data-tpa-chart]').forEach((canvas) => {
        const { metric, labels, series, names } = JSON.parse(canvas.dataset.tpaChart);
        const dark = document.documentElement.classList.contains('dark');
        const tick = dark ? '#cbd5e1' : '#475569';
        const grid = dark ? 'rgba(148,163,184,0.2)' : 'rgba(100,116,139,0.15)';
        const unit = metric === 'accuracy' ? '%' : ' dtk';

        new window.Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: Object.keys(series).map((cat) => ({
                    label: names[cat],
                    data: series[cat][metric],
                    borderColor: CATEGORY_COLORS[cat],
                    backgroundColor: `${CATEGORY_COLORS[cat]}33`,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                    spanGaps: true,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: tick, usePointStyle: true, boxWidth: 8, font: { size: 11 } },
                    },
                    tooltip: {
                        callbacks: {
                            label: (c) => (c.parsed.y === null ? null : `${c.dataset.label}: ${c.parsed.y}${unit}`),
                        },
                    },
                },
                scales: {
                    x: { ticks: { color: tick, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        suggestedMax: metric === 'accuracy' ? 100 : undefined,
                        max: metric === 'accuracy' ? 100 : undefined,
                        ticks: { color: tick, callback: (v) => `${v}${unit}` },
                        grid: { color: grid },
                    },
                },
            },
        });
    });
}
