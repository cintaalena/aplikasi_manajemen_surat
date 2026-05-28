<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel activity_logs: menyimpan jejak audit setiap perubahan data sensitif.
     * Setiap baris mencatat: siapa (user), apa (action), kapan, pada data apa,
     * dari mana (IP), dan nilai sebelum/sesudah perubahan.
     *
     * Model yang diaudit: User, Letter, Penduduk
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Siapa yang melakukan aksi (nullable: bisa System/seeder)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 150)->nullable();

            // Aksi yang dilakukan: created | updated | deleted | restored
            $table->string('action', 20)->index();

            // Model yang terdampak: Letter, Penduduk, User
            $table->string('model_type', 50)->index();
            $table->unsignedBigInteger('model_id')->index();

            // Nilai sebelum dan sesudah perubahan (hanya saat 'updated')
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Konteks request
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 512)->nullable();

            // Hanya created_at — log tidak bisa diubah (append-only)
            $table->timestamp('created_at')->useCurrent();

            // Index gabungan untuk query "riwayat surat X" atau "aktivitas user Y"
            $table->index(['model_type', 'model_id'], 'idx_activity_model');
            $table->index(['user_id', 'created_at'], 'idx_activity_user_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
