<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->morphs('tokenable');
            $table->foreignId('current_place_id')->constrained('places');
            $table->json('context')->nullable()->comment('Datos de contexto del token');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id']);
            $table->index(['workflow_id', 'current_place_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
