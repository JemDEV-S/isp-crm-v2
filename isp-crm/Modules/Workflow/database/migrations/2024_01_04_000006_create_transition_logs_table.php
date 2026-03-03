<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transition_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained('tokens')->cascadeOnDelete();
            $table->foreignId('transition_id')->nullable()->constrained('transitions')->nullOnDelete();
            $table->foreignId('from_place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->foreignId('to_place_id')->constrained('places');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('executed_at');
            $table->timestamps();

            $table->index(['token_id', 'executed_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transition_logs');
    }
};
