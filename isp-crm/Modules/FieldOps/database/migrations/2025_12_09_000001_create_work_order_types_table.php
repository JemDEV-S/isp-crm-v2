<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('workflow_code')->nullable();
            $table->unsignedInteger('default_duration_minutes')->default(120);
            $table->boolean('requires_materials')->default(true);
            $table->foreignId('checklist_template_id')->nullable()->constrained('checklist_templates');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_types');
    }
};
