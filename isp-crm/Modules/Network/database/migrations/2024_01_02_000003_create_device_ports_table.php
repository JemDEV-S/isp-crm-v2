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
        Schema::create('device_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->integer('port_number');
            $table->string('port_name', 50)->nullable();
            $table->string('type', 30)->comment('ethernet, gpon, sfp');
            $table->integer('speed_mbps')->nullable();
            $table->string('status', 30)->default('active');
            $table->foreignId('connected_device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'port_number']);
            $table->index('device_id');
            $table->index('status');
            $table->index('connected_device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_ports');
    }
};
