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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('nodes')->cascadeOnDelete();
            $table->string('type', 30)->comment('router, olt, switch, ap, ont');
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->string('serial_number', 100)->unique()->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 17)->nullable();
            $table->string('firmware_version', 50)->nullable();
            $table->string('snmp_community', 100)->nullable();
            $table->integer('api_port')->nullable();
            $table->string('api_user', 100)->nullable();
            $table->text('api_password_encrypted')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamp('last_seen_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('node_id');
            $table->index('type');
            $table->index('status');
            $table->index('ip_address');
            $table->index('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
