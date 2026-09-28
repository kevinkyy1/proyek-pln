<?php

namespace App\Support;

use SimpleXMLElement;
use ZipArchive;

/**
 * Baca info ringan dari file xlsx tanpa memuat isi sheet ke memori.
 *
 * Dipakai untuk menghitung jumlah baris data saat upload, supaya progres import
 * sudah punya penyebut sejak awal. Hanya membaca workbook.xml, relasi, dan
 * beberapa KB pertama dari XML sheet (elemen <dimension>), bukan 341 MB isinya.
 */
class XlsxSheetInfo
{
    /**
     * Cek cepat apakah file benar-benar arsip xlsx (punya xl/workbook.xml).
     */
    public static function isValid(string $path): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return false;
        }

        try {
            return $zip->locateName('xl/workbook.xml') !== false;
        } finally {
            $zip->close();
        }
    }

    /**
     * Jumlah baris (termasuk baris header) menurut dimensi sheet, 0 kalau tidak terbaca.
     */
    public static function totalRows(string $path, string $sheetName): int
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return 0;
        }

        try {
            $target = self::sheetPath($zip, $sheetName);

            if ($target === null) {
                return 0;
            }

            $stream = $zip->getStream($target);

            if ($stream === false) {
                return 0;
            }

            $head = (string) fread($stream, 16384);
            fclose($stream);

            if (preg_match('/<dimension[^>]*ref="[A-Z]+\d+:[A-Z]+(\d+)"/', $head, $matches) === 1) {
                return (int) $matches[1];
            }

            return 0;
        } finally {
            $zip->close();
        }
    }

    /**
     * Nama sheet -> path XML di dalam arsip xlsx.
     */
    private static function sheetPath(ZipArchive $zip, string $sheetName): ?string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return null;
        }

        $workbook = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($relsXml);

        if (! $workbook instanceof SimpleXMLElement || ! $rels instanceof SimpleXMLElement) {
            return null;
        }

        $relId = null;

        foreach ($workbook->sheets->sheet as $sheet) {
            if ((string) $sheet['name'] !== $sheetName) {
                continue;
            }

            $relId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            break;
        }

        if ($relId === null || $relId === '') {
            return null;
        }

        foreach ($rels->Relationship as $rel) {
            if ((string) $rel['Id'] !== $relId) {
                continue;
            }

            $target = ltrim((string) $rel['Target'], '/');

            return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
        }

        return null;
    }
}
