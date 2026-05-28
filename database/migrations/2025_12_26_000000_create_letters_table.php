<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->id();

            // identitas surat
            $table->string('template_slug');                 // contoh: keterangan-domisili
            $table->string('title');                         // judul surat (untuk arsip)
            $table->string('no_surat')->unique();            // harus unik (anti bentrok)

            // komponen nomor
            $table->unsignedInteger('index_code');           // contoh: 475
            $table->unsignedBigInteger('urut');              // contoh: 71
            $table->string('month_roman', 4);                // I-XII
            $table->unsignedSmallInteger('year');            // 2025

            // isi surat (flexibel antar template)
            $table->json('payload');

            // finalisasi
            $table->timestamp('printed_at');                 // kapan final/cetak
            $table->foreignId('printed_by')->nullable()
                ->constrained('users')->nullOnDelete();      // siapa yang cetak (opsional)

            $table->timestamps();

            $table->index(['template_slug']);
            $table->index(['index_code']);
            $table->index(['urut']);
            $table->index(['printed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
