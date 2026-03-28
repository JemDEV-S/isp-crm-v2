<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('has_pending_plan_change')->default(false)->after('notes');
            $table->timestamp('last_plan_change_at')->nullable()->after('has_pending_plan_change');
            $table->date('minimum_stay_until')->nullable()->after('last_plan_change_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['has_pending_plan_change', 'last_plan_change_at', 'minimum_stay_until']);
        });
    }
};
