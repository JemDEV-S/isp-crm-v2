<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('technology', 30);
            $table->unsignedInteger('download_speed'); // Mbps
            $table->unsignedInteger('upload_speed'); // Mbps
            $table->decimal('price', 10, 2);
            $table->decimal('installation_fee', 10, 2)->default(0);

            // Network configuration references
            $table->foreignId('ip_pool_id')->nullable()->constrained('ip_pools')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();

            // Router/OLT profiles
            $table->string('router_profile', 100)->nullable();
            $table->string('olt_profile', 100)->nullable();

            // Traffic shaping
            $table->boolean('burst_enabled')->default(false);
            $table->unsignedTinyInteger('priority')->default(4); // 1-8, where 1 is highest

            // Visibility flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true); // Show in public catalog

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('technology');
            $table->index('is_active');
            $table->index('is_visible');
            $table->index(['is_active', 'is_visible']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
