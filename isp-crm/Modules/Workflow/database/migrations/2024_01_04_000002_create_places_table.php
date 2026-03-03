<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('color', 20)->default('#6B7280');
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_final')->default(false);
            $table->unsignedSmallInteger('order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'code']);
            $table->index(['workflow_id', 'is_initial']);
            $table->index(['workflow_id', 'is_final']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
