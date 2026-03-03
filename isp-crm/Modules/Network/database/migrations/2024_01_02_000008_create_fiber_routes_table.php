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
        Schema::create('fiber_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_node_id')->constrained('nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('nodes')->cascadeOnDelete();
            $table->integer('distance_meters');
            $table->integer('fiber_count');
            $table->json('route_geojson')->nullable()->comment('GeoJSON LineString');
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('from_node_id');
            $table->index('to_node_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiber_routes');
    }
};
