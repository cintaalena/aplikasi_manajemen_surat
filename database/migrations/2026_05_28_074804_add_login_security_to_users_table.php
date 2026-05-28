<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SECURITY (A07 - Identification and Authentication Failures):
 * Add persistent login security columns to prevent brute-force attacks
 * beyond session-scoped rate limiting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Track consecutive failed login attempts (A07 - Brute Force Protection)
            $table->unsignedSmallInteger('failed_login_attempts')->default(0)->after('is_active');
            // Timestamp when account is locked out (null = not locked)
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            // Timestamp of last failed login attempt
            $table->timestamp('last_failed_login_at')->nullable()->after('locked_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['failed_login_attempts', 'locked_until', 'last_failed_login_at']);
        });
    }
};
