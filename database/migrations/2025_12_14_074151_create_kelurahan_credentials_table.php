<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('kelurahan_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('jabatan', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['jabatan', 'is_active']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('kelurahan_credentials');
    }
};

