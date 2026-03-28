<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_instances', function (Blueprint $table) {
            $table->json('provision_data')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('service_instances', function (Blueprint $table) {
            $table->dropColumn('provision_data');
        });
    }
};
