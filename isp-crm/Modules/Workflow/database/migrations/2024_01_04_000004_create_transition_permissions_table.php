<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transition_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transition_id')->constrained('transitions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['transition_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transition_permissions');
    }
};
