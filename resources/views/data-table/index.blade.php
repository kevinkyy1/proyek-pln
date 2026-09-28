@extends('layouts.app')

@section('title', 'Data Table')

@section('content')
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-lg font-semibold sm:text-xl">Data Table</h1>
            <p class="text-xs text-gray-500 sm:text-sm">
                Kolom mengikuti sheet MASTER: IDPEL, NAMA, TARIF, DAYA, {{ $periodeList[0] }}–{{ end($periodeList) }},
                RATA2 {{ implode(' / ', $tahunList) }}
                &middot; total pelanggan {{ number_format($pelanggan->total(), 0, ',', '.') }}
                &middot; import {{ number_format($chunkSize, 0, ',', '.') }} baris per chunk
            </p>
            <p class="mt-1 text-xs text-gray-400">
                Geser tabel ke kanan/kiri untuk melihat kolom bulan; kolom No, IDPEL, dan NAMA tetap terlihat.
            </p>
        </div>

        <button type="button" id="btnImportExcel"
                class="w-full rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 sm:w-auto">
            Import Excel
        </button>
    </div>

    {{-- Filter & pagination sisi server: hanya baris halaman aktif yang diambil dari database --}}
    <form method="GET" action="{{ route('home') }}" class="mb-3 flex flex-wrap items-end gap-2 rounded border border-gray-200 bg-white p-3">
        <div class="min-w-0 flex-1 sm:flex-none">
            <label for="search" class="mb-1 block text-xs font-medium text-gray-600">Cari IDPEL / NAMA / TARIF</label>
            <input type="search" id="search" name="search" value="{{ $search }}" placeholder="mis. 1712030 atau R1MT"
                   class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm sm:w-64">
        </div>

        <div>
            <label for="per_page" class="mb-1 block text-xs font-medium text-gray-600">Baris / halaman</label>
            <select id="per_page" name="per_page" class="rounded border border-gray-300 px-2 py-1.5 text-sm">
                @foreach ($perPageOptions as $opsi)
                    <option value="{{ $opsi }}" @selected($perPage === $opsi)>{{ $opsi }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="rounded bg-slate-800 px-4 py-1.5 text-sm font-medium text-white hover:bg-slate-700">
            Cari
        </button>

        @if ($search !== '')
            <a href="{{ route('home', ['per_page' => $perPage]) }}"
               class="rounded border border-gray-300 px-4 py-1.5 text-sm hover:bg-gray-50">
                Reset
            </a>
        @endif

        <p class="ml-auto self-center text-xs text-gray-500">
            @if ($pelanggan->total() > 0)
                Menampilkan {{ number_format($pelanggan->firstItem(), 0, ',', '.') }}–{{ number_format($pelanggan->lastItem(), 0, ',', '.') }}
                dari {{ number_format($pelanggan->total(), 0, ',', '.') }} pelanggan
                @if ($search !== '')
                    (filter: &quot;{{ $search }}&quot;)
                @endif
            @else
                Tidak ada data yang cocok{{ $search !== '' ? ' dengan filter "'.$search.'"' : '' }}
            @endif
        </p>
    </form>

    @if (session('status'))
        <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($batches->isNotEmpty())
        <div class="mb-6 rounded border border-gray-200 bg-white" id="riwayatImport"
             data-url="{{ route('import.status') }}"
             data-running="{{ $batches->contains(fn ($batch) => $batch->isRunning()) ? '1' : '0' }}">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <span class="text-sm font-medium text-gray-700">Riwayat import (5 terakhir)</span>
                <span class="hidden text-xs text-amber-700" id="riwayatImportLoading">
                    memantau progres&hellip;
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-2 font-medium">File</th>
                            <th class="px-4 py-2 font-medium">Status</th>
                            <th class="px-4 py-2 font-medium">Progres</th>
                            <th class="hidden px-4 py-2 font-medium md:table-cell">Chunk</th>
                            <th class="hidden px-4 py-2 font-medium md:table-cell">Mulai</th>
                            <th class="hidden px-4 py-2 font-medium md:table-cell">Selesai</th>
                        </tr>
                    </thead>
                    <tbody id="riwayatImportBody">
                        @include('data-table.partials.riwayat-import', ['batches' => $batches])
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="tabel-data rounded border border-gray-200 bg-white">
        <table class="min-w-full whitespace-nowrap text-[11px] sm:text-xs">
            <thead class="text-gray-600">
                <tr>
                    <th rowspan="2" class="kol-no px-2 py-2 text-left font-medium sm:px-3">No</th>
                    <th rowspan="2" class="kol-idpel px-2 py-2 text-left font-medium sm:px-3">IDPEL</th>
                    <th rowspan="2" class="kol-nama px-2 py-2 text-left font-medium sm:px-3">NAMA</th>
                    <th rowspan="2" class="px-2 py-2 text-left font-medium sm:px-3">TARIF</th>
                    <th rowspan="2" class="px-2 py-2 text-right font-medium sm:px-3">DAYA</th>

                    @foreach ($periodeList as $periode)
                        <th rowspan="2" class="px-2 py-2 text-right font-medium sm:px-3">{{ $periode }}</th>
                    @endforeach

                    @foreach ($tahunList as $tahun)
                        <th colspan="3" class="batas-tahun px-2 py-2 text-center font-medium sm:px-3">RATA2 {{ $tahun }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($tahunList as $tahun)
                        <th class="batas-tahun px-2 py-2 text-right font-medium sm:px-3">PEM</th>
                        <th class="px-2 py-2 text-right font-medium sm:px-3">JN</th>
                        <th class="px-2 py-2 text-center font-medium sm:px-3">KET</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-t border-gray-100">
                        <td class="kol-no px-2 py-2 sm:px-3">{{ $pelanggan->firstItem() + $loop->index }}</td>
                        <td class="kol-idpel px-2 py-2 font-mono sm:px-3">
                            <button type="button"
                                    class="link-idpel rounded font-mono text-blue-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                                    data-idpel="{{ $row['idpel'] }}"
                                    title="Klik untuk melihat grafik pemakaian">{{ $row['idpel'] }}</button>
                        </td>
                        <td class="kol-nama px-2 py-2 font-mono sm:px-3">
                            <span title="{{ $row['nama'] }}">{{ $row['nama'] }}</span>
                        </td>
                        <td class="px-2 py-2 sm:px-3">{{ $row['tarif'] }}</td>
                        <td class="px-2 py-2 text-right sm:px-3">{{ \App\Support\Angka::daya($row['daya']) }}</td>

                        @foreach ($periodeList as $periode)
                            <td class="px-2 py-2 text-right sm:px-3">{{ \App\Support\Angka::kwh($row['bulanan'][$periode] ?? null) }}</td>
                        @endforeach

                        @foreach ($tahunList as $tahun)
                            <td class="batas-tahun px-2 py-2 text-right sm:px-3">{{ \App\Support\Angka::rata2($row['rata2'][$tahun]['pem'] ?? null) }}</td>
                            <td class="px-2 py-2 text-right sm:px-3">{{ \App\Support\Angka::rata2($row['rata2'][$tahun]['jn'] ?? null) }}</td>
                            <td @class([
                                'px-2 py-2 text-center sm:px-3',
                                'text-gray-400' => ($row['rata2'][$tahun]['ket'] ?? null) === null,
                            ])>{{ $row['rata2'][$tahun]['ket'] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr class="border-t border-gray-100">
                        <td colspan="{{ 5 + count($periodeList) + count($tahunList) * 3 }}"
                            class="px-4 py-6 text-center text-gray-500">
                            Belum ada data.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $pelanggan->links() }}
    </div>

    {{-- Modal import --}}
    <div id="modalImportExcel" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-3 sm:p-4">
        <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded bg-white p-4 sm:p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Import Excel</h2>
                <button type="button" id="btnCloseImportExcel" class="text-2xl leading-none text-gray-400 hover:text-gray-600"
                        aria-label="Tutup">&times;</button>
            </div>

            <form id="formImportExcel" method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data"
                  data-chunk-url="{{ route('import.chunk') }}"
                  data-chunk-size="{{ (int) config('import.upload_chunk_size') }}">
                @csrf

                <label for="fileImportExcel" class="mb-1 block text-sm font-medium text-gray-700">File Excel (sheet MASTER)</label>
                <input type="file" name="file" id="fileImportExcel" accept=".xlsx,.xls" required
                       class="block w-full rounded border border-gray-300 p-2 text-sm">

                @error('file')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror

                <p class="mt-2 text-xs text-gray-500">
                    File besar dikirim bertahap ({{ number_format(config('import.upload_chunk_size') / 1048576, 1, ',', '.') }} MB per bagian),
                    lalu dibaca {{ number_format($chunkSize, 0, ',', '.') }} baris per job. Data lama akan diganti.
                </p>

                <div id="importProgress" class="mt-3 hidden">
                    <div class="mb-1 flex items-center justify-between text-xs text-gray-600">
                        <span class="flex items-center gap-1.5">
                            <span id="importSpinner" class="inline-block h-3 w-3 animate-spin rounded-full border-2 border-gray-300 border-t-blue-600"></span>
                            <span id="importProgressText">Mengunggah&hellip;</span>
                        </span>
                        <span id="importProgressPersen">0%</span>
                    </div>
                    <div class="h-2 w-full rounded bg-gray-100">
                        <div id="importProgressBar" class="h-2 rounded bg-blue-600" style="width: 0%"></div>
                    </div>
                    <div id="importProgressDetail" class="mt-1 text-[11px] text-gray-500"></div>
                </div>

                <p id="importError" class="mt-2 hidden text-xs text-red-600"></p>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" id="btnCancelImportExcel"
                            class="rounded border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitImportExcel"
                            class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal grafik pemakaian per IDPEL --}}
    <div id="modalGrafikPemakaian"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-3 sm:p-4"
         data-url-template="{{ route('pelanggan.grafik', ['idpel' => '__IDPEL__']) }}">
        <div class="flex max-h-[94vh] w-full max-w-[95vw] flex-col rounded bg-white p-4 sm:p-5">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold sm:text-lg">Tren Pemakaian</h2>
                    <p id="grafikIdentitas" class="mt-0.5 truncate text-xs text-gray-600 sm:text-sm"></p>
                </div>
                <button type="button" id="btnCloseGrafik"
                        class="text-2xl leading-none text-gray-400 hover:text-gray-600" aria-label="Tutup">&times;</button>
            </div>

            {{-- Tabel nilai per bulan: diisi JavaScript dari data yang sama dengan grafik --}}
            <div id="tabelTren" class="mb-3 hidden max-h-[38vh] overflow-auto rounded border border-gray-200"></div>

            <div class="relative flex-1">
                <div class="overflow-x-auto">
                    <div id="wadahKanvas" class="h-[300px] sm:h-[420px]">
                        <canvas id="kanvasGrafik"></canvas>
                    </div>
                </div>
                <p id="grafikStatus"
                   class="pointer-events-none absolute inset-0 hidden items-center justify-center text-sm text-gray-500"></p>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('modalImportExcel');

            // Tinggi baris header pertama dipakai untuk menempelkan baris kedua (PEM/JN/KET).
            function aturTinggiHeader() {
                var wadah = document.querySelector('.tabel-data');
                var barisPertama = wadah ? wadah.querySelector('thead tr:first-child') : null;

                if (wadah && barisPertama) {
                    wadah.style.setProperty('--tinggi-header', barisPertama.getBoundingClientRect().height + 'px');
                }
            }

            // Kolom identitas (No, IDPEL, NAMA) baru ditempelkan setelah lebarnya diukur,
            // supaya posisinya pasti pas dan tidak ada kolom yang saling menimpa.
            function aturKolomSticky() {
                var wadah = document.querySelector('.tabel-data');
                var tabel = wadah ? wadah.querySelector('table') : null;
                var barisHeader = tabel && tabel.tHead ? tabel.tHead.rows[0] : null;

                if (!wadah || !barisHeader) {
                    return;
                }

                var kolNo = barisHeader.querySelector('.kol-no');
                var kolIdpel = barisHeader.querySelector('.kol-idpel');
                var kolNama = barisHeader.querySelector('.kol-nama');

                if (!kolNo || !kolIdpel || !kolNama) {
                    wadah.classList.remove('kolom-sticky');

                    return;
                }

                var lebarNo = kolNo.getBoundingClientRect().width;
                var lebarIdpel = kolIdpel.getBoundingClientRect().width;
                var lebarNama = kolNama.getBoundingClientRect().width;
                var total = lebarNo + lebarIdpel + lebarNama;

                // Kalau ukurannya belum valid atau kolom identitas menghabiskan lebih dari 60%
                // lebar tampilan (layar sempit), biarkan tabel menggeser tanpa kolom menempel.
                if (lebarNo <= 0 || lebarIdpel <= 0 || lebarNama <= 0 || total > wadah.clientWidth * 0.6) {
                    wadah.classList.remove('kolom-sticky');

                    return;
                }

                wadah.style.setProperty('--sticky-idpel', lebarNo + 'px');
                wadah.style.setProperty('--sticky-nama', (lebarNo + lebarIdpel) + 'px');
                wadah.classList.add('kolom-sticky');
            }

            function aturTabel() {
                aturTinggiHeader();
                aturKolomSticky();
            }

            aturTabel();
            window.addEventListener('load', aturTabel);
            window.addEventListener('resize', aturTabel);

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.getElementById('btnImportExcel').addEventListener('click', openModal);
            document.getElementById('btnCloseImportExcel').addEventListener('click', closeModal);
            document.getElementById('btnCancelImportExcel').addEventListener('click', closeModal);

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            // ---- Modal grafik pemakaian: dibuka dengan mengklik nilai IDPEL ----
            (function () {
                var modalGrafik = document.getElementById('modalGrafikPemakaian');
                var kanvas = document.getElementById('kanvasGrafik');

                if (!modalGrafik || !kanvas || typeof window.Chart === 'undefined') {
                    return;
                }

                var status = document.getElementById('grafikStatus');
                var identitas = document.getElementById('grafikIdentitas');
                var templat = modalGrafik.dataset.urlTemplate;
                var grafik = null;

                function buka() {
                    modalGrafik.classList.remove('hidden');
                    modalGrafik.classList.add('flex');
                }

                function tutup() {
                    modalGrafik.classList.add('hidden');
                    modalGrafik.classList.remove('flex');
                }

                function pesan(teks) {
                    status.textContent = teks || '';
                    status.classList.toggle('hidden', !teks);
                    status.classList.toggle('flex', !!teks);
                }

                // Semua angka di modal ditampilkan bulat (mengikuti format sel Excel), mis. 1.234
                function angka(nilai) {
                    return Math.round(nilai).toLocaleString('id-ID');
                }

                // Label sumbu X: bulan, nilai PEM, dan nilai Jam Nyala (tiga baris)
                function labelSumbuX(periode, pem, jn) {
                    var teks = String(periode);

                    return [
                        teks.slice(4, 6) + '/' + teks.slice(0, 4),
                        'PEM ' + angka(pem),
                        'JN ' + angka(jn),
                    ];
                }

                // Tabel nilai di atas grafik: baris KETERANGAN / PEM / JAM NYALA per bulan
                function isiTabelTren(label, pem, jn) {
                    var kepala = ['KETERANGAN'].concat(label.map(function (periode) {
                        return String(periode);
                    }));
                    var baris = [
                        ['PEM'].concat(pem.map(angka)),
                        ['JAM NYALA'].concat(jn.map(angka)),
                    ];
                    var rata = 'sticky left-0 z-10 bg-white text-left font-medium';
                    var html = '<table class="min-w-full whitespace-nowrap text-[11px]"><thead><tr>';

                    kepala.forEach(function (teks, i) {
                        html += '<th class="' + (i === 0 ? rata.replace('bg-white', 'bg-gray-50') : 'text-right')
                            + ' border-b border-gray-200 bg-gray-50 px-2 py-1 font-medium text-gray-600">' + teks + '</th>';
                    });

                    html += '</tr></thead><tbody>';

                    baris.forEach(function (nilai) {
                        html += '<tr>';

                        nilai.forEach(function (teks, i) {
                            html += '<td class="' + (i === 0 ? rata : 'text-right')
                                + ' border-b border-gray-100 px-2 py-1">' + teks + '</td>';
                        });

                        html += '</tr>';
                    });

                    html += '</tbody></table>';

                    var wadah = document.getElementById('tabelTren');
                    wadah.innerHTML = html;
                    wadah.classList.remove('hidden');
                }

                function gambar(data) {
                    if (grafik) {
                        grafik.destroy();
                        grafik = null;
                    }

                    pesan('');

                    // Sumbu dimulai sedikit di bawah 0 supaya garis yang bernilai 0
                    // tidak berimpit dengan sumbu X (kalau semua nilai 0, dipakai -1).
                    function batasBawah(deret) {
                        var tertinggi = deret.reduce(function (a, b) {
                            return Math.max(a, b === null || b === undefined ? 0 : b);
                        }, 0);

                        return -Math.max(1, Math.round(tertinggi * 0.08));
                    }

                    var bawahPem = batasBawah(data.pem);
                    var bawahJn = batasBawah(data.jam_nyala);

                    // Semua nilai di modal dibulatkan dulu: grafik, label sumbu X, tabel, dan tooltip.
                    var pemBulat = data.pem.map(function (nilai) {
                        return Math.round(nilai === null || nilai === undefined ? 0 : nilai);
                    });
                    var jnBulat = data.jam_nyala.map(function (nilai) {
                        return Math.round(nilai === null || nilai === undefined ? 0 : nilai);
                    });

                    isiTabelTren(data.label, pemBulat, jnBulat);

                    // Di layar lebar, kanvas diberi lebar minimum (~42 px per bulan) supaya
                    // label "bulan / PEM / JN" tidak bertumpuk; kalau layar lebih sempit,
                    // grafik bisa digeser ke samping. Di layar kecil labelnya dijarangkan.
                    var sempit = window.innerWidth < 760;
                    document.getElementById('wadahKanvas').style.minWidth =
                        sempit ? '' : (data.label.length * 42 + 90) + 'px';

                    grafik = new window.Chart(kanvas, {
                        type: 'line',
                        data: {
                            labels: data.label.map(function (periode, i) {
                                return labelSumbuX(periode, pemBulat[i], jnBulat[i]);
                            }),
                            datasets: [
                                {
                                    label: 'PEM (kWh)',
                                    data: pemBulat,
                                    yAxisID: 'y',
                                    borderColor: '#1d4ed8',
                                    backgroundColor: 'rgba(29,78,216,.12)',
                                    borderWidth: 2,
                                    tension: 0,
                                    pointRadius: 3,
                                    pointHoverRadius: 6,
                                    pointBorderWidth: 1,
                                },
                                {
                                    label: 'JAM NYALA',
                                    data: jnBulat,
                                    yAxisID: 'y1',
                                    borderColor: '#f59e0b',
                                    backgroundColor: 'rgba(245,158,11,.12)',
                                    borderWidth: 2,
                                    tension: 0,
                                    pointRadius: 3,
                                    pointHoverRadius: 6,
                                    pointBorderWidth: 1,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } },
                                tooltip: {
                                    callbacks: {
                                        label: function (item) {
                                            return item.dataset.label + ': ' + angka(item.parsed.y);
                                        },
                                    },
                                },
                            },
                            scales: {
                                y: {
                                    position: 'left',
                                    min: bawahPem,
                                    title: { display: true, text: 'PEM (kWh)' },
                                    ticks: {
                                        precision: 0,
                                        // angka negatif (jarak bawah) tidak ditampilkan
                                        callback: function (nilai) {
                                            return nilai < 0 ? '' : angka(nilai);
                                        },
                                    },
                                    grid: {
                                        color: function (ctx) {
                                            return ctx.tick && ctx.tick.value < 0 ? 'transparent' : 'rgba(17,24,39,.08)';
                                        },
                                    },
                                },
                                y1: {
                                    position: 'right',
                                    min: bawahJn,
                                    title: { display: true, text: 'Jam Nyala' },
                                    grid: { drawOnChartArea: false },
                                    ticks: {
                                        precision: 0,
                                        callback: function (nilai) {
                                            return nilai < 0 ? '' : angka(nilai);
                                        },
                                    },
                                },
                                x: {
                                    // Di layar lebar semua bulan (beserta nilai PEM/JN) ditampilkan;
                                    // di layar sempit label dijarangkan supaya tidak bertumpuk —
                                    // nilai lengkapnya tetap bisa dibaca di tabel di atas grafik.
                                    ticks: sempit
                                        ? { autoSkip: true, maxTicksLimit: 5, maxRotation: 0, font: { size: 8 } }
                                        : { autoSkip: false, maxRotation: 0, minRotation: 0, font: { size: 8 } },
                                    grid: { display: false },
                                },
                            },
                        },
                    });

                    window.grafikPemakaian = grafik; // memudahkan pemeriksaan otomatis
                }

                function bukaUntuk(idpel) {
                    identitas.textContent = 'IDPEL ' + idpel;
                    pesan('Memuat data\u2026');
                    buka();

                    var url = templat.replace('__IDPEL__', encodeURIComponent(idpel));

                    fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Data IDPEL ini tidak ditemukan (' + response.status + ').');
                            }

                            return response.json();
                        })
                        .then(function (data) {
                            identitas.textContent = 'IDPEL ' + data.idpel + ' \u00b7 ' + (data.nama || '-')
                                + ' \u00b7 ' + (data.tarif || '-')
                                + ' \u00b7 DAYA ' + (data.daya === null ? '-' : angka(data.daya));

                            gambar(data);
                        })
                        .catch(function (error) {
                            pesan(error.message);
                        });
                }

                document.addEventListener('click', function (event) {
                    var tombol = event.target.closest ? event.target.closest('.link-idpel') : null;

                    if (!tombol) {
                        return;
                    }

                    event.preventDefault();
                    bukaUntuk(tombol.dataset.idpel);
                });

                document.getElementById('btnCloseGrafik').addEventListener('click', tutup);

                modalGrafik.addEventListener('click', function (event) {
                    if (event.target === modalGrafik) {
                        tutup();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !modalGrafik.classList.contains('hidden')) {
                        tutup();
                    }
                });
            })();


            // Pantau progres import lewat fetch tiap 5 detik, hanya selama masih ada batch berjalan.
            (function () {
                var panel = document.getElementById('riwayatImport');

                if (!panel) {
                    return;
                }

                var body = document.getElementById('riwayatImportBody');
                var indikator = document.getElementById('riwayatImportLoading');
                var url = panel.dataset.url;
                var sedangJalan = panel.dataset.running === '1';
                var timer = null;

                function jadwalkan() {
                    timer = setTimeout(poll, 5000);
                }

                function hentikan() {
                    if (timer) {
                        clearTimeout(timer);
                        timer = null;
                    }

                    if (indikator) {
                        indikator.classList.add('hidden');
                    }
                }

                function poll() {
                    timer = null;

                    fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(function (response) {
                            return response.ok ? response.json() : null;
                        })
                        .then(function (data) {
                            if (!data) {
                                jadwalkan();

                                return;
                            }

                            if (body) {
                                body.innerHTML = data.html;
                            }

                            if (data.running) {
                                jadwalkan();

                                return;
                            }

                            hentikan();

                            // Import selesai: sekali saja, segarkan seluruh halaman supaya tabel ikut terisi.
                            if (sedangJalan) {
                                window.location.reload();
                            }
                        })
                        .catch(function () {
                            jadwalkan();
                        });
                }

                if (sedangJalan) {
                    if (indikator) {
                        indikator.classList.remove('hidden');
                    }

                    jadwalkan();
                }
            })();

            // Upload berpotong untuk file besar (mis. 54 MB): dikirim per bagian lewat fetch
            // supaya tidak kena batas post_max_size PHP.
            (function () {
                var form = document.getElementById('formImportExcel');

                if (!form || !window.fetch || !window.FormData) {
                    return;
                }

                var input = document.getElementById('fileImportExcel');
                var kotak = document.getElementById('importProgress');
                var bar = document.getElementById('importProgressBar');
                var persen = document.getElementById('importProgressPersen');
                var teks = document.getElementById('importProgressText');
                var galat = document.getElementById('importError');
                var tombolKirim = document.getElementById('btnSubmitImportExcel');
                var tombolBatal = document.getElementById('btnCancelImportExcel');
                var detail = document.getElementById('importProgressDetail');
                var spinner = document.getElementById('importSpinner');
                var metaCsrf = document.querySelector('meta[name="csrf-token"]');
                var csrf = metaCsrf ? metaCsrf.getAttribute('content') : '';
                var ukuranPotong = parseInt(form.dataset.chunkSize, 10) || 4194304;

                function ukuranTeks(byte) {
                    if (byte >= 1048576) {
                        return (byte / 1048576).toFixed(1).replace('.', ',') + ' MB';
                    }

                    return Math.max(1, Math.round(byte / 1024)) + ' KB';
                }

                function durasiTeks(detik) {
                    if (!isFinite(detik) || detik < 0) {
                        return '?';
                    }

                    if (detik < 60) {
                        return Math.round(detik) + ' dtk';
                    }

                    return Math.floor(detik / 60) + ' mnt ' + Math.round(detik % 60) + ' dtk';
                }

                function tampilProgress(nilai, label, keterangan) {
                    if (kotak) {
                        kotak.classList.remove('hidden');
                    }

                    if (bar) {
                        bar.style.width = nilai + '%';
                    }

                    if (persen) {
                        persen.textContent = Math.floor(nilai) + '%';
                    }

                    if (teks && label) {
                        teks.textContent = label;
                    }

                    if (detail && keterangan !== undefined) {
                        detail.textContent = keterangan;
                    }
                }

                function hentikanSpinner() {
                    if (spinner) {
                        spinner.classList.add('hidden');
                    }
                }

                function tampilGalat(pesan) {
                    hentikanSpinner();

                    if (galat) {
                        galat.textContent = pesan;
                        galat.classList.remove('hidden');
                    }

                    if (tombolKirim) {
                        tombolKirim.disabled = false;
                    }

                    if (tombolBatal) {
                        tombolBatal.disabled = false;
                    }
                }

                form.addEventListener('submit', function (event) {
                    var file = input && input.files ? input.files[0] : null;

                    // File kecil: kirim seperti biasa (satu request).
                    if (!file || file.size <= ukuranPotong) {
                        tampilProgress(0, 'Mengunggah ' + (file ? file.name : 'file') + '…');

                        return;
                    }

                    event.preventDefault();

                    if (galat) {
                        galat.classList.add('hidden');
                    }

                    if (tombolKirim) {
                        tombolKirim.disabled = true;
                    }

                    if (tombolBatal) {
                        tombolBatal.disabled = true;
                    }

                    var jumlahPotongan = Math.ceil(file.size / ukuranPotong);
                    var idUpload = (window.crypto && window.crypto.randomUUID)
                        ? window.crypto.randomUUID().replace(/-/g, '')
                        : 'u' + Date.now() + Math.random().toString(36).slice(2);
                    var indeks = 0;
                    var percobaan = 0;
                    var mulaiWaktu = Date.now();

                    tampilProgress(
                        0,
                        'Menyiapkan upload ' + jumlahPotongan + ' bagian…',
                        '0 KB / ' + ukuranTeks(file.size) + ' · ' + ukuranTeks(ukuranPotong) + ' per bagian'
                    );

                    function kirimPotongan() {
                        var mulai = indeks * ukuranPotong;
                        var potongan = file.slice(mulai, Math.min(mulai + ukuranPotong, file.size));
                        var data = new FormData();

                        data.append('_token', csrf);
                        data.append('chunk', potongan, 'bagian-' + indeks);
                        data.append('upload_id', idUpload);
                        data.append('chunk_index', indeks);
                        data.append('total_chunks', jumlahPotongan);
                        data.append('file_name', file.name);
                        data.append('total_size', file.size);

                        fetch(form.dataset.chunkUrl, {
                            method: 'POST',
                            body: data,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(function (respons) {
                                return respons
                                    .json()
                                    .catch(function () {
                                        return { message: 'Respons server tidak valid (HTTP ' + respons.status + ').' };
                                    })
                                    .then(function (isi) {
                                        return { ok: respons.ok, isi: isi };
                                    });
                            })
                            .then(function (hasil) {
                                if (!hasil.ok) {
                                    tampilGalat(hasil.isi.message || 'Upload gagal.');

                                    return;
                                }

                                indeks++;
                                percobaan = 0;

                                var terkirim = Math.min(indeks * ukuranPotong, file.size);
                                var detik = (Date.now() - mulaiWaktu) / 1000;
                                var kecepatan = detik > 0 ? terkirim / detik : 0;
                                var sisa = kecepatan > 0 ? (file.size - terkirim) / kecepatan : 0;

                                tampilProgress(
                                    terkirim / file.size * 100,
                                    'Bagian ' + indeks + '/' + jumlahPotongan + ' terkirim',
                                    ukuranTeks(terkirim) + ' / ' + ukuranTeks(file.size)
                                        + ' · ' + ukuranTeks(kecepatan) + '/dtk'
                                        + ' · sisa ~' + durasiTeks(sisa)
                                        + ' · ' + durasiTeks(detik) + ' berjalan'
                                );

                                if (hasil.isi.status === 'selesai') {
                                    hentikanSpinner();

                                    if (teks) {
                                        teks.textContent = 'Upload selesai, memulai import…';
                                    }

                                    window.location.href = hasil.isi.redirect;

                                    return;
                                }

                                kirimPotongan();
                            })
                            .catch(function () {
                                percobaan++;

                                if (percobaan <= 3) {
                                    if (teks) {
                                        teks.textContent = 'Koneksi terputus, mengulang bagian ' + (indeks + 1) + ' (percobaan ' + percobaan + '/3)…';
                                    }

                                    setTimeout(kirimPotongan, 1000);

                                    return;
                                }

                                tampilGalat('Gagal mengirim bagian ' + (indeks + 1) + ' setelah 3 percobaan.');
                            });
                    }

                    kirimPotongan();
                });
            })();
        });
    </script>
@endsection
