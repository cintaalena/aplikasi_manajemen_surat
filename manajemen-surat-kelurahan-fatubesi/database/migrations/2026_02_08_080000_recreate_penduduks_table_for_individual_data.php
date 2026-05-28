<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop existing table
        Schema::dropIfExists('penduduks');
        
        // Create new table with individual data structure
        Schema::create('penduduks', function (Blueprint $table) {
            $table->id();
            
            // Data Keluarga
            $table->string('kode_keluarga', 32)->index(); // No KK (tidak unique karena 1 KK bisa banyak anggota)
            $table->string('nama_kepala_keluarga', 150)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->string('rt', 3)->nullable(); // "001"
            $table->string('rw', 3)->nullable(); // "001"
            $table->string('dusun', 100)->nullable();
            
            // Data Individu
            $table->unsignedInteger('no_urut')->nullable(); // No. urut dalam keluarga
            $table->string('nik', 20)->unique()->nullable(); // NIK
            $table->string('nama', 150); // Nama Anggota Keluarga
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable(); // L/P
            $table->string('hubungan', 50)->nullable(); // Hubungan dengan kepala keluarga
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->unsignedTinyInteger('usia')->nullable();
            $table->string('status_perkawinan', 50)->nullable(); // Status
            $table->string('agama', 30)->nullable();
            $table->string('golongan_darah', 3)->nullable(); // A, B, AB, O
            $table->string('kewarganegaraan', 50)->default('WNI');
            $table->string('etnis', 50)->nullable(); // Etnis/Suku
            $table->string('pendidikan', 100)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['dusun', 'rt', 'rw']);
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penduduks');
    }
};
