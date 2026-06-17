<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lengkapi Data Anda</title>
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
            width: min(92vw, 476px);
            background: #ffffff;
            border: 1px solid #d9e0ea;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .13);
            padding: 30px;
            position: relative;
        }
        .brand-logo {
            display: block;
            width: 128px;
            height: auto;
            margin: 4px auto 18px;
        }
        h1 {
            margin: 0 28px 18px;
            text-align: center;
            font-size: 24px;
            line-height: 1.2;
        }
        .subtitle {
            margin: 0 auto 28px;
            max-width: 330px;
            text-align: center;
            color: #64748b;
            font-size: 16px;
            line-height: 1.55;
        }
        label {
            display: block;
            margin: 0 0 10px;
            font-weight: 700;
            font-size: 14px;
        }
        .field { margin-bottom: 22px; }
        .required-mark {
            color: #dc2626;
            font-weight: 800;
        }
        .control {
            display: flex;
            align-items: center;
            gap: 14px;
            height: 52px;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            background: #fff;
            padding: 0 14px;
            box-shadow: 0 1px 5px rgba(15, 23, 42, .05);
        }
        .control:focus-within {
            border-color: #1d6cff;
            box-shadow: 0 0 0 3px rgba(29, 108, 255, .12);
        }
        .icon {
            width: 22px;
            height: 22px;
            color: #8090a5;
            flex: 0 0 auto;
        }
        .currency-prefix {
            width: 22px;
            color: #8090a5;
            flex: 0 0 auto;
            font-weight: 800;
            font-size: 15px;
            text-align: center;
        }
        input {
            width: 100%;
            height: 100%;
            border: 0;
            outline: 0;
            color: #111827;
            font: inherit;
            font-weight: 600;
            background: transparent;
        }
        input::placeholder { color: #a3adbd; font-weight: 600; }
        .error {
            margin-top: 8px;
            color: #dc2626;
            font-size: 13px;
        }
        .field-note {
            margin-top: 8px;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }
        .payment-options { display: grid; gap: 10px; }
        .payment-option {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 52px;
            padding: 12px 14px;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 1px 5px rgba(15, 23, 42, .05);
        }
        .payment-option:has(input:checked), .bank-option:has(input:checked) {
            border-color: #1d6cff;
            color: #0967f6;
            background: #f5f9ff;
        }
        .payment-option input, .bank-option input {
            width: 18px;
            height: 18px;
            margin: 0;
            accent-color: #0967f6;
        }
        .payment-option .icon { color: #0967f6; }
        .bank-options {
            display: none;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding-left: 30px;
        }
        .bank-options.is-visible { display: grid; }
        .bank-option {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 8px 10px;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 800;
        }
        .bank-logo {
            width: 30px;
            height: 22px;
            flex: 0 0 30px;
            object-fit: contain;
        }
        button {
            width: 100%;
            height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 0;
            border-radius: 6px;
            background: #0967f6;
            color: #ffffff;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(9, 103, 246, .26);
        }
        .secure {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-top: 24px;
            color: #7c8da3;
            font-size: 14px;
            font-weight: 600;
        }
        .support-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 12px;
            color: #0967f6;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
        }
        .support-link:hover {
            color: #0756cf;
            text-decoration: underline;
        }
        @media (max-width: 520px) {
            .modal { padding: 24px 18px; }
            .brand-logo { margin-top: 12px; }
            h1 { font-size: 22px; }
        }
    </style>
</head>
<body>
    <main class="modal">
        <img class="brand-logo" src="{{ asset('assets/myads_colour_02.png') }}" alt="MyAds">
        <h1>Lengkapi Data Anda</h1>
        <p class="subtitle">Silakan isi data di bawah ini untuk melanjutkan pembayaran.</p>

        <form method="POST" action="{{ route('checkout.store') }}">
            @csrf
            <div class="field">
                <label for="name">Nama Lengkap <span class="required-mark">(*)</span></label>
                <div class="control">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                    <input id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Budi Santoso" autocomplete="name" required>
                </div>
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="email">Email MyAds <span class="required-mark">(*)</span></label>
                <div class="control">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Contoh: budi@myads.id" autocomplete="email" maxlength="150" required>
                </div>
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="phone">Nomor Telepon <span class="required-mark">(*)</span></label>
                <div class="control">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.32 1.77.59 2.61a2 2 0 0 1-.45 2.11L8 9.69a16 16 0 0 0 6.31 6.31l1.25-1.25a2 2 0 0 1 2.11-.45c.84.27 1.71.47 2.61.59A2 2 0 0 1 22 16.92z"/></svg>
                    <input id="phone" name="phone" value="{{ old('phone') }}" placeholder="Contoh: 08123456789" autocomplete="tel" inputmode="numeric" minlength="10" maxlength="14" pattern="[0-9]{10,14}" required>
                </div>
                @error('phone') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="referral_code">Referral Code (Optional)</label>
                <div class="control">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10 13-2 2a3 3 0 1 1-4-4l2-2"/><path d="m14 11 2-2a3 3 0 1 1 4 4l-2 2"/><path d="M8 16l8-8"/></svg>
                    <input id="referral_code" name="referral_code" value="{{ old('referral_code') }}" placeholder="Opsional">
                </div>
                @error('referral_code') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="amount">Amount <span class="required-mark">(*)</span></label>
                <div class="control">
                    <span class="currency-prefix">Rp</span>
                    <input id="amount" name="amount" type="text" value="{{ old('amount') ? number_format((int) preg_replace('/\D/', '', old('amount')), 0, ',', '.') : '' }}" placeholder="Contoh: 500.000" inputmode="numeric" autocomplete="off" required>
                </div>
                @error('amount') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="grandTotal">Grand Total</label>
                <div class="control">
                    <span class="currency-prefix">Rp</span>
                    <input id="grandTotal" type="text" value="0" readonly tabindex="-1" aria-readonly="true">
                </div>
                <div class="field-note">*) Include PPN</div>
            </div>

            <div class="field">
                <label>Metode Pembayaran <span class="required-mark">(*)</span></label>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_type" value="qris" {{ old('payment_type', 'qris') === 'qris' ? 'checked' : '' }}>
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM18 18h3v3h-3zM18 14h3M14 18v3"/></svg>
                        QRIS
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_type" value="va" {{ old('payment_type') === 'va' ? 'checked' : '' }}>
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V9l7-4 7 4v12"/><path d="M9 21v-8h6v8"/><path d="M7 9h10"/></svg>
                        Virtual Account
                    </label>
                    <div class="bank-options" id="bankOptions">
                        @foreach ([
                            'bsi' => ['label' => 'BSI', 'logo' => 'bsi.svg'],
                            'bni' => ['label' => 'BNI', 'logo' => 'bni.png'],
                            'permata' => ['label' => 'Permata', 'logo' => 'permata.png'],
                            'mandiri' => ['label' => 'Mandiri', 'logo' => 'mandiri.png'],
                        ] as $value => $bank)
                            <label class="bank-option">
                                <input type="radio" name="va_bank" value="{{ $value }}" {{ old('va_bank') === $value ? 'checked' : '' }}>
                                <img class="bank-logo" src="{{ asset('assets/banks/'.$bank['logo']) }}" alt="" aria-hidden="true">
                                {{ $bank['label'] }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="payment_method" id="paymentMethod" value="{{ old('payment_method', 'qris') }}">
                @error('payment_method') <div class="error">{{ $message }}</div> @enderror
            </div>

            <button type="submit">
                <svg class="icon" style="color:#fff" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                Bayar
            </button>
        </form>

        <div class="secure">
            <svg class="icon" style="width:18px;height:18px;color:#4f9ac9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
            Data Anda aman dan terlindungi
        </div>
        <a class="support-link" href="https://wa.me/6282347189584" target="_blank" rel="noopener">
            Hubungi CS: +62 823-4718-9584
        </a>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const minimumAmount = 500000;
        const checkoutForm = document.querySelector('form[action="{{ route('checkout.store') }}"]');
        const amountInput = document.getElementById('amount');
        const grandTotalInput = document.getElementById('grandTotal');
        const paymentMethodInput = document.getElementById('paymentMethod');
        const bankOptions = document.getElementById('bankOptions');
        const paymentTypeInputs = document.querySelectorAll('input[name="payment_type"]');
        const bankInputs = document.querySelectorAll('input[name="va_bank"]');

        function updateAmounts() {
            const digits = amountInput.value.replace(/\D/g, '');
            amountInput.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            const dpp = Number(digits || 0);
            const ppn = Math.round((dpp * 11 / 12) * 0.12);
            grandTotalInput.value = (dpp + ppn).toLocaleString('id-ID');
        }

        amountInput.addEventListener('input', updateAmounts);
        updateAmounts();

        checkoutForm?.addEventListener('submit', (event) => {
            const amount = Number(amountInput.value.replace(/\D/g, '') || 0);

            if (amount < minimumAmount) {
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Amount belum memenuhi minimum',
                    text: 'Minimal amount adalah Rp 500.000',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#0967f6',
                });
                amountInput.focus();
            }
        });

        function syncPaymentMethod() {
            const paymentType = document.querySelector('input[name="payment_type"]:checked')?.value;
            const selectedBank = document.querySelector('input[name="va_bank"]:checked')?.value;

            bankOptions.classList.toggle('is-visible', paymentType === 'va');
            paymentMethodInput.value = paymentType === 'va' ? (selectedBank || '') : 'qris';
        }

        paymentTypeInputs.forEach((input) => input.addEventListener('change', syncPaymentMethod));
        bankInputs.forEach((input) => input.addEventListener('change', syncPaymentMethod));
        syncPaymentMethod();
    </script>
</body>
</html>
