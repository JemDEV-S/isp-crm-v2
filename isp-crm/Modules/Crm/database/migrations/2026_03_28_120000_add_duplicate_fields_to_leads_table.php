<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('is_duplicate')->default(false)->after('status');
            $table->foreignId('duplicate_of_id')
                ->nullable()
                ->after('is_duplicate')
                ->constrained('leads')
                ->nullOnDelete();
            $table->string('duplicate_resolution', 50)->nullable()->after('duplicate_of_id');

            $table->index('is_duplicate');
            $table->index('duplicate_of_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['is_duplicate']);
            $table->dropIndex(['duplicate_of_id']);
            $table->dropConstrainedForeignId('duplicate_of_id');
            $table->dropColumn(['is_duplicate', 'duplicate_resolution']);
        });
    }
};
