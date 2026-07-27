<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_number_sequences', function (Blueprint $table) {
            $table->string('prefix');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence')->default(0);
            $table->primary(['prefix', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_number_sequences');
    }
};
