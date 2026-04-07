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
        Schema::table('email_otps', function (Blueprint $table) {
            // SECURITY: Progressive cooldown untuk mencegah brute force
            $table->timestamp('locked_until')->nullable()->after('attempts');
            $table->unsignedTinyInteger('consecutive_failures')->default(0)->after('locked_until');
            $table->timestamp('last_failed_at')->nullable()->after('consecutive_failures');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->dropColumn(['locked_until', 'consecutive_failures', 'last_failed_at']);
        });
    }
};
