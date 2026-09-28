<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemakaian_bulanan', function (Blueprint $table) {
            $table->id();
            $table->string('idpel', 20);
            $table->char('periode', 6)->comment('YYYYMM');
            $table->decimal('kwh', 12, 2)->nullable();

            // index (idpel) tidak perlu: sudah tercakup unique (idpel, periode)
            $table->index('periode');

            // Unik (idpel, periode) supaya job chunk aman diulang (insertOrIgnore)
            $table->unique(['idpel', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemakaian_bulanan');
    }
};
