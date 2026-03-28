<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions');
            $table->decimal('total_debt', 10, 2);
            $table->string('status', 20)->default('open');
            $table->string('priority', 10)->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('external_agency', 100)->nullable();
            $table->timestamp('sent_to_external_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('close_reason', 30)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('status');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_cases');
    }
};
