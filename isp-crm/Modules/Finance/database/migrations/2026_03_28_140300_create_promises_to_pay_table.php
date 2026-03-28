<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promises_to_pay', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->decimal('promised_amount', 10, 2);
            $table->date('promise_date');
            $table->string('status', 20)->default('pending');
            $table->string('source_channel', 30);
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('broken_at')->nullable();
            $table->smallInteger('max_extensions')->default(0);
            $table->smallInteger('extensions_used')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index('promise_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promises_to_pay');
    }
};
