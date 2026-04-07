<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop existing table if exists
        Schema::dropIfExists('penduduks');
        
        // Recreate with correct structure
        Schema::create('penduduks', function (Blueprint $table) {
            $table->id();

            $table->string('kode_keluarga', 32)->unique(); // No KK
            $table->string('nama_kepala_keluarga', 150);
            $table->string('alamat', 255);

            $table->string('rt', 3); // "001"
            $table->string('rw', 3); // "001"
            $table->string('nama_dusun', 100)->nullable();

            $table->unsignedTinyInteger('bulan'); // 1..12
            $table->unsignedSmallInteger('tahun'); // 1900..2100

            $table->string('nama_pengisi', 150)->nullable();
            $table->string('pekerjaan', 150)->nullable();
            $table->string('jabatan', 150)->nullable();
            $table->string('sumber_data', 200)->nullable();

            $table->timestamps();

            $table->index(['nama_dusun', 'rt', 'rw']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penduduks');
    }
};
