import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

/**
 * Bar chart profil RIASEC.
 *
 * Elemen <canvas> cukup membawa atribut data-riasec-chart berisi JSON
 * { labels: [], values: [], colors: [] } — grafik dibuat otomatis saat halaman siap.
 */
function renderRiasecCharts() {
    document.querySelectorAll('canvas[data-riasec-chart]').forEach((canvas) => {
        const { labels, values, colors } = JSON.parse(canvas.dataset.riasecChart);

        // Warna teks mengikuti tema terang/gelap yang sedang aktif.
        const dark = document.documentElement.classList.contains('dark');
        const tick = dark ? '#cbd5e1' : '#475569';
        const grid = dark ? 'rgba(148,163,184,0.2)' : 'rgba(100,116,139,0.15)';

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Persentase',
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 6,
                    maxBarThickness: 64,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.parsed.y.toFixed(2)} %`,
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { color: tick, callback: (value) => `${value}%` },
                        grid: { color: grid },
                    },
                    x: {
                        ticks: { color: tick },
                        grid: { display: false },
                    },
                },
            },
        });
    });
}

document.addEventListener('DOMContentLoaded', renderRiasecCharts);
