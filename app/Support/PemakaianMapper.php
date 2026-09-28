<?php

namespace App\Support;

/**
 * Utilitas daftar periode (YYYYMM).
 *
 * Sejak import membaca kolom periode langsung dari header file, daftar periode
 * yang dipakai aplikasi berasal dari file yang terakhir diimpor (disimpan di
 * import_batches.periode_list). Fungsi di sini hanya dipakai sebagai cadangan
 * kalau belum ada import sama sekali.
 */
class PemakaianMapper
{
    /**
     * Daftar periode cadangan dari config (mis. saat database masih kosong).
     *
     * @return array<int, string>
     */
    public static function periodeListDariConfig(): array
    {
        $jumlah = (int) config('import.periode_count');
        $tahunAwal = (int) substr((string) config('import.periode_start'), 0, 4);
        $bulanAwal = (int) substr((string) config('import.periode_start'), 4, 2) - 1;

        $periode = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $pergeseran = $bulanAwal + $i;
            $periode[] = sprintf('%04d%02d', $tahunAwal + intdiv($pergeseran, 12), $pergeseran % 12 + 1);
        }

        return $periode;
    }

    /**
     * Tahun unik dari daftar periode, contoh ['202401','202702'] -> [2024, 2027].
     *
     * @param  array<int, string>  $periodeList
     * @return array<int, int>
     */
    public static function tahunDari(array $periodeList): array
    {
        $tahun = [];

        foreach ($periodeList as $periode) {
            $tahun[(int) substr((string) $periode, 0, 4)] = true;
        }

        ksort($tahun);

        return array_keys($tahun);
    }
}
