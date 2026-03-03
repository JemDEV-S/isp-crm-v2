<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique()->comment('Código de cliente autogenerado');
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('customer_type', 20)->default('personal');
            $table->string('document_type', 20);
            $table->string('document_number', 20);
            $table->string('name', 150);
            $table->string('trade_name', 150)->nullable()->comment('Nombre comercial para empresas');
            $table->string('phone', 20);
            $table->string('email', 100)->nullable();
            $table->string('billing_email', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('credit_limit', 10, 2)->default(0);
            $table->boolean('tax_exempt')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['document_type', 'document_number']);
            $table->index('customer_type');
            $table->index('is_active');
            $table->index('phone');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
