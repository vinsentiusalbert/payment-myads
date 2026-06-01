<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->json('request_payload')->nullable()->after('payment_date');
            $table->json('gateway_payload')->nullable()->after('request_payload');
            $table->json('callback_payload')->nullable()->after('gateway_response');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'request_payload',
                'gateway_payload',
                'callback_payload',
            ]);
        });
    }
};
