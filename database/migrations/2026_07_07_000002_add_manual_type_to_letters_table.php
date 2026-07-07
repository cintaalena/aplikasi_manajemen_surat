<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->string('manual_type', 20)->nullable()->after('is_manual');
        });

        // Surat manual yang sudah ada sebelum fitur "Surat Keluar" ditambahkan
        // semuanya adalah surat masuk.
        DB::table('letters')->where('is_manual', true)->update(['manual_type' => 'masuk']);
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn('manual_type');
        });
    }
};
