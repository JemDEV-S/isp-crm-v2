<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->string('type', 20); // Enum: before, during, after
            $table->string('file_path');
            $table->string('caption')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('taken_at');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index('work_order_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_photos');
    }
};
