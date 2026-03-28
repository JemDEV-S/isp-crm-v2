<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->json('commercial_snapshot')->nullable()->after('promotion_id');
            $table->timestamp('terms_accepted_at')->nullable()->after('commercial_snapshot');
            $table->string('acceptance_method', 30)->nullable()->after('terms_accepted_at');
            $table->string('acceptance_ip', 45)->nullable()->after('acceptance_method');
            $table->text('acceptance_user_agent')->nullable()->after('acceptance_ip');

            $table->index('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['terms_accepted_at']);
            $table->dropColumn([
                'commercial_snapshot',
                'terms_accepted_at',
                'acceptance_method',
                'acceptance_ip',
                'acceptance_user_agent',
            ]);
        });
    }
};
