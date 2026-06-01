<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Lengkapi Data Anda');
        $response->assertSee('Amount');
    }

    public function test_checkout_redirects_to_payment_page(): void
    {
        $this->travelTo(now()->startOfSecond());

        $response = $this->post('/checkout', [
            'name' => 'Budi Santoso',
            'email' => 'budi@myads.id',
            'phone' => '081234567890',
            'amount' => 100000,
        ]);

        $response->assertRedirect('/payment');

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('Pilih Metode Pembayaran')
            ->assertSee('Rp 100.000');

        $this->assertDatabaseHas('payment_transactions', [
            'customer_email' => 'budi@myads.id',
            'transaction_amount' => 100000,
            'status' => 'PENDING',
        ]);

        $transaction = \App\Models\PaymentTransaction::first();

        $this->assertTrue($transaction->transaction_date->copy()->addMinutes(15)->equalTo($transaction->transaction_expire));
        $this->assertSame('Budi Santoso', $transaction->request_payload['name']);
        $this->assertSame('budi@myads.id', $transaction->gateway_payload['customer_email']);
        $this->assertSame(100000, $transaction->gateway_payload['transaction_amount']);
    }

    public function test_checkout_requires_minimum_amount(): void
    {
        $response = $this->from('/')->post('/checkout', [
            'name' => 'Budi Santoso',
            'email' => 'budi@myads.id',
            'phone' => '081234567890',
            'amount' => 500,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('amount');
    }

    public function test_checkout_accepts_formatted_amount(): void
    {
        $response = $this->post('/checkout', [
            'name' => 'Budi Santoso',
            'email' => 'budi@myads.id',
            'phone' => '081234567890',
            'amount' => '250.000',
        ]);

        $response->assertRedirect('/payment');

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('Rp 250.000');
    }

    public function test_api_can_initiate_payment_using_gateway_payload_names(): void
    {
        $response = $this->postJson('/api/payment/initiate', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@myads.id',
            'customer_phone' => '081234567890',
            'transaction_amount' => 150000,
            'product_category' => 'MYADS',
            'product_type' => 'ADVERTISEMENT',
            'product_detail' => 'Advertisement Payment',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction_amount', 150000);
    }

    public function test_callback_updates_payment_status(): void
    {
        $initiate = $this->postJson('/api/payment/initiate', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@myads.id',
            'customer_phone' => '081234567890',
            'transaction_amount' => 150000,
        ]);

        $transactionId = $initiate->json('data.transaction_id');

        $response = $this->postJson('/api/payment/callback', [
            'transaction_id' => $transactionId,
            'transaction_status' => '00',
            'payment_code' => 'QRIS-'.$transactionId,
            'transaction_date' => now()->toDateTimeString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.internal_status', 'SUCCESS');

        $this->assertDatabaseHas('payment_transactions', [
            'transaction_id' => $transactionId,
            'status' => 'SUCCESS',
        ]);

        $this->assertSame(
            '00',
            \App\Models\PaymentTransaction::where('transaction_id', $transactionId)->first()->callback_payload['transaction_status']
        );
    }

    public function test_payment_qris_fallback_returns_jpg_qr_image(): void
    {
        $initiate = $this->postJson('/api/payment/initiate', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@myads.id',
            'customer_phone' => '081234567890',
            'transaction_amount' => 150000,
        ]);

        $transactionId = $initiate->json('data.transaction_id');

        $response = $this->get("/payment/{$transactionId}/qris.jpg");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
    }
}
