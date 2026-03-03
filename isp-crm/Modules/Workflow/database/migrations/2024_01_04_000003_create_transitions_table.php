<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->foreignId('from_place_id')->nullable()->constrained('places')->cascadeOnDelete();
            $table->foreignId('to_place_id')->constrained('places')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('from_any')->default(false)->comment('Si es true, la transición puede ejecutarse desde cualquier estado');
            $table->json('conditions')->nullable()->comment('Condiciones adicionales para la transición');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'code']);
            $table->index(['workflow_id', 'from_place_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transitions');
    }
};
