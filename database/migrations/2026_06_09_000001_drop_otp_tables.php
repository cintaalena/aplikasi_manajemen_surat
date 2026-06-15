<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('email_otps');
        Schema::dropIfExists('register_otps');
    }

    public function down(): void
    {
        // Tables were intentionally removed and are not restored.
    }
};
