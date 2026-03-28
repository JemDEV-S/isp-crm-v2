<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_job_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('billing_period', 7);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 20)->default('running');
            $table->integer('total_eligible')->default(0);
            $table->integer('total_processed')->default(0);
            $table->integer('total_invoiced')->default(0);
            $table->integer('total_skipped')->default(0);
            $table->integer('total_failed')->default(0);
            $table->json('metadata')->nullable();
            $table->string('triggered_by', 20)->default('scheduler');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_job_runs');
    }
};
