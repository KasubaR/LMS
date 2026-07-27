<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('loan_number_prefix')->default('LN');
            $table->decimal('default_interest_rate', 5, 2)->default(40);
            $table->string('default_penalty_type')->default('fixed');
            $table->decimal('default_penalty_value', 8, 2)->default(0);
            $table->unsignedSmallInteger('grace_period_days')->default(0);
            $table->string('currency_code')->default('ZMW');
            $table->string('currency_symbol')->default('K');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
