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
        Schema::create('letter_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->nullable()->constrained('letters')->nullOnDelete();
            $table->string('doc_key', 80);
            $table->string('doc_label', 200);
            $table->string('file_path', 500);
            $table->string('original_name', 300)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->index('letter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_documents');
    }
};
