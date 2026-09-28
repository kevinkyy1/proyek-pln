<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            // daftar kolom periode yang dibaca dari header file (JSON), mis. ["202401",...,"202701"]
            $table->text('periode_list')->nullable()->after('chunks_total');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn('periode_list');
        });
    }
};
