<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans');
            $table->foreignId('address_id')->constrained('addresses');
            $table->string('status', 30)->default('draft');
            $table->unsignedTinyInteger('billing_day')->default(1);
            $table->string('billing_cycle', 20)->default('monthly');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('contracted_months')->nullable();
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('installation_fee', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->unsignedSmallInteger('discount_months_remaining')->default(0);
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('billing_day');
            $table->index(['customer_id', 'status']);
            $table->index('plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
