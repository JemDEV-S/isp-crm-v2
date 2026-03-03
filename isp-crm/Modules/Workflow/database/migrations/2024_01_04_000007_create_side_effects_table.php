<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('side_effects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transition_id')->constrained('transitions')->cascadeOnDelete();
            $table->enum('trigger_point', ['on_exit', 'on_enter', 'before', 'after'])->default('after');
            $table->string('action_class', 255)->comment('Clase que implementa la acción');
            $table->json('parameters')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['transition_id', 'trigger_point']);
            $table->index(['transition_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('side_effects');
    }
};
