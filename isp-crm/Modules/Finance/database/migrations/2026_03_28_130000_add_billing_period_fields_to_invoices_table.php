<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('billing_period', 7)->nullable()->after('type');
            $table->date('period_start')->nullable()->after('billing_period');
            $table->date('period_end')->nullable()->after('period_start');
            $table->json('calculation_snapshot')->nullable()->after('metadata');
            $table->string('generation_source', 20)->default('scheduled')->after('calculation_snapshot');
            $table->string('external_tax_status', 30)->nullable()->after('generation_source');
            $table->foreignId('issued_by_job_run_id')->nullable()->after('external_tax_status')
                ->constrained('billing_job_runs')->nullOnDelete();

            $table->unique(['subscription_id', 'billing_period', 'type'], 'invoices_subscription_period_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_subscription_period_type_unique');
            $table->dropConstrainedForeignId('issued_by_job_run_id');
            $table->dropColumn([
                'billing_period',
                'period_start',
                'period_end',
                'calculation_snapshot',
                'generation_source',
                'external_tax_status',
            ]);
        });
    }
};
