<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions_cdsi', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('payment_transactions_cdsi', 'request_payload') ? 'request_payload' : null,
                Schema::hasColumn('payment_transactions_cdsi', 'gateway_payload') ? 'gateway_payload' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions_cdsi', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_transactions_cdsi', 'request_payload')) {
                $table->json('request_payload')->nullable()->after('payment_date');
            }

            if (! Schema::hasColumn('payment_transactions_cdsi', 'gateway_payload')) {
                $table->json('gateway_payload')->nullable()->after('request_payload');
            }
        });
    }
};
