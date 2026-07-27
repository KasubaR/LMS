<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // your internal reference, sent to Lenco
            $table->string('lenco_reference')->nullable(); // Lenco's own transaction id
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('operator'); // mtn | airtel | zamtel
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('ZMW');
            $table->string('status')->default('pending'); // pending | successful | failed | otp-required | pay-offline
            $table->string('reason_for_failure')->nullable();
            $table->json('raw_response')->nullable(); // full Lenco response for debugging/audit
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable(); // when an admin approved the send
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
