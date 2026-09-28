<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\Pelanggan;
use App\Models\PemakaianBulanan;
use App\Support\PemakaianMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DataTableController extends Controller
{
    /** Pilihan jumlah baris per halaman. */
    private const PER_PAGE = [25, 50, 100, 250];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', self::PER_PAGE[0]);
        $perPage = in_array($perPage, self::PER_PAGE, true) ? $perPage : self::PER_PAGE[0];

        // Pagination dikerjakan di sisi database (LIMIT/OFFSET), jadi hanya baris
        // halaman aktif yang diambil — bukan seluruh 160 ribu baris.
        $pelanggan = Pelanggan::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('idpel', 'like', '%'.$search.'%')
                        ->orWhere('nama', 'like', '%'.$search.'%')
                        ->orWhere('tarif', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('idpel')
            ->paginate($perPage)
            ->withQueryString();

        // Daftar kolom periode mengikuti hasil pembacaan header file terakhir,
        // jadi kalau file ditambah kolom bulan baru (mis. 202701) kolom itu ikut muncul.
        $periodeList = $this->periodeList();
        $tahunList = PemakaianMapper::tahunDari($periodeList);

        // Pemakaian hanya diambil untuk IDPEL yang tampil di halaman ini.
        $pemakaian = PemakaianBulanan::query()
            ->whereIn('idpel', $pelanggan->getCollection()->pluck('idpel')->all())
            ->get(['idpel', 'periode', 'kwh'])
            ->groupBy('idpel');

        $rows = $pelanggan->getCollection()->map(function (Pelanggan $item) use ($pemakaian, $tahunList): array {
            $bulanan = ($pemakaian[$item->idpel] ?? new Collection)
                ->pluck('kwh', 'periode');

            return [
                'idpel' => $item->idpel,
                'nama' => $item->nama,
                'tarif' => $item->tarif,
                'daya' => $item->daya,
                'bulanan' => $bulanan,
                'rata2' => $this->rata2($bulanan, $item->daya, $tahunList),
            ];
        });

        $batches = ImportBatch::query()
            ->latest()
            ->limit(5)
            ->get();

        return view('data-table.index', [
            'pelanggan' => $pelanggan,
            'rows' => $rows,
            'periodeList' => $periodeList,
            'tahunList' => $tahunList,
            'batches' => $batches,
            'chunkSize' => (int) config('import.chunk_size'),
            'search' => $search,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE,
        ]);
    }

    /**
     * Data grafik pemakaian satu IDPEL (JSON) untuk modal di halaman tabel.
     *
     * Dua deret mengikuti sheet GRAFIK di file Excel:
     *   PEM       = kWh murni per bulan (kolom 202401, 202402, …)
     *   JAM NYALA = (PEM x 1000) / DAYA pemakai, rumus =(E10*1000)/$E$7
     *
     * Bulan yang tidak ada nilainya dikirim 0, mengikuti sheet GRAFIK di file:
     * rumus di sana memakai XLOOKUP dengan default "0", jadi sel kosong pun
     * terhitung nol dan garisnya tetap menyambung sepanjang daftar periode.
     */
    public function grafik(string $idpel): JsonResponse
    {
        $pelanggan = Pelanggan::query()->where('idpel', $idpel)->first();

        if ($pelanggan === null) {
            return response()->json(['message' => 'IDPEL '.$idpel.' tidak ditemukan.'], 404);
        }

        $periodeList = $this->periodeList();
        $daya = $pelanggan->daya === null ? null : (int) $pelanggan->daya;

        $bulanan = PemakaianBulanan::query()
            ->where('idpel', $idpel)
            ->pluck('kwh', 'periode');

        $pem = [];
        $jamNyala = [];

        foreach ($periodeList as $periode) {
            $kwh = $bulanan[$periode] ?? null;
            $kwh = $kwh === null ? null : (float) $kwh;

            $pem[] = round($kwh ?? 0, 2);
            $jamNyala[] = ! $daya ? null : round(($kwh ?? 0) * 1000 / $daya, 2);
        }

        return response()->json([
            'idpel' => $pelanggan->idpel,
            'nama' => $pelanggan->nama,
            'tarif' => $pelanggan->tarif,
            'daya' => $daya,
            'label' => array_values($periodeList),
            'pem' => $pem,
            'jam_nyala' => $jamNyala,
        ]);
    }

    /**
     * Daftar periode untuk header tabel.
     *
     * Sumber utama: kolom periode yang terdeteksi dari header file pada import
     * terakhir. Kalau belum pernah ada import, pakai daftar cadangan di config.
     *
     * @return array<int, string>
     */
    private function periodeList(): array
    {
        $dariFile = ImportBatch::query()
            ->whereNotNull('periode_list')
            ->latest()
            ->first()
            ?->periode_list;

        if (is_array($dariFile) && $dariFile !== []) {
            return array_values($dariFile);
        }

        return PemakaianMapper::periodeListDariConfig();
    }

    /**
     * Kolom RATA2 per tahun seperti di sheet MASTER:
     *   PEM = rata-rata kWh bulan yang ada datanya
     *   JN  = jam nyala = PEM x 1000 / DAYA
     *   KET = belum ada di database (nilai aslinya TBT/NORMAL/40JN dari file)
     *
     * @param  Collection<string, mixed>  $bulanan  periode => kwh
     * @param  array<int, int>  $tahunList
     * @return array<int, array{pem: float|null, jn: float|null, ket: string|null}>
     */
    private function rata2(Collection $bulanan, ?int $daya, array $tahunList): array
    {
        $hasil = [];

        foreach ($tahunList as $tahun) {
            $nilai = $bulanan
                ->filter(fn ($kwh, $periode) => str_starts_with((string) $periode, (string) $tahun) && $kwh !== null)
                ->map(fn ($kwh) => (float) $kwh);

            $pem = $nilai->isEmpty() ? null : $nilai->sum() / $nilai->count();
            $jn = ($pem === null || ! $daya) ? null : $pem * 1000 / $daya;

            $hasil[$tahun] = [
                'pem' => $pem,
                'jn' => $jn,
                'ket' => $this->ket($jn),
            ];
        }

        return $hasil;
    }

    /**
     * Kolom KET, meniru rumus di file Excel:
     *   =IF(JN<1,"TBT",IF(JN>40,"NORMAL","40JN"))
     *
     * JN dibulatkan 6 desimal lebih dulu: file Excel menyimpan sisa floating point
     * (mis. jam nyala 1 tersimpan 0,99999999999999978) dan KET-nya tetap "40JN".
     * Dengan pembulatan ini hasil kami sama dengan 481.692 dari 481.692 sel KET di file.
     */
    private function ket(?float $jn): ?string
    {
        if ($jn === null) {
            return null;
        }

        $nilai = round($jn, 6);

        if ($nilai < 1) {
            return 'TBT';
        }

        return $nilai > 40 ? 'NORMAL' : '40JN';
    }
}
