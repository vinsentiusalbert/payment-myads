<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Payment Success</title>
</head>
<body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;color:#111827;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #d9e0ea;border-radius:8px;padding:32px;">
        <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;">Payment Success</h1>
        <p style="margin:0 0 20px;font-size:14px;line-height:1.6;">
            Pembayaran sukses telah diterima untuk transaksi berikut. Mohon dibantu untuk transfer saldonya
        </p>

        <table style="width:100%;border-collapse:collapse;font-size:14px;line-height:1.6;">
            <tr>
                <td style="padding:8px 0;color:#64748b;">Transaction ID</td>
                <td style="padding:8px 0;"><strong>{{ $payment->transaction_id }}</strong></td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Nama</td>
                <td style="padding:8px 0;"><strong>{{ $payment->customer_name }}</strong></td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Email</td>
                <td style="padding:8px 0;"><strong>{{ $payment->customer_email }}</strong></td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Nomor Telepon</td>
                <td style="padding:8px 0;"><strong>{{ $payment->customer_phone }}</strong></td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Referral Code</td>
                <td style="padding:8px 0;"><strong>{{ $payment->referral_code ?: '-' }}</strong></td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Metode</td>
                <td style="padding:8px 0;"><strong>{{ $payment->channel_code }}</strong></td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Amount</td>
                <td style="padding:8px 0;"><strong>Rp {{ number_format($payment->transaction_amount, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">PPN</td>
                <td style="padding:8px 0;"><strong>Rp {{ number_format($payment->tax_amount, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Grand Total</td>
                <td style="padding:8px 0;"><strong>Rp {{ number_format($payment->grand_total_amount, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Status</td>
                <td style="padding:8px 0;"><strong>{{ $payment->status }}</strong></td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">Payment Date</td>
                <td style="padding:8px 0;"><strong>{{ $payment->payment_date?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') }} WIB</strong></td>
            </tr>
        </table>

        <p style="margin:24px 0 0;font-size:14px;line-height:1.6;">
            Mohon menunggu saldo akan masuk kurang dari 24 jam.
        </p>
    </div>
</body>
</html>
