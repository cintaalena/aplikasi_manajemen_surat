<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('register_otps', function (Blueprint $table) {
      $table->id();
      $table->string('phone', 20)->index();
      $table->string('purpose', 30)->default('register'); // extensible
      $table->string('otp_hash', 255);                    // HASH OTP
      $table->timestamp('expires_at');
      $table->timestamp('verified_at')->nullable();
      $table->unsignedTinyInteger('attempts')->default(0);
      $table->timestamp('last_sent_at')->nullable();
      $table->string('request_ip', 45)->nullable();
      $table->string('user_agent', 255)->nullable();
      $table->timestamps();

      $table->index(['phone', 'purpose', 'expires_at']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('register_otps');
  }
};

