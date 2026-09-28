<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('idpel', 20)->unique();
            $table->string('nama', 50)->nullable();
            $table->string('tarif', 10)->nullable();
            $table->unsignedInteger('daya')->nullable();
            $table->timestamps();

            $table->index('tarif');
            $table->index('daya');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan');
    }
};
