<?php

namespace App\Support;

/**
 * Pemformatan angka untuk tampilan tabel (format Indonesia: ribuan '.' dan desimal ',').
 *
 * Kolom bulanan dan RATA2 ditampilkan sebagai bilangan bulat mengikuti format sel
 * di file Excel (number format '0'). Excel membulatkan setengah ke atas
 * (94,5 -> 95), dan PHP round() berperilaku sama. Nilai asli tetap desimal di
 * database — yang dibulatkan hanya tampilannya.
 */
class Angka
{
    /** Nilai kWh seperti di Excel: dibulatkan ke bilangan bulat. */
    public static function kwh(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) round((float) $value), 0, ',', '.');
    }

    /** Nilai rata-rata (PEM/JN) seperti di Excel: bilangan bulat, kosong tampil "-". */
    public static function rata2(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) round((float) $value), 0, ',', '.');
    }

    public static function daya(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 0, ',', '.');
    }
}
