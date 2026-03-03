<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number', 100)->unique();
            $table->string('mac_address', 17)->nullable()->unique();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('status', 30); // in_stock, assigned, in_transit, damaged, returned, lost
            $table->unsignedBigInteger('subscription_id')->nullable(); // Si está asignado
            $table->date('purchase_date')->nullable();
            $table->date('warranty_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('status');
            $table->index('subscription_id');
            $table->index(['product_id', 'status']);
            $table->index(['warehouse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serials');
    }
};
