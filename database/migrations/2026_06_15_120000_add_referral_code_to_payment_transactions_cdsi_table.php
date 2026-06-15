<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions_cdsi', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->after('customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions_cdsi', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });
    }
};
