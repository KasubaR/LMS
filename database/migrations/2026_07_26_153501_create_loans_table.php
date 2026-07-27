<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('principal', 14, 2);
            $table->decimal('interest_rate', 8, 2)->default(40);
            $table->string('interest_type')->default('flat');
            $table->unsignedInteger('duration_months')->default(1);
            $table->string('frequency')->default('monthly');
            $table->date('start_date');
            $table->date('due_date');
            $table->decimal('processing_fee', 14, 2)->default(0);
            $table->string('penalty_type')->default('daily_percent');
            $table->decimal('penalty_value', 14, 2)->default(0);
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('loan_number_sequences', function (Blueprint $table) {
            $table->string('prefix');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence')->default(0);
            $table->primary(['prefix', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_number_sequences');
        Schema::dropIfExists('loans');
    }
};
