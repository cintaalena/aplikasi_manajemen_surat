<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'jabatan')) {
                $table->string('jabatan', 50)->index()->after('phone');
            }
            if (!Schema::hasColumn('users', 'credential_code_hash')) {
                $table->string('credential_code_hash')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'credential_issued_at')) {
                $table->timestamp('credential_issued_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'jabatan',
                'credential_code_hash',
                'credential_issued_at'
            ]);
        });
    }
};
