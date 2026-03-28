<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('georeference_quality', 20)->nullable()->after('longitude');
            $table->text('address_reference')->nullable()->after('reference');
            $table->string('photo_url')->nullable()->after('address_reference');

            $table->index('georeference_quality');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex(['georeference_quality']);
            $table->dropColumn(['georeference_quality', 'address_reference', 'photo_url']);
        });
    }
};
