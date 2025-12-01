<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');

            $table->string('merchant_oid')->unique();
            $table->string('payment_provider')->default('paytr');
            $table->string('payment_method')->nullable();

            $table->decimal('amount', 12, 2);
            $table->integer('payment_amount'); // Amount in kuruş (cents)
            $table->string('currency', 3)->default('TRY');

            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');

            // PayTR specific fields
            $table->string('paytr_token')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('masked_pan')->nullable();
            $table->string('installment_count')->nullable();

            // Response data
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();

            // Terms acceptance
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_acceptance_ip')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('merchant_oid');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
