<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('code', 30)->nullable()->after('invoice_id');
            $table->string('type', 30)->default('service')->after('code');
            $table->date('billing_period_start')->nullable()->after('tax');
            $table->date('billing_period_end')->nullable()->after('billing_period_start');
            $table->string('source_reference', 100)->nullable()->after('billing_period_end');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'code',
                'type',
                'billing_period_start',
                'billing_period_end',
                'source_reference',
            ]);
        });
    }
};
