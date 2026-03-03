<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique();
            $table->string('type', 30); // transfer, reservation
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('status', 30)->default('pending'); // pending, approved, rejected, completed
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // Índices
            $table->index('status');
            $table->index('from_warehouse_id');
            $table->index('to_warehouse_id');
            $table->index('requested_by');
        });

        Schema::create('movement_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('movement_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_requested', 10, 2);
            $table->decimal('quantity_approved', 10, 2)->nullable();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->timestamps();

            // Índices
            $table->index('request_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_request_items');
        Schema::dropIfExists('movement_requests');
    }
};
