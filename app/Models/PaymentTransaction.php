<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'transaction_id',
        'user_id',
        'channel_code',
        'customer_phone',
        'customer_email',
        'customer_name',
        'transaction_amount',
        'tax_amount',
        'grand_total_amount',
        'product_category',
        'product_type',
        'product_detail',
        'status',
        'payment_code',
        'qris_url',
        'redirect_url',
        'transaction_date',
        'transaction_expire',
        'payment_date',
        'gateway_response',
        'callback_payload',
    ];

    protected function casts(): array
    {
        return [
            'transaction_amount' => 'integer',
            'tax_amount' => 'integer',
            'grand_total_amount' => 'integer',
            'transaction_date' => 'datetime',
            'transaction_expire' => 'datetime',
            'payment_date' => 'datetime',
            'gateway_response' => 'array',
            'callback_payload' => 'array',
        ];
    }
}
