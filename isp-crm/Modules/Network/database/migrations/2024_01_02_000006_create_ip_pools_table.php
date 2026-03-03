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
        Schema::create('ip_pools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('network_cidr', 50)->comment('e.g., 10.0.0.0/24');
            $table->string('gateway', 45);
            $table->string('dns_primary', 45)->nullable();
            $table->string('dns_secondary', 45)->nullable();
            $table->string('type', 30)->comment('public, private, cgnat');
            $table->integer('vlan_id')->nullable();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('device_id');
            $table->index('type');
            $table->index('is_active');
            $table->index('vlan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_pools');
    }
};
