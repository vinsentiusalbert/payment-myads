<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function form(): View
    {
        return view('checkout');
    }

    public function initiate(Request $request): JsonResponse|RedirectResponse
    {
        $request->merge([
            'name' => $request->input('name', $request->input('customer_name')),
            'email' => $request->input('email', $request->input('customer_email')),
            'phone' => $request->input('phone', $request->input('customer_phone')),
            'amount' => preg_replace('/\D/', '', (string) $request->input('amount', $request->input('transaction_amount'))),
            'payment_method' => $request->input('payment_method', 'qris'),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:150'],
            'phone' => ['required', 'regex:/^\d{10,14}$/'],
            'referral_code' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:1000'],
            'payment_method' => ['required', 'string', Rule::in(array_keys($this->paymentChannels()))],
            'product_category' => ['nullable', 'string', 'max:80'],
            'product_type' => ['nullable', 'string', 'max:80'],
            'product_detail' => ['nullable', 'string', 'max:180'],
        ]);

        $validated['tax_amount'] = $this->calculatePpn((int) $validated['amount']);
        $validated['grand_total_amount'] = (int) $validated['amount'] + $validated['tax_amount'];
        $channelCode = $this->paymentChannels()[$validated['payment_method']];
        $createdAt = now();
        $transactionId = $createdAt->getTimestampMs().random_int(1000, 9999);
        $expiresAt = $createdAt->copy()->addMinutes(15);

        $payload = [
            'transactionId' => $transactionId,
            'customerPhone' => $validated['phone'],
            'customerEmail' => $validated['email'],
            'customerName' => $validated['name'],
            'transactionAmount' => $validated['grand_total_amount'],
            'transactionExpire' => $expiresAt->toDateTimeString(),
            'productCategory' => $validated['product_category'] ?? 'MYADS2',
            'productType' => $validated['product_type'] ?? 'Recharge Coin',
            'productDetail' => $validated['product_detail'] ?? 'Test',
        ];

        try {
            $gatewayResponse = $this->initiateGatewayPayment($payload, $channelCode);
        } catch (RequestException $exception) {
            $gatewayResponse = $exception->response?->json() ?? [
                'success' => false,
                'message' => $exception->getMessage(),
            ];

            $transaction = $this->saveTransaction(
                validated: $validated,
                payload: $payload,
                gatewayResponse: $gatewayResponse,
                createdAt: $createdAt,
                expiresAt: $expiresAt,
                channelCode: $channelCode,
                status: 'FAILED'
            );

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $gatewayResponse['message'] ?? 'Payment gateway rejected the request',
                'data' => [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'gateway_response' => $gatewayResponse,
                    ],
                ], $exception->response?->status() ?? 502);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'amount' => $gatewayResponse['message'] ?? 'Payment gateway rejected the request',
                ]);
        }

        $pgData = $this->extractGatewayData($gatewayResponse);

        $transaction = $this->saveTransaction(
            validated: $validated,
            payload: $payload,
            gatewayResponse: $gatewayResponse,
            createdAt: $createdAt,
            expiresAt: $expiresAt,
            channelCode: $channelCode,
            pgData: $pgData
        );

        $request->session()->put('payment_transaction_id', $transaction->transaction_id);

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data' => [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'payment_code' => $transaction->payment_code,
                    'qris_url' => $transaction->qris_url,
                    'transaction_expire' => $this->formatWib($transaction->transaction_expire),
                    'transaction_amount' => $transaction->transaction_amount,
                    'tax_amount' => $transaction->tax_amount,
                    'grand_total_amount' => $transaction->grand_total_amount,
                    'redirect_url' => $transaction->redirect_url,
                    'gateway_response' => $transaction->gateway_response,
                ],
            ]);
        }

        if (
            $validated['payment_method'] !== 'qris'
            && $this->isExternalPaymentUrl($transaction->redirect_url)
        ) {
            return redirect()->away($transaction->redirect_url);
        }

        return redirect()->route('payment.show');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $transactionId = $request->session()->get('payment_transaction_id');

        if (! $transactionId) {
            return redirect()->route('checkout.form');
        }

        $payment = PaymentTransaction::where('transaction_id', $transactionId)->first();

        if (! $payment) {
            return redirect()->route('checkout.form');
        }

        return view('payment', [
            'payment' => $payment,
        ]);
    }

    public function qris(string $transactionId): Response|RedirectResponse
    {
        $payment = PaymentTransaction::where('transaction_id', $transactionId)->firstOrFail();

        abort_if(! $payment->qris_url, 404);

        if ($this->isDirectImageSource($payment->qris_url)) {
            return redirect()->away($payment->qris_url);
        }

        $result = (new Builder(
            writer: new PngWriter(),
            data: $payment->qris_url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 240,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        ))->build();

        $image = imagecreatefromstring($result->getString());
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($canvas, 255, 255, 255);

        imagefilledrectangle($canvas, 0, 0, imagesx($image), imagesy($image), $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, 92);
        $jpg = ob_get_clean();

        imagedestroy($image);
        imagedestroy($canvas);

        return response($jpg, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function continueAfterPayment(Request $request, string $transactionId): RedirectResponse
    {
        $payment = PaymentTransaction::where('transaction_id', $transactionId)->firstOrFail();

        $request->session()->put('payment_transaction_id', $payment->transaction_id);

        return redirect()->route('payment.show');
    }

    public function callback(Request $request): JsonResponse
    {
        Log::info('Payment callback request received', [
            'payload' => $request->all(),
        ]);

        $validated = $request->validate([
            'transaction_id' => ['required', 'string'],
            'transaction_status' => ['required', 'string'],
            'transaction_message' => ['nullable', 'string'],
            'payment_code' => ['nullable', 'string'],
            'transaction_date' => ['nullable', 'date'],
            'transaction_expire' => ['nullable', 'date'],
            'transaction_amount' => ['nullable', 'numeric'],
            'channel_code' => ['nullable', 'string'],
            'insert_id' => ['nullable', 'string'],
        ]);

        $payment = PaymentTransaction::where('transaction_id', $validated['transaction_id'])->first();

        if (! $payment) {
            $response = [
                'success' => false,
                'message' => 'Transaction not found',
            ];

            Log::warning('Payment callback response', [
                'transaction_id' => $validated['transaction_id'],
                'response' => $response,
                'status_code' => 404,
            ]);

            return response()->json($response, 404);
        }

        if ($payment->callback_payload !== null && $payment->status === 'SUCCESS') {
            $response = [
                'success' => false,
                'message' => 'Callback already processed for this successful transaction',
                'data' => [
                    'transaction_id' => $payment->transaction_id,
                    'status' => $payment->status,
                    'processed_at' => optional($payment->payment_date)->toISOString(),
                ],
            ];

            Log::warning('Payment callback response', [
                'transaction_id' => $payment->transaction_id,
                'response' => $response,
                'status_code' => 409,
            ]);

            return response()->json($response, 409);
        }

        $status = $validated['transaction_status'] === '00' ? 'SUCCESS' : 'FAILED';
        $transactionDate = isset($validated['transaction_date'])
            ? Carbon::parse($validated['transaction_date'])
            : null;

        $payment->update([
            'status' => $status,
            'payment_code' => $validated['payment_code'] ?? $payment->payment_code,
            'channel_code' => $validated['channel_code'] ?? $payment->channel_code,
            'grand_total_amount' => isset($validated['transaction_amount'])
                ? (int) $validated['transaction_amount']
                : $payment->grand_total_amount,
            'transaction_date' => $transactionDate ?? $payment->transaction_date,
            'transaction_expire' => isset($validated['transaction_expire'])
                ? Carbon::parse($validated['transaction_expire'])
                : $payment->transaction_expire,
            'payment_date' => $status === 'SUCCESS'
                ? ($transactionDate ?? now())
                : null,
            'callback_payload' => $request->all(),
            'gateway_response' => array_merge($payment->gateway_response ?? [], [
                'callback' => $request->all(),
            ]),
        ]);

        $response = [
            'success' => true,
            'message' => 'Payment status updated successfully',
            'data' => [
                'transaction_id' => $payment->transaction_id,
                'transaction_status' => $validated['transaction_status'],
                'internal_status' => $status,
                'transaction_message' => $validated['transaction_message'] ?? null,
                'payment_code' => $payment->payment_code,
                'channel_code' => $payment->channel_code,
                'insert_id' => $validated['insert_id'] ?? null,
                'transaction_date' => $this->formatWib($transactionDate),
                'transaction_expire' => $this->formatWib($validated['transaction_expire'] ?? null),
                'processed_at' => now()->toISOString(),
            ],
        ];

        Log::info('Payment callback response', [
            'transaction_id' => $payment->transaction_id,
            'response' => $response,
            'status_code' => 200,
        ]);

        return response()->json($response);
    }

    public function transactions(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 10), 100);
        $offset = (int) $request->query('offset', 0);

        $query = PaymentTransaction::query()
            ->latest()
            ->offset($offset)
            ->limit($limit);

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function detail(string $transactionId): JsonResponse
    {
        $payment = PaymentTransaction::where('transaction_id', $transactionId)->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if (Auth::check() && $payment->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    private function initiateGatewayPayment(array $payload, string $channelCode): array
    {
        $endpoint = config('services.payment_gateway.initiate_url');

        abort_if(
            ! is_string($endpoint)
            || blank($endpoint)
            || ! filter_var($endpoint, FILTER_VALIDATE_URL)
            || ! in_array(parse_url($endpoint, PHP_URL_SCHEME), ['http', 'https'], true),
            500,
            'Payment gateway URL is invalid. Please set PAYMENT_GATEWAY_INITIATE_URL or PAYMENT_GATEWAY_BASE_URL with http:// or https:// scheme.'
        );

        $response = Http::withHeaders($this->buildGatewayHeaders())
            ->acceptJson()
            ->post($endpoint, $this->buildGatewayPayload($payload, $channelCode));

        $response->throw();

        return $response->json();
    }

    private function saveTransaction(
        array $validated,
        array $payload,
        array $gatewayResponse,
        Carbon $createdAt,
        Carbon $expiresAt,
        string $channelCode,
        array $pgData = [],
        string $status = 'PENDING',
    ): PaymentTransaction {
        return PaymentTransaction::create([
            'id' => (string) Str::uuid(),
            'transaction_id' => $payload['transactionId'],
            'user_id' => Auth::id(),
            'channel_code' => $channelCode,
            'customer_phone' => $validated['phone'],
            'customer_email' => $validated['email'],
            'customer_name' => $validated['name'],
            'referral_code' => $validated['referral_code'] ?? null,
            'transaction_amount' => (int) $validated['amount'],
            'tax_amount' => $validated['tax_amount'],
            'grand_total_amount' => $validated['grand_total_amount'],
            'product_category' => $payload['productCategory'],
            'product_type' => $payload['productType'],
            'product_detail' => $payload['productDetail'],
            'status' => $status,
            'payment_code' => $pgData['payment_code'] ?? null,
            'qris_url' => $pgData['qris_url'] ?? null,
            'redirect_url' => $pgData['redirect_url'] ?? null,
            'transaction_date' => $createdAt,
            'transaction_expire' => $pgData['transaction_expire'] ?? $expiresAt,
            'gateway_response' => $gatewayResponse,
        ]);
    }

    private function formatWib(Carbon|string|null $dateTime): ?string
    {
        if (! $dateTime) {
            return null;
        }

        return Carbon::parse($dateTime)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s').' WIB';
    }

    private function calculatePpn(int $dpp): int
    {
        return (int) round(($dpp * 11 / 12) * 0.12);
    }

    private function extractGatewayData(array $gatewayResponse): array
    {
        return [
            'payment_code' => data_get($gatewayResponse, 'data.payment_code')
                ?? data_get($gatewayResponse, 'payment_code')
                ?? data_get($gatewayResponse, 'data.paymentCode')
                ?? data_get($gatewayResponse, 'paymentCode'),
            'qris_url' => data_get($gatewayResponse, 'data.qris_url')
                ?? data_get($gatewayResponse, 'qris_url')
                ?? data_get($gatewayResponse, 'data.qrisUrl')
                ?? data_get($gatewayResponse, 'qrisUrl')
                ?? data_get($gatewayResponse, 'data.qris')
                ?? data_get($gatewayResponse, 'qris')
                ?? data_get($gatewayResponse, 'data.qr_code')
                ?? data_get($gatewayResponse, 'qr_code')
                ?? data_get($gatewayResponse, 'data.qrCode')
                ?? data_get($gatewayResponse, 'qrCode'),
            'redirect_url' => data_get($gatewayResponse, 'data.redirect_url')
                ?? data_get($gatewayResponse, 'redirect_url')
                ?? data_get($gatewayResponse, 'data.redirectUrl')
                ?? data_get($gatewayResponse, 'redirectUrl'),
            'transaction_expire' => data_get($gatewayResponse, 'data.transaction_expire')
                ?? data_get($gatewayResponse, 'transaction_expire')
                ?? data_get($gatewayResponse, 'data.transactionExpire')
                ?? data_get($gatewayResponse, 'transactionExpire'),
        ];
    }

    private function isDirectImageSource(?string $qris): bool
    {
        if (! $qris) {
            return false;
        }

        return str_starts_with($qris, 'http://')
            || str_starts_with($qris, 'https://')
            || str_starts_with($qris, 'data:image/');
    }

    private function isExternalPaymentUrl(?string $url): bool
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    private function buildGatewayPayload(array $payload, string $channelCode): array
    {
        return [
            'channel_code' => $channelCode,
            'transaction_id' => $payload['transactionId'],
            'customer_phone' => $payload['customerPhone'],
            'customer_email' => $payload['customerEmail'],
            'customer_name' => $payload['customerName'],
            'transaction_amount' => $payload['transactionAmount'],
            'product_category' => $payload['productCategory'],
            'product_type' => $payload['productType'],
            'product_detail' => $payload['productDetail'],
        ];
    }

    private function paymentChannels(): array
    {
        return array_filter(
            config('services.payment_gateway.channels', []),
            fn ($channelCode) => filled($channelCode)
        );
    }

    private function buildGatewayHeaders(): array
    {
        $date = now('Asia/Jakarta')->format('D, d M Y H:i:s O');
        $appId = (string) config('services.payment_gateway.app_id');
        $clientKey = (string) config('services.payment_gateway.client_key');
        $secretKey = (string) config('services.payment_gateway.secret_key');

        return [
            'X-Date' => $date,
            'X-App-ID' => $this->encryptGatewayHeader($appId),
            'X-Client-Key' => $this->encryptGatewayHeader($clientKey),
            'X-Signature' => hash('sha256', $date.$appId.$clientKey.$secretKey),
        ];
    }

    private function encryptGatewayHeader(string $value): string
    {
        $now = now('Asia/Jakarta');
        $iv = sprintf(
            '8%s9%s7%s8%s%s',
            $now->format('Y'),
            $now->format('m'),
            $now->format('d'),
            $now->format('H'),
            $now->format('i')
        );

        $encrypted = openssl_encrypt(
            $value,
            'AES-256-CBC',
            substr((string) config('services.payment_gateway.secret_key'), 0, 32),
            OPENSSL_RAW_DATA,
            substr($iv, 0, 16)
        );

        return base64_encode($encrypted);
    }

}
