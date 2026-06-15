<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('penduduks');
        
        Schema::create('penduduks', function (Blueprint $table) {
            $table->id();
            
            $table->string('kode_keluarga', 32)->index();
            $table->string('nama_kepala_keluarga', 150)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();
            $table->string('dusun', 100)->nullable();
            
            $table->unsignedInteger('no_urut')->nullable();
            $table->string('nik', 20)->unique()->nullable();
            $table->string('nama', 150);
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('hubungan', 50)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->unsignedTinyInteger('usia')->nullable();
            $table->string('status_perkawinan', 50)->nullable();
            $table->string('agama', 30)->nullable();
            $table->string('golongan_darah', 3)->nullable();
            $table->string('kewarganegaraan', 50)->default('WNI');
            $table->string('etnis', 50)->nullable();
            $table->string('pendidikan', 100)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            
            $table->timestamps();
            
            $table->index(['dusun', 'rt', 'rw']);
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penduduks');
    }
};
