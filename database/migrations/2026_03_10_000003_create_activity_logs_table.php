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

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 150)->nullable();

            $table->string('action', 20)->index();

            $table->string('model_type', 50)->index();
            $table->unsignedBigInteger('model_id')->index();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['model_type', 'model_id'], 'idx_activity_model');
            $table->index(['user_id', 'created_at'], 'idx_activity_user_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
