<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('serial_id')->nullable()->constrained('serials');
            $table->decimal('quantity', 10, 2);
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();

            $table->index('work_order_id');
            $table->index('product_id');
            $table->index('serial_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_usage');
    }
};
