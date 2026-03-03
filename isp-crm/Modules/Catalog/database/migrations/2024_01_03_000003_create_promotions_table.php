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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();

            // Discount configuration
            $table->string('discount_type', 20); // percentage, fixed
            $table->decimal('discount_value', 10, 2);
            $table->string('applies_to', 20); // monthly, installation, both

            // Duration
            $table->unsignedInteger('min_months')->default(0); // Minimum contract duration required
            $table->unsignedInteger('discount_months')->nullable(); // How many months the discount applies

            // Validity period
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            // Usage limits
            $table->unsignedInteger('max_uses')->nullable(); // null = unlimited
            $table->unsignedInteger('current_uses')->default(0);

            // Status
            $table->boolean('is_active')->default(true);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('valid_from');
            $table->index('valid_until');
            $table->index(['is_active', 'valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
