<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('PEN');
            $table->string('method', 30);
            $table->string('channel', 30);
            $table->string('status', 20)->default('pending');
            $table->string('reference', 100)->nullable();
            $table->string('external_id', 100)->nullable();
            $table->string('idempotency_key', 100)->unique()->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('validated_at')->nullable();
            $table->string('reconciliation_status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index('reference');
            $table->index('external_id');
            $table->index('reconciliation_status');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
