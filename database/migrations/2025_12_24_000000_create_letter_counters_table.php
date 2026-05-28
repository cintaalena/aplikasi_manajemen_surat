<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
       Schema::create('letter_counters', function (Blueprint $table) {
    $table->id();

    $table->string('template_slug')->unique();

    $table->unsignedBigInteger('count')->default(0);
    $table->timestamps();
});
    }

    public function down()
    {
        Schema::dropIfExists('letter_counters');
    }
};
