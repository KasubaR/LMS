<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('email');
            $table->boolean('failed')->default(false)->index();
            $table->string('failure_reason')->nullable();

            $table->timestamp('login_at')->index();
            $table->timestamp('logout_at')->nullable();

            $table->string('ip_address')->nullable()->index();
            $table->string('browser')->nullable();
            $table->string('device')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
