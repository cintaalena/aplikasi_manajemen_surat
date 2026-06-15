<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('jabatan', 50)->nullable()->index();
            $table->foreignId('kelurahan_credential_id')
                ->nullable()
                ->constrained('kelurahan_credentials')
                ->nullOnDelete()
                ->unique();
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['kelurahan_credential_id']);
            $table->dropConstrainedForeignId('kelurahan_credential_id');
            $table->dropColumn('jabatan');
        });
    }
};

