<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->foreignId('work_order_type_id')->constrained('work_order_types');
            $table->string('type', 50); // Enum: installation, repair, relocation, etc.
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('address_id')->constrained('addresses');
            $table->string('priority', 20)->default('normal'); // Enum: low, normal, high, urgent
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->date('scheduled_date')->nullable();
            $table->string('scheduled_time_slot', 20)->nullable(); // Enum: morning, afternoon, evening
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('code');
            $table->index('type');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('scheduled_date');
            $table->index(['customer_id', 'type']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
