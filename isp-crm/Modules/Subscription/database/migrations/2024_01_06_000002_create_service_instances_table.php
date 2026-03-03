<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('pppoe_user', 50)->nullable()->unique();
            $table->string('pppoe_password', 50)->nullable();
            $table->foreignId('ip_address_id')->nullable()->constrained('ip_addresses')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->foreignId('nap_port_id')->nullable()->constrained('nap_ports')->nullOnDelete();
            $table->string('onu_serial', 50)->nullable();
            $table->string('provision_status', 30)->default('pending');
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('last_connection_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('pppoe_user');
            $table->index('provision_status');
            $table->index('onu_serial');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_instances');
    }
};
