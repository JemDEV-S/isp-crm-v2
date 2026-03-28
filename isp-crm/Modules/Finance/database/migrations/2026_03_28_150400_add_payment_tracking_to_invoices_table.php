<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('total_paid', 10, 2)->default(0)->after('total');
            $table->decimal('balance_due', 10, 2)->default(0)->after('total_paid');
        });

        // Inicializar balance_due = total para facturas existentes
        DB::statement('UPDATE invoices SET balance_due = total WHERE status != ?', ['paid']);
        DB::statement('UPDATE invoices SET total_paid = total, balance_due = 0 WHERE status = ?', ['paid']);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['total_paid', 'balance_due']);
        });
    }
};
