<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions_cdsi', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_amount')->default(0)->after('transaction_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions_cdsi', function (Blueprint $table) {
            $table->dropColumn('tax_amount');
        });
    }
};
