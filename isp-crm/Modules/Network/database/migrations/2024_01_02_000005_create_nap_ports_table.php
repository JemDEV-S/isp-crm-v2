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
        Schema::create('nap_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nap_box_id')->constrained('nap_boxes')->cascadeOnDelete();
            $table->integer('port_number');
            $table->string('status', 30)->default('free');
            $table->unsignedBigInteger('subscription_id')->nullable()->comment('Will be linked to subscriptions module');
            $table->string('label', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['nap_box_id', 'port_number']);
            $table->index('nap_box_id');
            $table->index('status');
            $table->index('subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nap_ports');
    }
};
