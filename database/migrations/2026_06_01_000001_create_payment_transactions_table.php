<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_id')->unique();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('channel_code')->nullable();
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->string('customer_name')->nullable();
            $table->unsignedBigInteger('transaction_amount');
            $table->string('product_category')->default('MYADS');
            $table->string('product_type')->default('ADVERTISEMENT');
            $table->string('product_detail')->default('Advertisement Payment');
            $table->string('status')->default('PENDING')->index();
            $table->string('payment_code')->nullable();
            $table->text('qris_url')->nullable();
            $table->text('redirect_url')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->timestamp('transaction_expire')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
