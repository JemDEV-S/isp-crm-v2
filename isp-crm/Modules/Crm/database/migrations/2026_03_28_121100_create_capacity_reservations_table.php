<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacity_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservable_type', 100);
            $table->unsignedBigInteger('reservable_id');
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('feasibility_request_id')->nullable()->constrained('feasibility_requests')->nullOnDelete();
            $table->string('status', 30)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['reservable_type', 'reservable_id']);
            $table->index(['status', 'expires_at']);
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacity_reservations');
    }
};
