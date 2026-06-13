<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilih Metode Pembayaran</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/myads_colour_02.ico') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f4f7fb;
            color: #111827;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .modal {
            width: min(92vw, 420px);
            background: #ffffff;
            border: 1px solid #d9e0ea;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .13);
            padding: 28px;
            position: relative;
        }
        .close {
            position: absolute;
            top: 16px;
            right: 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 28px;
            line-height: 1;
        }
        .brand-logo {
            display: block;
            width: 122px;
            height: auto;
            margin: 0 auto 16px;
        }
        h1 {
            margin: 0 26px 10px;
            text-align: center;
            font-size: 22px;
            line-height: 1.2;
        }
        .timer {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }
        .timer span { color: #126bff; }
        .summary {
            margin: 20px 0 16px;
            border: 1px solid #e3e9f2;
            border-radius: 8px;
            padding: 12px 14px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
            background: #fbfdff;
        }
        .summary strong { color: #111827; }
        .status {
            display: inline-flex;
            align-items: center;
            height: 24px;
            margin-top: 8px;
            padding: 0 10px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            font-size: 12px;
            font-weight: 800;
        }
        .tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin: 20px 0 28px;
            border-bottom: 1px solid #dbe3ee;
        }
        .tabs.single {
            grid-template-columns: 1fr;
        }
        .tab {
            padding: 0 8px 14px;
            text-align: center;
            color: #334155;
            font-size: 14px;
            font-weight: 800;
        }
        .tab.active {
            color: #126bff;
            border-bottom: 3px solid #126bff;
            margin-bottom: -2px;
        }
        .instruction {
            margin: 0 auto 10px;
            max-width: 275px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
            line-height: 1.4;
            font-weight: 600;
        }
        .qr-box {
            width: 234px;
            height: 220px;
            display: grid;
            place-items: center;
            margin: 0 auto 18px;
            border: 1px solid #e0e7f0;
            border-radius: 8px;
            background: #ffffff;
            position: relative;
        }
        .qris-image {
            max-width: 188px;
            max-height: 188px;
            object-fit: contain;
            opacity: 0;
            transition: opacity .18s ease;
        }
        .qris-image.is-loaded {
            opacity: 1;
        }
        .qris-link {
            display: inline-grid;
            place-items: center;
            line-height: 0;
        }
        .qr-loading {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            gap: 10px;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            background: #ffffff;
            border-radius: 8px;
        }
        .qr-spinner {
            width: 34px;
            height: 34px;
            border: 4px solid #dbeafe;
            border-top-color: #126bff;
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }
        .qr-loading.is-hidden {
            display: none;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .section-label {
            margin: 0 0 10px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }
        .method {
            width: 100%;
            height: 48px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            color: #111827;
            background: #fff;
            font-weight: 800;
            box-shadow: 0 1px 5px rgba(15, 23, 42, .05);
        }
        .method svg:first-child { color: #126bff; }
        .method svg:last-child { margin-left: auto; color: #94a3b8; }
        .note {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding: 12px;
            border: 1px solid #b9d7ff;
            border-radius: 7px;
            background: #eaf3ff;
            color: #126bff;
            font-size: 12px;
            line-height: 1.45;
            font-weight: 700;
        }
        .payment-code {
            margin: -6px 0 18px;
            text-align: center;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            word-break: break-word;
        }
        .qris-empty {
            padding: 18px;
            text-align: center;
            color: #dc2626;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.4;
        }
        .download-qris {
            width: 100%;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin: -4px 0 18px;
            border: 1px solid #126bff;
            border-radius: 7px;
            color: #126bff;
            background: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
        }
        .icon { width: 20px; height: 20px; flex: 0 0 auto; }
        @media (max-width: 480px) {
            .modal { padding: 24px 18px; }
            .brand-logo { margin-top: 10px; }
            h1 { font-size: 20px; }
            .qr-box { width: 224px; }
        }
    </style>
</head>
<body>
    <main class="modal">
        <a href="{{ route('checkout.form') }}" class="close" aria-label="Tutup">&times;</a>
        <img class="brand-logo" src="{{ asset('assets/myads_colour_02.png') }}" alt="MyAds">
        <h1>Pilih Metode Pembayaran</h1>
        <div class="timer">Selesaikan pembayaran sebelum <span>{{ $payment->transaction_expire ? $payment->transaction_expire->timezone('Asia/Jakarta')->format('d M Y H:i').' WIB' : '-' }}</span></div>

        <div class="summary">
            <div><strong>{{ $payment->customer_name }}</strong></div>
            <div>{{ $payment->customer_email }} - {{ $payment->customer_phone }}</div>
            <div>Transaction ID: <strong>{{ $payment->transaction_id }}</strong></div>
            <div>Amount: <strong>Rp {{ number_format($payment->transaction_amount, 0, ',', '.') }}</strong></div>
            <div>PPN: <strong>Rp {{ number_format($payment->tax_amount, 0, ',', '.') }}</strong></div>
            <div>Grand Total: <strong>Rp {{ number_format($payment->grand_total_amount, 0, ',', '.') }}</strong></div>
            <div class="status">{{ $payment->status }}</div>
        </div>

        @php
            $paymentChannels = config('services.payment_gateway.channels', []);
            $selectedMethod = array_search($payment->channel_code, $paymentChannels, true) ?: 'qris';
            $bankNames = ['bsi' => 'BSI', 'bni' => 'BNI', 'permata' => 'Permata', 'mandiri' => 'Mandiri'];
            $isQris = $selectedMethod === 'qris';
        @endphp

        <div class="tabs {{ $isQris ? 'single' : '' }}" role="tablist">
            <div class="tab {{ $isQris ? 'active' : '' }}">QRIS / Barcode</div>
            @unless ($isQris)
                <div class="tab active">Virtual Account</div>
            @endunless
        </div>

        @if ($isQris)
            <p class="instruction">Scan kode QR berikut dengan aplikasi pembayaran Anda</p>
            <div class="qr-box" aria-label="QRIS pembayaran">
                @if ($payment->qris_url)
                    <div class="qr-loading" id="qrLoading" aria-live="polite">
                        <div class="qr-spinner" aria-hidden="true"></div>
                        Memuat QRIS
                    </div>
                    <a class="qris-link" href="{{ route('payment.continue', $payment->transaction_id) }}" aria-label="Lanjut ke MyAds">
                        <img class="qris-image" id="qrisImage" src="{{ str_starts_with($payment->qris_url, 'http://') || str_starts_with($payment->qris_url, 'https://') || str_starts_with($payment->qris_url, 'data:image/') ? $payment->qris_url : route('payment.qris', $payment->transaction_id) }}" alt="QRIS {{ $payment->transaction_id }}">
                    </a>
                @else
                    <div class="qris-empty">QRIS dari API belum tersedia.</div>
                @endif
            </div>
            @if ($payment->payment_code)
                <div class="payment-code">Kode pembayaran: {{ $payment->payment_code }}</div>
            @endif
            @if ($payment->qris_url)
                <a class="download-qris" href="{{ route('payment.qris', $payment->transaction_id) }}" download="qris-{{ $payment->transaction_id }}.jpg">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                    Download QRIS
                </a>
            @endif
        @else
            <p class="instruction">Bayar melalui Virtual Account {{ $bankNames[$selectedMethod] ?? strtoupper($selectedMethod) }}</p>
            <div class="qr-box" aria-label="Nomor Virtual Account">
                @if ($payment->payment_code)
                    <div style="padding:20px;text-align:center">
                        <div class="section-label">Nomor Virtual Account</div>
                        <strong style="font-size:20px;word-break:break-all">{{ $payment->payment_code }}</strong>
                    </div>
                @else
                    <div class="qris-empty">Nomor Virtual Account dari API belum tersedia.</div>
                @endif
            </div>
        @endif

        <div class="note">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            Setelah pembayaran berhasil, sistem akan otomatis menginformasikan kepada kami.
        </div>
    </main>
    <script>
        const transactionId = @json($payment->transaction_id);
        const redirectUrl = 'https://myads.telkomsel.com/login';
        const qrisImage = document.getElementById('qrisImage');
        const qrLoading = document.getElementById('qrLoading');

        function finishQrLoading() {
            qrisImage?.classList.add('is-loaded');
            qrLoading?.classList.add('is-hidden');
        }

        if (qrisImage) {
            if (qrisImage.complete && qrisImage.naturalWidth > 0) {
                finishQrLoading();
            } else {
                qrisImage.addEventListener('load', finishQrLoading, { once: true });
                qrisImage.addEventListener('error', finishQrLoading, { once: true });
            }
        }

        async function checkPaymentStatus() {
            try {
                const response = await fetch(`/api/payment/${encodeURIComponent(transactionId)}`, {
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();

                if (result?.data?.status === 'SUCCESS') {
                    window.location.href = redirectUrl;
                }
            } catch (error) {
            }
        }

        @if ($payment->status === 'SUCCESS')
            window.location.href = redirectUrl;
        @else
            setInterval(checkPaymentStatus, 3000);
        @endif
    </script>
</body>
</html>
