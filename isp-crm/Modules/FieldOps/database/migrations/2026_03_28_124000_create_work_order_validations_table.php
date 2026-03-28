<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('validator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->json('criteria_checked')->nullable();
            $table->json('observations')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'status']);
            $table->index('validated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_validations');
    }
};
