<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:25'],
            'amount' => ['required', 'integer', 'min:1000'],
            'product_category' => ['nullable', 'string', 'max:80'],
            'product_type' => ['nullable', 'string', 'max:80'],
            'product_detail' => ['nullable', 'string', 'max:180'],
        ]);

        $createdAt = now();
        $transactionId = 'TEST'.substr($createdAt->getTimestampMs().random_int(10, 99), -10);
        $expiresAt = $createdAt->copy()->addMinutes(15);

        $payload = [
            'transactionId' => $transactionId,
            'customerPhone' => $validated['phone'],
            'customerEmail' => $validated['email'],
            'customerName' => $validated['name'],
            'transactionAmount' => (int) $validated['amount'],
            'productCategory' => $validated['product_category'] ?? 'MYADS',
            'productType' => $validated['product_type'] ?? 'ADVERTISEMENT',
            'productDetail' => $validated['product_detail'] ?? 'Advertisement Payment',
        ];
        $gatewayPayload = $this->buildGatewayPayload($payload);

        $gatewayResponse = $this->initiateGatewayPayment($payload, $expiresAt);
        $pgData = $gatewayResponse['data'] ?? [];

        $transaction = PaymentTransaction::create([
            'id' => (string) Str::uuid(),
            'transaction_id' => $transactionId,
            'user_id' => Auth::id(),
            'channel_code' => config('services.payment_gateway.channel_code'),
            'customer_phone' => $validated['phone'],
            'customer_email' => $validated['email'],
            'customer_name' => $validated['name'],
            'transaction_amount' => (int) $validated['amount'],
            'product_category' => $payload['productCategory'],
            'product_type' => $payload['productType'],
            'product_detail' => $payload['productDetail'],
            'status' => 'PENDING',
            'payment_code' => $pgData['payment_code'] ?? null,
            'qris_url' => $pgData['qris_url'] ?? null,
            'redirect_url' => $pgData['redirect_url'] ?? null,
            'transaction_date' => $createdAt,
            'transaction_expire' => $pgData['transaction_expire'] ?? $expiresAt,
            'request_payload' => $request->except('_token'),
            'gateway_payload' => $gatewayPayload,
            'gateway_response' => $gatewayResponse,
        ]);

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
                    'transaction_expire' => optional($transaction->transaction_expire)->toDateTimeString(),
                    'transaction_amount' => $transaction->transaction_amount,
                    'redirect_url' => $transaction->redirect_url,
                    'gateway_response' => $transaction->gateway_response,
                ],
            ]);
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

    public function qris(string $transactionId): Response
    {
        $payment = PaymentTransaction::where('transaction_id', $transactionId)->firstOrFail();

        $qrisPayload = $payment->qris_url ?: $payment->payment_code ?: $payment->transaction_id;

        $result = (new Builder(
            writer: new PngWriter(),
            data: $qrisPayload,
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

    public function callback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'string'],
            'transaction_status' => ['required', 'string'],
            'transaction_message' => ['nullable', 'string'],
            'payment_code' => ['nullable', 'string'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $payment = PaymentTransaction::where('transaction_id', $validated['transaction_id'])->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        $status = $validated['transaction_status'] === '00' ? 'SUCCESS' : 'FAILED';

        $payment->update([
            'status' => $status,
            'payment_code' => $validated['payment_code'] ?? $payment->payment_code,
            'payment_date' => $status === 'SUCCESS'
                ? Carbon::parse($validated['transaction_date'] ?? now())
                : null,
            'callback_payload' => $request->all(),
            'gateway_response' => array_merge($payment->gateway_response ?? [], [
                'callback' => $request->all(),
            ]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully',
            'data' => [
                'transaction_id' => $payment->transaction_id,
                'transaction_status' => $validated['transaction_status'],
                'internal_status' => $status,
                'payment_code' => $payment->payment_code,
                'transaction_date' => $validated['transaction_date'] ?? null,
                'processed_at' => now()->toISOString(),
            ],
        ]);
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

    private function initiateGatewayPayment(array $payload, Carbon $expiresAt): array
    {
        $endpoint = config('services.payment_gateway.initiate_url');

        if (! $endpoint) {
            return $this->fakeGatewayResponse($payload, $expiresAt);
        }

        $response = Http::withHeaders([
                'X-Secret-Key' => (string) config('services.payment_gateway.secret_key'),
                'X-Client-Key' => (string) config('services.payment_gateway.client_key'),
                'X-App-Id' => (string) config('services.payment_gateway.app_id'),
            ])
            ->acceptJson()
            ->post($endpoint, $this->buildGatewayPayload($payload));

        $response->throw();

        return $response->json();
    }

    private function buildGatewayPayload(array $payload): array
    {
        return [
            'transaction_id' => $payload['transactionId'],
            'customer_phone' => $payload['customerPhone'],
            'customer_email' => $payload['customerEmail'],
            'customer_name' => $payload['customerName'],
            'transaction_amount' => $payload['transactionAmount'],
            'product_category' => $payload['productCategory'],
            'product_type' => $payload['productType'],
            'product_detail' => $payload['productDetail'],
            'secret_key' => config('services.payment_gateway.secret_key'),
            'client_key' => config('services.payment_gateway.client_key'),
            'app_id' => config('services.payment_gateway.app_id'),
            'channel_code' => config('services.payment_gateway.channel_code'),
        ];
    }

    private function fakeGatewayResponse(array $payload, Carbon $expiresAt): array
    {
        return [
            'success' => true,
            'message' => 'Payment initiated successfully',
            'data' => [
                'transaction_id' => $payload['transactionId'],
                'payment_code' => 'QRIS-'.$payload['transactionId'],
                'qris_url' => null,
                'transaction_expire' => $expiresAt->toDateTimeString(),
                'transaction_amount' => $payload['transactionAmount'],
                'redirect_url' => null,
            ],
        ];
    }
}
