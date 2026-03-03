<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('relationship', 50)->nullable()->comment('Ej: Titular, Esposa, Hijo, Administrador');
            $table->string('type', 20)->comment('phone, email, whatsapp');
            $table->string('value', 100);
            $table->boolean('is_primary')->default(false);
            $table->boolean('receives_notifications')->default(true);
            $table->timestamps();

            $table->index('customer_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
