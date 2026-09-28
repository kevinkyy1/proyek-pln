<?php

return [
    /*
     * Nama sheet sumber data pada file Excel (sheet lain diabaikan).
     */
    'sheet' => env('IMPORT_SHEET', 'MASTER'),

    /*
     * Jumlah baris per job chunk.
     *
     * Catatan penting: tiap chunk dibaca oleh job terpisah dan tiap job memuat
     * ulang file Excel-nya (sheet MASTER = 341 MB XML), jadi biaya baca tiap job
     * hampir sama besar. Chunk besar = job lebih sedikit = total waktu lebih
     * singkat, tapi memori tiap job lebih besar.
     */
    'chunk_size' => (int) env('IMPORT_CHUNK_SIZE', 20000),

    /*
     * Ukuran potongan upload dari browser (byte). File besar dikirim bertahap
     * lewat fetch supaya tidak kena batas post_max_size PHP.
     *
     * 1 MB dipilih supaya aman dengan nilai bawaan php.ini (upload_max_filesize
     * 2M / post_max_size 8M): tiap permintaan hanya ~1 MB.
     */
    'upload_chunk_size' => (int) env('IMPORT_UPLOAD_CHUNK_SIZE', 1024 * 1024),

    /*
     * Layout sheet MASTER:
     *   baris 1 = header (IDPEL, NAMA, TARIF, DAYA, 202401, 202402, ..., RATA2 ...)
     *   baris 2 = sub-header khusus kolom RATA2 (PEM, JN, KET)
     *   baris 3 = awal data
     *
     * Kolom bulan TIDAK dipatok di sini: semua kolom header yang berbentuk
     * YYYYMM (202401, 202402, ..., 202701) dikenali otomatis saat import,
     * lalu daftar itu disimpan per batch (import_batches.periode_list) dan
     * dipakai sebagai kolom tabel. Kunci periode_* di bawah hanya cadangan
     * tampilan kalau belum ada satu pun import.
     */
    'data_start_row' => (int) env('IMPORT_DATA_START_ROW', 3),
    'periode_start_index' => 4,
    'periode_count' => 36,
    'periode_start' => '202401',
];
