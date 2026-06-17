<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions_cdsi', function (Blueprint $table) {
            $table->timestamp('success_email_sent_at')->nullable()->after('payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions_cdsi', function (Blueprint $table) {
            $table->dropColumn('success_email_sent_at');
        });
    }
};
