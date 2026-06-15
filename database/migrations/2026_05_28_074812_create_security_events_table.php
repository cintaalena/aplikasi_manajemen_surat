<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SECURITY (A09 - Security Logging and Monitoring Failures):
 * Centralized security event log for authentication and authorization events.
 * This table is append-only — no updates, no deletes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 60)->index();
            $table->string('severity', 20)->default('warning');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('username_attempted', 255)->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_agent', 512)->nullable();
            $table->string('url', 512)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
