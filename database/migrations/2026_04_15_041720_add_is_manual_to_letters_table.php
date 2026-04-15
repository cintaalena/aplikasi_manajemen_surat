<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            // Flag surat masuk manual (dari luar, bukan dari cetak web)
            $table->boolean('is_manual')->default(false)->after('printed_by');

            // Kolom-kolom ini tidak relevan untuk surat manual, jadikan nullable
            $table->unsignedInteger('index_code')->nullable()->change();
            $table->unsignedBigInteger('urut')->nullable()->change();
            $table->string('month_roman', 4)->nullable()->change();
            $table->unsignedSmallInteger('year')->nullable()->change();
            $table->json('payload')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn('is_manual');
            $table->unsignedInteger('index_code')->nullable(false)->change();
            $table->unsignedBigInteger('urut')->nullable(false)->change();
            $table->string('month_roman', 4)->nullable(false)->change();
            $table->unsignedSmallInteger('year')->nullable(false)->change();
            $table->json('payload')->nullable(false)->change();
        });
    }
};
