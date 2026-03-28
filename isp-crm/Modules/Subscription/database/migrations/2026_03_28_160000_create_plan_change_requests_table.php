<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_change_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('old_plan_id')->constrained('plans');
            $table->foreignId('new_plan_id')->constrained('plans');
            $table->string('change_type', 20);
            $table->string('effective_mode', 20);
            $table->timestamp('effective_at')->nullable();
            $table->date('scheduled_for')->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('old_plan_snapshot')->nullable();
            $table->json('new_plan_snapshot')->nullable();
            $table->decimal('old_monthly_price', 10, 2);
            $table->decimal('new_monthly_price', 10, 2);
            $table->decimal('prorate_credit', 10, 2)->default(0);
            $table->decimal('prorate_debit', 10, 2)->default(0);
            $table->decimal('net_difference', 10, 2)->default(0);
            $table->string('billing_adjustment_type', 20)->nullable();
            $table->boolean('feasibility_checked')->default(false);
            $table->json('feasibility_result')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('provision_status', 20)->nullable();
            $table->json('provision_result')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subscription_id', 'status']);
            $table->index('status');
            $table->index('scheduled_for');
            $table->index('change_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_change_requests');
    }
};
