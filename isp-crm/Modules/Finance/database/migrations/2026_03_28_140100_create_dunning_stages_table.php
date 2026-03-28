<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dunning_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dunning_policy_id')->constrained('dunning_policies')->cascadeOnDelete();
            $table->smallInteger('stage_order');
            $table->string('name', 50);
            $table->string('code', 30);
            $table->string('action_type', 30);
            $table->smallInteger('min_days_overdue');
            $table->smallInteger('max_days_overdue');
            $table->json('channels');
            $table->string('template_code', 50)->nullable();
            $table->boolean('auto_execute')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['dunning_policy_id', 'stage_order']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_stages');
    }
};
