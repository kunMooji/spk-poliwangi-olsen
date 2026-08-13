@use('App\Support\Riasec')

{{--
    Lembar hasil siap cetak.

    Sengaja berdiri sendiri tanpa layout aplikasi dan tanpa Tailwind: gaya cetak
    perlu kendali penuh atas ukuran kertas, pemenggalan halaman, dan warna yang
    ikut tercetak. Peramban yang mencetaknya menjadi PDF, sehingga tidak ada
    pustaka PDF tambahan yang perlu dipasang.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Tes {{ $assessment->code }} &mdash; {{ $assessment->full_name }}</title>

    <style>
        @page {
            size: A4;
            margin: 14mm 14mm 16mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            line-height: 1.5;
            color: #1f2937;
            background: #f3f4f6;
        }

        .sheet {
            max-width: 210mm;
            margin: 0 auto;
            padding: 14mm;
            background: #fff;
        }

        /* Batang berwarna wajib ikut tercetak, bukan hilang jadi putih. */
        .bar-track, .bar-fill, .badge, thead th, .kop {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .kop {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .kop h1 { margin: 0; font-size: 15pt; color: #312e81; }
        .kop p { margin: 2px 0 0; font-size: 9pt; color: #6b7280; }

        .kode {
            font-family: "Consolas", "Courier New", monospace;
            font-size: 9pt;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 4px 8px;
            white-space: nowrap;
        }

        h2 {
            font-size: 11pt;
            margin: 16px 0 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #e5e7eb;
            color: #312e81;
        }

        table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        th, td { padding: 5px 7px; text-align: left; vertical-align: top; }
        thead th { background: #eef2ff; color: #3730a3; border-bottom: 1px solid #c7d2fe; }
        tbody tr + tr td { border-top: 1px solid #f3f4f6; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }

        .biodata td { padding: 3px 7px; }
        .biodata td:first-child { color: #6b7280; width: 32%; }

        .highlight {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 6px;
            padding: 10px 12px;
            margin: 10px 0;
        }
        .highlight .label { font-size: 8.5pt; text-transform: uppercase; letter-spacing: .04em; color: #4f46e5; }
        .highlight .value { font-size: 14pt; font-weight: 700; color: #312e81; margin-top: 2px; }

        .note {
            border-left: 3px solid #f59e0b;
            background: #fffbeb;
            padding: 8px 10px;
            margin: 10px 0;
            font-size: 9.5pt;
        }
        .note.ok { border-left-color: #10b981; background: #ecfdf5; }

        .bar-track {
            height: 7px;
            background: #f3f4f6;
            border-radius: 99px;
            overflow: hidden;
            margin-top: 3px;
        }
        .bar-fill { height: 100%; border-radius: 99px; }

        .dim-row { margin-bottom: 7px; }
        .dim-head { display: flex; justify-content: space-between; font-size: 9.5pt; }

        .badge {
            display: inline-block;
            border-radius: 99px;
            padding: 1px 7px;
            font-size: 8pt;
            font-weight: 700;
            background: #4f46e5;
            color: #fff;
        }
        .badge.muted { background: #e5e7eb; color: #374151; }

        .grid2 { display: flex; gap: 16px; }
        .grid2 > * { flex: 1; }

        .ttd {
            margin-top: 22px;
            display: flex;
            justify-content: flex-end;
            text-align: center;
            font-size: 9.5pt;
        }
        .ttd .kolom { width: 62mm; }
        .ttd .ruang { height: 20mm; }

        .kaki {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 8.5pt;
            color: #6b7280;
        }

        .aksi {
            max-width: 210mm;
            margin: 16px auto 0;
            padding: 0 14mm;
            display: flex;
            gap: 10px;
        }
        .aksi button, .aksi a {
            font: inherit;
            font-size: 10pt;
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
            text-decoration: none;
            cursor: pointer;
        }
        .aksi button { background: #4f46e5; border-color: #4f46e5; color: #fff; }

        /* Jangan penggal blok di tengah-tengah saat berpindah halaman. */
        section, tr, .dim-row { break-inside: avoid; }

        @media print {
            body { background: #fff; }
            .sheet { padding: 0; max-width: none; }
            .aksi { display: none; }
        }
    </style>
</head>
<body>
    <div class="aksi">
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ route('assessments.result', $assessment) }}">Kembali ke Hasil</a>
    </div>

    <div class="sheet">
        <div class="kop">
            <div>
                <h1>Hasil Rekomendasi Program Studi</h1>
                <p>
                    Politeknik Negeri Banyuwangi &mdash; Sistem Pendukung Keputusan
                    (RIASEC &amp; CoCoSo)
                    @if ($assessment->period)
                        &middot; {{ $assessment->period->name }} {{ $assessment->period->academic_year }}
                    @endif
                </p>
            </div>
            <div class="kode">{{ $assessment->code }}</div>
        </div>

        <section>
            <h2>Data Calon Mahasiswa</h2>
            <div class="grid2">
                <table class="biodata">
                    <tr><td>Nama Lengkap</td><td><strong>{{ $assessment->full_name }}</strong></td></tr>
                    <tr><td>Jenis Kelamin</td><td>{{ ['L' => 'Laki-laki', 'P' => 'Perempuan'][$assessment->gender] ?? '-' }}</td></tr>
                    <tr><td>Asal Sekolah</td><td>{{ $assessment->school_name ?? '-' }}</td></tr>
                </table>
                <table class="biodata">
                    <tr><td>Jurusan</td><td>{{ $assessment->school_major ?? '-' }}</td></tr>
                    <tr><td>Tahun Lulus</td><td>{{ $assessment->graduation_year ?? '-' }}</td></tr>
                    <tr><td>Tanggal Tes</td><td>{{ $assessment->completed_at?->translatedFormat('d F Y, H:i') ?? '-' }}</td></tr>
                </table>
            </div>
        </section>

        <section>
            <h2>Rekomendasi Utama</h2>
            <div class="highlight">
                <div class="label">Program Studi yang Disarankan</div>
                <div class="value">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</div>
            </div>

            @if ($assessment->matches_preference)
                <p class="note ok">
                    Pilihan pertama Anda, <strong>{{ $assessment->primaryProgram?->full_name }}</strong>,
                    sekaligus menempati peringkat teratas dengan nilai
                    <strong>{{ number_format($primaryResult?->k_normal ?? 0, 2) }}</strong>
                    dari {{ $assessment->results->count() }} program studi. Peringkat ini murni hasil
                    perhitungan seluruh kriteria, bukan karena urutan pilihan Anda.
                </p>
            @else
                <p class="note">
                    Pilihan pertama Anda, <strong>{{ $assessment->primaryProgram?->full_name ?? '-' }}</strong>,
                    berada di peringkat <strong>{{ $primaryResult?->ranking ?? '-' }}</strong>
                    dari {{ $assessment->results->count() }} program studi dengan nilai
                    <strong>{{ number_format($primaryResult?->k_normal ?? 0, 2) }}</strong>.
                    Nilai tertinggi diraih <strong>{{ $assessment->recommendedProgram?->full_name }}</strong>
                    ({{ number_format($recommendedResult?->k_normal ?? 0, 2) }}).
                </p>
            @endif
        </section>

        <section>
            <h2>Profil Kepribadian RIASEC</h2>
            <p style="margin:0 0 8px; font-size:9.5pt;">
                Kode Holland <strong>{{ $assessment->holland_code }}</strong> &mdash;
                tipe dominan <strong>{{ Riasec::name($assessment->dominant_type) }}</strong>.
                {{ Riasec::description($assessment->dominant_type) }}
            </p>

            @foreach (Riasec::DIMENSIONS as $dimension)
                <div class="dim-row">
                    <div class="dim-head">
                        <span>{{ Riasec::label($dimension) }}</span>
                        <span class="num">{{ number_format($percentages[$dimension], 2) }}%</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: {{ $percentages[$dimension] }}%; background: {{ Riasec::color($dimension) }};"></div>
                    </div>
                </div>
            @endforeach
        </section>

        @if ($contributions !== [])
            <section>
                <h2>Alasan Rekomendasi &mdash; Sumbangan Tiap Kriteria</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Kriteria</th>
                            <th class="num">Bobot</th>
                            <th class="num">Nilai Ternormalisasi</th>
                            <th class="num">Sumbangan</th>
                            <th>Tingkat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contributions as $row)
                            <tr>
                                <td>{{ $row['name'] }} <span style="color:#9ca3af;">({{ $row['code'] }})</span></td>
                                <td class="num">{{ number_format($row['weight'], 4) }}</td>
                                <td class="num">{{ number_format($row['normalized'], 4) }}</td>
                                <td class="num">{{ $row['share'] }}%</td>
                                <td>{{ ucfirst($row['level']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        @if ($comparison !== [])
            <section>
                <h2>Perbandingan dengan Pilihan Pertama</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Kriteria</th>
                            <th class="num">{{ $assessment->primaryProgram?->code }}</th>
                            <th class="num">{{ $assessment->recommendedProgram?->code }}</th>
                            <th class="num">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparison as $row)
                            <tr>
                                <td>{{ $row['name'] }} <span style="color:#9ca3af;">({{ $row['code'] }})</span></td>
                                <td class="num">{{ number_format($row['subject'], 4) }}</td>
                                <td class="num">{{ number_format($row['against'], 4) }}</td>
                                <td class="num">{{ $row['delta'] > 0 ? '+' : '' }}{{ number_format($row['delta'], 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        <section>
            <h2>Peringkat Program Studi</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width:8%;">#</th>
                        <th>Program Studi</th>
                        <th class="num" style="width:18%;">Nilai (K ternorm.)</th>
                        <th style="width:22%;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assessment->results as $result)
                        <tr>
                            <td><strong>{{ $result->ranking }}</strong></td>
                            <td>{{ $result->studyProgram->full_name }}</td>
                            <td class="num">{{ number_format($result->k_normal, 2) }}</td>
                            <td>
                                @if ($result->study_program_id === $assessment->recommended_program_id)
                                    <span class="badge">Rekomendasi</span>
                                @endif
                                @if ($result->study_program_id === $assessment->primary_program_id)
                                    <span class="badge muted">Pilihan 1</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <p class="note">
            <strong>Catatan.</strong> Hasil ini bersifat <strong>saran, bukan keputusan</strong>. Angka di atas
            disusun dari nilai rapor, profil minat bakat, urutan pilihan Anda sendiri, dan data serapan kerja
            alumni. Keputusan akhir dalam memilih program studi sepenuhnya berada di tangan Anda.
        </p>

        <div class="ttd">
            <div class="kolom">
                <div>Banyuwangi, {{ now()->translatedFormat('d F Y') }}</div>
                <div>Calon Mahasiswa,</div>
                <div class="ruang"></div>
                <div style="border-top:1px solid #9ca3af; padding-top:3px;">{{ $assessment->full_name }}</div>
            </div>
        </div>

        <div class="kaki">
            Dicetak dari Sistem Pendukung Keputusan Rekomendasi Program Studi pada
            {{ now()->translatedFormat('d F Y, H:i') }} &middot; Kode verifikasi: {{ $assessment->code }} &middot;
            Parameter: &lambda; = {{ number_format($assessment->lambda_used, 3) }}.
        </div>
    </div>
</body>
</html>
