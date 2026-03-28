<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dunning_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('dunning_stage_id')->constrained('dunning_stages');
            $table->string('action_type', 30);
            $table->string('channel', 30)->nullable();
            $table->string('status', 20);
            $table->text('result')->nullable();
            $table->string('skip_reason', 50)->nullable();
            $table->smallInteger('days_overdue');
            $table->decimal('amount_overdue', 10, 2);
            $table->string('executed_by', 20);
            $table->string('job_run_id', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('executed_at');
            $table->timestamps();

            $table->unique(['invoice_id', 'dunning_stage_id']);
            $table->index('subscription_id');
            $table->index('executed_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_executions');
    }
};
