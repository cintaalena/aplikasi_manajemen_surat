<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SECURITY (A01 - Broken Access Control):
 * Track the uploader of each document to enforce object-level authorization —
 * only the uploader or an admin may delete an unlinked document.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by')->nullable()->after('file_size');
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('letter_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn('uploaded_by');
        });
    }
};
