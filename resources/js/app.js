import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import { animate, stagger } from 'animejs';
import Lenis from 'lenis';

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

/**
 * Animasi masuk saat elemen ber-class `.reveal` menyentuh viewport.
 *
 * Dipakai luas di welcome.blade.php untuk memberi kesan halaman "hidup" saat
 * di-scroll. Memakai IntersectionObserver (bukan listener `scroll`) supaya
 * tidak memicu reflow terus-menerus. Setiap elemen boleh membawa child
 * `[data-bar-fill]` (mis. bar bobot kriteria) yang lebar akhirnya diisi lewat
 * atribut `data-bar-fill`, dianimasikan begitu elemen induk terlihat.
 */
function initScrollReveal() {
    const items = document.querySelectorAll('.reveal');
    if (!items.length) return;

    const reveal = (el) => {
        el.classList.add('reveal-visible');
        const bar = el.querySelector('[data-bar-fill]');
        if (bar) {
            requestAnimationFrame(() => {
                bar.style.width = `${bar.dataset.barFill}%`;
            });
        }
    };

    if (!('IntersectionObserver' in window)) {
        items.forEach(reveal);
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            reveal(entry.target);
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });

    items.forEach((el) => observer.observe(el));
}

/**
 * Trek horizontal yang bisa digeser (mis. kartu kriteria & program studi).
 *
 * Markup: trek diberi `[data-carousel]` dan sebuah `id`. Tombol panah di
 * mana pun pada halaman menautkan diri lewat `data-carousel-prev="<id
 * trek>"` / `data-carousel-next="<id trek>"` — dibuat berbasis id (bukan
 * kedekatan DOM) supaya beberapa set tombol (mis. versi mobile & desktop)
 * bisa menunjuk ke trek yang sama tanpa perlu bersarang di elemen pembungkus
 * yang sama. Mendukung drag-to-scroll dengan mouse (bukan sentuhan, supaya
 * scroll native di layar sentuh tidak terganggu) dan menonaktifkan tombol
 * panah otomatis saat mencapai ujung trek.
 */
function initCarousels() {
    document.querySelectorAll('[data-carousel]').forEach((track) => {
        if (!track.id) return;

        const prevBtns = document.querySelectorAll(`[data-carousel-prev="${track.id}"]`);
        const nextBtns = document.querySelectorAll(`[data-carousel-next="${track.id}"]`);

        const step = () => Math.min(track.clientWidth * 0.85, 420);

        const updateEdges = () => {
            const atStart = track.scrollLeft <= 4;
            const atEnd = track.scrollLeft >= track.scrollWidth - track.clientWidth - 4;
            prevBtns.forEach((btn) => { btn.disabled = atStart; });
            nextBtns.forEach((btn) => { btn.disabled = atEnd; });
        };

        prevBtns.forEach((btn) => btn.addEventListener('click', () => {
            track.scrollBy({ left: -step(), behavior: 'smooth' });
        }));
        nextBtns.forEach((btn) => btn.addEventListener('click', () => {
            track.scrollBy({ left: step(), behavior: 'smooth' });
        }));

        track.addEventListener('scroll', updateEdges, { passive: true });
        window.addEventListener('resize', updateEdges);
        updateEdges();

        // Drag-to-scroll dengan mouse saja — sentuhan dibiarkan memakai
        // scroll-snap bawaan browser agar terasa native di HP/tablet.
        let dragging = false;
        let startX = 0;
        let startScroll = 0;

        track.addEventListener('pointerdown', (event) => {
            if (event.pointerType !== 'mouse') return;
            dragging = true;
            startX = event.pageX;
            startScroll = track.scrollLeft;
            track.classList.add('cursor-grabbing');
            track.setPointerCapture(event.pointerId);
        });

        track.addEventListener('pointermove', (event) => {
            if (!dragging) return;
            track.scrollLeft = startScroll - (event.pageX - startX);
        });

        const stopDragging = () => {
            dragging = false;
            track.classList.remove('cursor-grabbing');
        };

        track.addEventListener('pointerup', stopDragging);
        track.addEventListener('pointercancel', stopDragging);
        track.addEventListener('pointerleave', stopDragging);
    });
}

/**
 * Efek "whoosh" pada dek RIASEC (mosaik blok yang melekat di tepi kanan
 * layar & panel deskripsinya di kiri).
 *
 * Dua bagian:
 *  1. `window.riasecReveal(code)` — dipanggil dari x-data di welcome.blade.php
 *     tiap kali sebuah blok diketuk. Panel yang baru tampil melesat dari
 *     kanan (arah singkatan R·I·A·S·E·C) menuju kiri dengan pantulan kecil
 *     (`outBack`) lewat anime.js — `code` bernilai null berarti kembali ke
 *     panel placeholder.
 *  2. Blok mosaik sendiri melesat masuk dari tepi kanan layar dengan jeda
 *     beruntun begitu deck-nya pertama kali terlihat (lihat `.riasec-card`
 *     di app.css), dipicu sekali lewat IntersectionObserver.
 */
function initRiasecReveal() {
    const deck = document.querySelector('[data-riasec-deck]');
    if (!deck) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.riasecReveal = (code) => {
        const panel = document.querySelector(`[data-riasec-panel="${code ?? 'default'}"]`);
        if (!panel || reduceMotion) return;

        animate(panel, {
            translateX: [48, 0],
            opacity: [0, 1],
            duration: 700,
            ease: 'outBack(3)',
        });
    };

    const cards = deck.querySelectorAll('.riasec-card');

    const play = () => {
        if (reduceMotion) {
            cards.forEach((card) => card.classList.add('riasec-in'));
            return;
        }

        cards.forEach((card, index) => {
            card.style.setProperty('--riasec-delay', `${index * 60}ms`);
            requestAnimationFrame(() => card.classList.add('riasec-in'));
        });
    };

    if (!('IntersectionObserver' in window)) {
        play();
        return;
    }

    const observer = new IntersectionObserver(([entry]) => {
        if (!entry.isIntersecting) return;
        play();
        observer.unobserve(deck);
    }, { threshold: 0.25 });

    observer.observe(deck);
}

/**
 * Menambah bayangan lembut pada navbar begitu halaman mulai di-scroll,
 * dideteksi lewat sentinel 1px di puncak halaman (bukan listener `scroll`).
 */
function initHeaderShadow() {
    const header = document.querySelector('[data-site-header]');
    const sentinel = document.getElementById('scroll-sentinel');
    if (!header || !sentinel || !('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver(([entry]) => {
        header.classList.toggle('shadow-lg', !entry.isIntersecting);
        header.classList.toggle('shadow-gray-900/5', !entry.isIntersecting);
    });

    observer.observe(sentinel);
}

/**
 * Smooth-scroll seluruh halaman lewat Lenis (menggantikan `scroll-behavior:
 * smooth` bawaan CSS, yang dilepas dari elemen <html> supaya tidak dobel-
 * smoothing dengan Lenis). Menghormati preferensi "reduced motion" — kalau
 * aktif, scroll native browser dipakai apa adanya.
 *
 * Tautan jangkar (`<a href="#id">`) diarahkan lewat `lenis.scrollTo` supaya
 * ikut mulus, bukan lompat langsung ala browser bawaan.
 */
function initSmoothScroll() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return null;

    const lenis = new Lenis({
        duration: 1.05,
        easing: (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t)),
        smoothWheel: true,
    });

    const raf = (time) => {
        lenis.raf(time);
        requestAnimationFrame(raf);
    };
    requestAnimationFrame(raf);

    document.querySelectorAll('a[href^="#"]').forEach((link) => {
        const id = link.getAttribute('href');
        if (!id || id.length < 2) return;
        link.addEventListener('click', (event) => {
            const target = document.querySelector(id);
            if (!target) return;
            event.preventDefault();
            lenis.scrollTo(target, { offset: -84 });
        });
    });

    return lenis;
}

/**
 * Reveal judul per-karakter saat elemen `[data-split-reveal]` menyentuh
 * viewport — tiap huruf melesat naik dengan jeda beruntun lewat anime.js
 * (tidak butuh GSAP SplitText berbayar; pemisahan teksnya dibuat manual di
 * sini). Elemen aslinya disalin ke `aria-label` supaya pembaca layar tetap
 * mendengar teks utuh, bukan huruf lepas satu-satu.
 */
function initSplitReveal() {
    const items = document.querySelectorAll('[data-split-reveal]');
    if (!items.length) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    items.forEach((el) => {
        const text = el.textContent;
        el.setAttribute('aria-label', text);
        el.innerHTML = '';

        const chars = [];
        const makeCharSpan = (char) => {
            const span = document.createElement('span');
            span.setAttribute('aria-hidden', 'true');
            span.style.display = 'inline-block';
            span.style.willChange = 'transform, opacity';
            if (char === ' ') span.style.whiteSpace = 'pre';
            span.textContent = char === ' ' ? ' ' : char;
            if (!reduceMotion) {
                span.style.opacity = '0';
                span.style.transform = 'translateY(0.6em)';
            }
            chars.push(span);
            return span;
        };

        // Setiap kata dibungkus `inline-block` tersendiri (bukan huruf lepas
        // langsung sebagai anak `el`) supaya baris hanya boleh patah di
        // spasi antar kata, bukan di antara sembarang huruf — beberapa
        // browser memperlakukan span `inline-block` yang bersebelahan
        // sebagai titik patah yang sah meski tanpa spasi di antaranya.
        text.split(/(\s+)/).forEach((chunk) => {
            if (!chunk.length) return;
            if (/^\s+$/.test(chunk)) {
                chunk.split('').forEach((char) => el.appendChild(makeCharSpan(char)));
                return;
            }
            const word = document.createElement('span');
            word.style.display = 'inline-block';
            chunk.split('').forEach((char) => word.appendChild(makeCharSpan(char)));
            el.appendChild(word);
        });

        if (reduceMotion) return;

        const play = () => {
            animate(chars, {
                opacity: [0, 1],
                translateY: ['0.6em', '0em'],
                delay: stagger(18),
                duration: 700,
                ease: 'outExpo',
            });
        };

        if (!('IntersectionObserver' in window)) {
            play();
            return;
        }

        const observer = new IntersectionObserver(([entry]) => {
            if (!entry.isIntersecting) return;
            play();
            observer.unobserve(el);
        }, { threshold: 0.4 });

        observer.observe(el);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    renderRiasecCharts();
    initScrollReveal();
    initCarousels();
    initHeaderShadow();
    initRiasecReveal();
    initSmoothScroll();
    initSplitReveal();
});