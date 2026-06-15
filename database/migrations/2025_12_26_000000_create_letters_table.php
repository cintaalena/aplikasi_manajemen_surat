<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->id();

            $table->string('template_slug');
            $table->string('title');
            $table->string('no_surat')->unique();

            $table->unsignedInteger('index_code');
            $table->unsignedBigInteger('urut');
            $table->string('month_roman', 4);
            $table->unsignedSmallInteger('year');

            $table->json('payload');

            $table->timestamp('printed_at');
            $table->foreignId('printed_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['template_slug']);
            $table->index(['index_code']);
            $table->index(['urut']);
            $table->index(['printed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
