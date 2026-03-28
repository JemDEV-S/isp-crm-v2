<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->smallInteger('days_overdue')->default(0)->after('status');
            $table->string('aging_bucket', 10)->nullable()->after('days_overdue');
            $table->foreignId('last_dunning_stage_id')->nullable()->after('aging_bucket')
                ->constrained('dunning_stages')->nullOnDelete();
            $table->boolean('dunning_paused')->default(false)->after('last_dunning_stage_id');
            $table->string('dunning_pause_reason', 50)->nullable()->after('dunning_paused');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['last_dunning_stage_id']);
            $table->dropColumn([
                'days_overdue',
                'aging_bucket',
                'last_dunning_stage_id',
                'dunning_paused',
                'dunning_pause_reason',
            ]);
        });
    }
};
