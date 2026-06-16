<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.payment_gateway.initiate_url' => 'https://api-payment.kinarya-tech.com/api/v1/payment']);
    }

    private function fakeGatewaySuccess(array $data = []): void
    {
        Http::fake([
            'api-payment.kinarya-tech.com/*' => Http::response([
                'status' => 200,
                'message' => 'Transaction success',
                'data' => array_merge([
                    'transaction_id' => '175758407509441071',
                    'transaction_date' => '2026-06-01 13:12:51',
                    'transaction_expire' => now()->addMinutes(15)->toDateTimeString(),
                    'transaction_amount' => 150000,
                    'payment_code' => '1234281311740293',
                    'channel_code' => 'DEVQRIS',
                    'redirect_url' => '',
                    'redirect_data' => null,
                    'qris_url' => 'https://devstag.pasarind.id:2446/api/qr/invoice?data=test',
                ], $data),
                'timestamp' => now()->toDateTimeString(),
            ], 200),
        ]);
    }

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Lengkapi Data Anda');
        $response->assertSee('Amount');
        $response->assertSee('PPN');
        $response->assertSee('Grand Total');
        $response->assertSee('QRIS');
        $response->assertSee('Virtual Account');
        $response->assertSee('BSI');
        $response->assertSee('BNI');
        $response->assertSee('Permata');
        $response->assertSee('Mandiri');
        $response->assertSee('Hubungi CS: +62 823-4718-9584');
        $response->assertSee('https://wa.me/6282347189584', false);
    }

    public function test_checkout_validates_email_and_phone_number_format(): void
    {
        $response = $this->from('/')->post('/checkout', [
            'name' => 'Budi Santoso',
            'email' => 'email-tidak-valid',
            'phone' => '08123abc',
            'amount' => 100000,
            'payment_method' => 'qris',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['email', 'phone']);
        Http::assertNothingSent();
    }

    public function test_checkout_uses_selected_virtual_account_channel(): void
    {
        config(['services.payment_gateway.channels.bni' => 'DEVBNI']);
        $redirectUrl = 'https://live.finpay.id/pg/payment/card/v2/access/test-token';
        $this->fakeGatewaySuccess([
            'channel_code' => 'DEVBNI',
            'qris_url' => null,
            'payment_code' => '88081234567890',
            'redirect_url' => $redirectUrl,
        ]);

        $response = $this->post('/checkout', [
            'name' => 'Budi Santoso',
            'email' => 'budi@myads.id',
            'phone' => '081234567890',
            'amount' => 100000,
            'payment_method' => 'bni',
        ]);

        $response->assertRedirect($redirectUrl);

        Http::assertSent(fn ($request) => $request['channel_code'] === 'DEVBNI');

        $this->assertDatabaseHas('payment_transactions', [
            'channel_code' => 'DEVBNI',
            'payment_code' => '88081234567890',
            'redirect_url' => $redirectUrl,
        ]);
    }

    public function test_virtual_account_without_redirect_url_falls_back_to_payment_page(): void
    {
        config(['services.payment_gateway.channels.bsi' => 'FINBSIVA']);
        $this->fakeGatewaySuccess([
            'channel_code' => 'FINBSIVA',
            'qris_url' => '',
            'payment_code' => '2987000003260376',
            'redirect_url' => '',
        ]);

        $response = $this->post('/checkout', [
            'name' => 'Budi Santoso',
            'email' => 'budi@myads.id',
            'phone' => '081234567890',
            'amount' => 17200,
            'payment_method' => 'bsi',
        ]);

        $response->assertRedirect('/payment');

        $this->followRedirects($response)
            ->assertSee('Virtual Account BSI')
            ->assertSee('2987000003260376');
    }

    public function test_checkout_redirects_to_payment_page(): void
    {
        $this->fakeGatewaySuccess(['transaction_amount' => 100000]);
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
            ->assertSee('Rp 100.000')
            ->assertSee('WIB');

        $this->assertDatabaseHas('payment_transactions', [
            'customer_email' => 'budi@myads.id',
            'transaction_amount' => 100000,
            'tax_amount' => 11000,
            'grand_total_amount' => 111000,
            'status' => 'PENDING',
        ]);

        Http::assertSent(fn ($request) => $request['transaction_amount'] === 111000);

        $transaction = \App\Models\PaymentTransaction::first();

        $this->assertTrue($transaction->transaction_date->copy()->addMinutes(15)->equalTo($transaction->transaction_expire));
        $this->assertSame('DEVQRIS', $transaction->channel_code);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('payment_transactions', 'request_payload'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('payment_transactions', 'gateway_payload'));
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
        $this->fakeGatewaySuccess(['transaction_amount' => 250000]);

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
        $this->fakeGatewaySuccess(['transaction_amount' => 150000]);

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

    public function test_api_qris_value_from_gateway_response_is_saved(): void
    {
        Http::fake([
            'api-payment.kinarya-tech.com/*' => Http::response([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data' => [
                    'transaction_id' => 'PG-123',
                    'payment_code' => 'PAY-123',
                    'qris_url' => '00020101021226680016ID.CO.QRIS.WWW01189360091100212345670203UME51440014ID.CO.QRIS.WWW0215ID20240123456780303UMI5204599953033605802ID5910MYADS TEST6007JAKARTA6105123456304ABCD',
                    'transaction_expire' => now()->addMinutes(15)->toDateTimeString(),
                    'transaction_amount' => 150000,
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/payment/initiate', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@myads.id',
            'customer_phone' => '081234567890',
            'transaction_amount' => 150000,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.payment_code', 'PAY-123');

        $transaction = \App\Models\PaymentTransaction::first();

        $this->assertSame('PAY-123', $transaction->payment_code);
        $this->assertStringStartsWith('000201', $transaction->qris_url);

        Http::assertSent(fn ($request) => $request->url() === 'https://api-payment.kinarya-tech.com/api/v1/payment'
            && $request->hasHeader('X-Date')
            && $request->hasHeader('X-App-ID')
            && $request->hasHeader('X-Client-Key')
            && $request->hasHeader('X-Signature')
            && ! isset($request['secretKey'])
            && ! isset($request['clientKey'])
            && ! isset($request['app_id']));
    }

    public function test_api_requires_qris_from_gateway_when_gateway_url_is_configured(): void
    {
        Http::fake([
            'api-payment.kinarya-tech.com/*' => Http::response([
                'success' => true,
                'data' => [
                    'payment_code' => null,
                    'qris_url' => null,
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/payment/initiate', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@myads.id',
            'customer_phone' => '081234567890',
            'transaction_amount' => 150000,
        ]);

        $response->assertOk();

        $transaction = \App\Models\PaymentTransaction::first();

        $this->assertNull($transaction->qris_url);
        $this->assertNull($transaction->payment_code);
    }

    public function test_gateway_error_response_is_saved_for_debugging(): void
    {
        Http::fake([
            'api-payment.kinarya-tech.com/*' => Http::response([
                'status' => 400,
                'message' => 'Invalid request',
                'error' => 'A4003',
            ], 400),
        ]);

        $response = $this->postJson('/api/payment/initiate', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@myads.id',
            'customer_phone' => '081234567890',
            'transaction_amount' => 150000,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.gateway_response.error', 'A4003');

        $transaction = \App\Models\PaymentTransaction::first();

        $this->assertSame('FAILED', $transaction->status);
        $this->assertSame('A4003', $transaction->gateway_response['error']);
    }

    public function test_checkout_returns_to_form_when_gateway_returns_invalid_request(): void
    {
        Http::fake([
            'api-payment.kinarya-tech.com/*' => Http::response([
                'status' => 400,
                'message' => 'Invalid request',
                'error' => 'A4003',
            ], 400),
        ]);

        $response = $this->post('/checkout', [
            'name' => 'Budi Santoso',
            'email' => 'budi@myads.id',
            'phone' => '081234567890',
            'amount' => 150000,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('amount');

        $transaction = \App\Models\PaymentTransaction::first();

        $this->assertSame('A4003', $transaction->gateway_response['error']);
    }

    public function test_callback_updates_payment_status(): void
    {
        $this->fakeGatewaySuccess(['transaction_amount' => 150000]);

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

    public function test_callback_example_payload_updates_transaction_to_success(): void
    {
        \App\Models\PaymentTransaction::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'transaction_id' => 'TEST2026051901',
            'channel_code' => 'DEVQRIS',
            'customer_phone' => '081311740293',
            'customer_email' => 'rianpr20@gmail.com',
            'customer_name' => 'Febriansyah Putra Ramadhan',
            'transaction_amount' => 10000,
            'product_category' => 'BAYARAJA',
            'product_type' => 'Recharge Coin',
            'product_detail' => 'Test',
            'status' => 'PENDING',
            'transaction_date' => '2026-05-19 08:40:16',
            'transaction_expire' => '2026-05-19 08:55:16',
            'gateway_response' => [],
        ]);

        $payload = [
            'transaction_id' => 'TEST2026051901',
            'transaction_date' => '2026-05-19T08:45:16+07:00',
            'transaction_expire' => '2026-05-19T08:55:16+07:00',
            'transaction_amount' => '10000',
            'transaction_status' => '00',
            'transaction_message' => 'Approved',
            'payment_code' => '1234081234567890',
            'channel_code' => 'DEVQRIS',
            'insert_id' => '56498',
        ];

        $response = $this->postJson('/api/payment/callback', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction_id', 'TEST2026051901')
            ->assertJsonPath('data.internal_status', 'SUCCESS')
            ->assertJsonPath('data.transaction_message', 'Approved')
            ->assertJsonPath('data.insert_id', '56498');

        $this->assertDatabaseHas('payment_transactions', [
            'transaction_id' => 'TEST2026051901',
            'status' => 'SUCCESS',
            'payment_code' => '1234081234567890',
            'channel_code' => 'DEVQRIS',
            'transaction_amount' => 10000,
        ]);

        $transaction = \App\Models\PaymentTransaction::where('transaction_id', 'TEST2026051901')->first();

        $this->assertSame('56498', $transaction->callback_payload['insert_id']);
        $this->assertSame('Approved', $transaction->gateway_response['callback']['transaction_message']);
        $this->assertNotNull($transaction->payment_date);
    }

    public function test_payment_qris_redirects_to_api_qris_url(): void
    {
        $this->fakeGatewaySuccess(['transaction_amount' => 150000]);

        $initiate = $this->postJson('/api/payment/initiate', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@myads.id',
            'customer_phone' => '081234567890',
            'transaction_amount' => 150000,
        ]);

        $transactionId = $initiate->json('data.transaction_id');

        $response = $this->get("/payment/{$transactionId}/qris.jpg");

        $response->assertRedirect('https://devstag.pasarind.id:2446/api/qr/invoice?data=test');
    }

    public function test_payment_page_uses_api_qris_when_gateway_qris_is_saved(): void
    {
        $this->fakeGatewaySuccess([
            'payment_code' => 'PAY-123',
            'qris_url' => '000201APIQRIS',
            'transaction_amount' => 150000,
        ]);

        $response = $this->post('/checkout', [
            'name' => 'Budi Santoso',
            'email' => 'budi@myads.id',
            'phone' => '081234567890',
            'amount' => 150000,
        ]);

        $transaction = \App\Models\PaymentTransaction::first();

        $this->assertSame('000201APIQRIS', $transaction->qris_url);

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee(route('payment.qris', $transaction->transaction_id))
            ->assertSee(route('payment.continue', $transaction->transaction_id))
            ->assertSee('https://myads.telkomsel.com/login')
            ->assertSee('Download QRIS')
            ->assertDontSee('Virtual Account');

        $this->get("/payment/{$transaction->transaction_id}/qris.jpg")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_payment_continue_waits_until_transaction_success(): void
    {
        \App\Models\PaymentTransaction::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'transaction_id' => 'WAIT2026060101',
            'channel_code' => 'DEVQRIS',
            'customer_phone' => '081311740293',
            'customer_email' => 'rianpr20@gmail.com',
            'customer_name' => 'Febriansyah Putra Ramadhan',
            'transaction_amount' => 10000,
            'product_category' => 'BAYARAJA',
            'product_type' => 'Recharge Coin',
            'product_detail' => 'Test',
            'status' => 'PENDING',
            'transaction_date' => now(),
            'transaction_expire' => now()->addMinutes(15),
            'gateway_response' => [],
        ]);

        $this->get('/payment/WAIT2026060101/continue')
            ->assertRedirect('/payment');
    }

    public function test_payment_continue_redirects_to_login_after_callback_success(): void
    {
        \App\Models\PaymentTransaction::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'transaction_id' => 'DONE2026060101',
            'channel_code' => 'DEVQRIS',
            'customer_phone' => '081311740293',
            'customer_email' => 'rianpr20@gmail.com',
            'customer_name' => 'Febriansyah Putra Ramadhan',
            'transaction_amount' => 10000,
            'product_category' => 'BAYARAJA',
            'product_type' => 'Recharge Coin',
            'product_detail' => 'Test',
            'status' => 'PENDING',
            'transaction_date' => now(),
            'transaction_expire' => now()->addMinutes(15),
            'gateway_response' => [],
        ]);

        $this->postJson('/api/payment/callback', [
            'transaction_id' => 'DONE2026060101',
            'transaction_status' => '00',
            'transaction_message' => 'Approved',
            'payment_code' => '1234081234567890',
            'transaction_date' => now()->toIso8601String(),
        ])->assertOk();

        $this->get('/payment/DONE2026060101/continue')
            ->assertRedirect('https://myads.telkomsel.com/login');
    }

    public function test_success_payment_page_shows_transaction_number_and_whatsapp_support(): void
    {
        \App\Models\PaymentTransaction::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'transaction_id' => 'SUCCESS2026060101',
            'channel_code' => 'DEVQRIS',
            'customer_phone' => '081311740293',
            'customer_email' => 'rianpr20@gmail.com',
            'customer_name' => 'Febriansyah Putra Ramadhan',
            'transaction_amount' => 10000,
            'tax_amount' => 1100,
            'grand_total_amount' => 11100,
            'product_category' => 'BAYARAJA',
            'product_type' => 'Recharge Coin',
            'product_detail' => 'Test',
            'status' => 'SUCCESS',
            'transaction_date' => now(),
            'transaction_expire' => now()->addMinutes(15),
            'payment_date' => now(),
            'gateway_response' => [],
        ]);

        $this->withSession(['payment_transaction_id' => 'SUCCESS2026060101'])
            ->get('/payment')
            ->assertOk()
            ->assertSee('Nomor Transaksi: SUCCESS2026060101')
            ->assertSee('Simpan nomor transaksi ini sebagai bukti pembayaran.')
            ->assertSee('Jika ada kendala, hubungi CS +62 823-4718-9584.')
            ->assertSee('https://wa.me/6282347189584', false)
            ->assertSee('Hubungi CS via WhatsApp');
    }

    public function test_root_url_can_receive_gateway_callback(): void
    {
        \App\Models\PaymentTransaction::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'transaction_id' => 'ROOT2026060101',
            'channel_code' => 'DEVQRIS',
            'customer_phone' => '081311740293',
            'customer_email' => 'rianpr20@gmail.com',
            'customer_name' => 'Febriansyah Putra Ramadhan',
            'transaction_amount' => 10000,
            'product_category' => 'BAYARAJA',
            'product_type' => 'Recharge Coin',
            'product_detail' => 'Test',
            'status' => 'PENDING',
            'transaction_date' => now(),
            'transaction_expire' => now()->addMinutes(15),
            'gateway_response' => [],
        ]);

        $this->postJson('/', [
            'transaction_id' => 'ROOT2026060101',
            'transaction_status' => '00',
            'transaction_message' => 'Approved',
            'payment_code' => '1234081234567890',
            'transaction_date' => now()->toIso8601String(),
        ])
            ->assertOk()
            ->assertJsonPath('data.internal_status', 'SUCCESS');

        $this->assertDatabaseHas('payment_transactions', [
            'transaction_id' => 'ROOT2026060101',
            'status' => 'SUCCESS',
        ]);
    }
}
