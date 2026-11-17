@php
    $format = fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Struk {{ $order->order_number }}</title>
        <style>
            :root {
                color-scheme: light;
                --paper-width: {{ $paperWidth }}mm;

                /* Sama dengan token di resources/css/app.css. */
                --brand: #D50112;
                --brand-foreground: #FFFFFF;
                --surface: #FFFFFF;
                --background: #FAF9F6;
                --foreground: #211D1B;
                --muted-foreground: #746E68;
                --border: #E9E4DD;
                --success: #15803D;
            }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                background: var(--background);
                color: var(--foreground);
                font-family: 'Courier New', Courier, monospace;
                font-size: 12px;
                line-height: 1.45;
            }

            .receipt-actions {
                max-width: 700px;
                margin: 0 auto;
                padding: 16px;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 12px;
                font-family: system-ui, sans-serif;
            }

            .receipt-actions h1 {
                font-size: 16px;
                margin: 0 auto 0 0;
            }

            .receipt-actions a,
            .receipt-actions button {
                min-height: 40px;
                padding: 8px 14px;
                border: 1px solid var(--border);
                border-radius: 6px;
                background: var(--surface);
                color: var(--foreground);
                font: inherit;
                font-size: 13px;
                text-decoration: none;
                cursor: pointer;
            }

            .receipt-actions a[aria-current='true'],
            .receipt-actions button {
                background: var(--brand);
                border-color: var(--brand);
                color: var(--brand-foreground);
            }

            .receipt-actions a[aria-current='true']:hover,
            .receipt-actions button:hover {
                background: var(--brand-hover);
                border-color: var(--brand-hover);
            }

            .notice {
                max-width: 700px;
                margin: 0 auto;
                padding: 0 16px 12px;
                color: var(--success);
                font-family: system-ui, sans-serif;
            }

            .receipt {
                width: var(--paper-width);
                min-height: 120mm;
                margin: 0 auto 24px;
                padding: 6mm 4mm;
                background: var(--surface);
                box-shadow: 0 1px 4px rgb(0 0 0 / 15%);
            }

            .center { text-align: center; }

            .brand-logo {
                display: block;
                width: auto;
                max-width: 70%;
                height: auto;
                margin: 0 auto 4mm;
                object-fit: contain;
            }

            .muted { color: var(--muted-foreground); }

            .business-name {
                font-size: 15px;
                font-weight: 700;
                letter-spacing: 0.03em;
                text-transform: uppercase;
            }

            .rule {
                border-top: 1px dashed var(--muted-foreground);
                margin: 8px 0;
            }

            .row {
                display: flex;
                justify-content: space-between;
                gap: 8px;
            }

            .row span:last-child {
                text-align: right;
                white-space: nowrap;
            }

            .item { margin: 8px 0; }
            .item-name { font-weight: 700; }
            .total-row {
                font-size: 14px;
                font-weight: 700;
                border-top: 1px dashed var(--muted-foreground);
                margin-top: 8px;
                padding-top: 8px;
            }

            @page {
                size: {{ $paperWidth }}mm auto;
                margin: 0;
            }

            @media print {
                body {
                    background: #fff;
                    font-size: 11px;
                }

                .receipt-actions,
                .notice {
                    display: none !important;
                }

                .receipt {
                    width: var(--paper-width);
                    min-height: 0;
                    margin: 0;
                    padding: 3mm 2mm;
                    box-shadow: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="receipt-actions">
            <h1>Struk pembayaran</h1>
            <a href="{{ route('orders.receipt', $order) }}?paper=58" @if ($paperWidth === 58) aria-current="true" @endif>58 mm</a>
            <a href="{{ route('orders.receipt', $order) }}?paper=80" @if ($paperWidth === 80) aria-current="true" @endif>80 mm</a>
            <button type="button" id="print-receipt">Cetak struk</button>
            <a href="{{ route('orders.show', $order) }}">Kembali ke pesanan</a>
        </div>

        @if ($successMessage)
            <p class="notice" role="status">{{ $successMessage }}</p>
        @endif

        <main class="receipt" data-paper="{{ $paperWidth }}" aria-label="Struk pembayaran">
            <div class="center">
                <img
                    class="brand-logo"
                    src="{{ asset('images/brand/logo-salero-basamo-jati.png') }}"
                    alt="{{ $businessName }}"
                />
                <div class="business-name">{{ $businessName }}</div>
                <div>Struk Pembayaran</div>
            </div>

            <div class="rule"></div>

            <div class="row"><span>No. pembayaran</span><span>{{ $payment->payment_number }}</span></div>
            <div class="row"><span>No. order</span><span>{{ $order->order_number }}</span></div>
            <div class="row"><span>Waktu</span><span>{{ $paidAt }} WIB</span></div>
            <div class="row"><span>Kasir</span><span>{{ $cashierName }}</span></div>
            <div class="row"><span>Tipe</span><span>{{ $orderTypeLabel }}</span></div>
            @if ($order->order_type === \App\Models\Order::TYPE_DINE_IN)
                <div class="row"><span>Meja</span><span>{{ $tableLabel }}</span></div>
            @endif

            <div class="rule"></div>

            @foreach ($order->items as $item)
                <div class="item">
                    <div class="item-name">{{ $item->product_name_snapshot }}</div>
                    <div class="muted">{{ $item->variant_name_snapshot }}</div>
                    <div class="row">
                        <span>{{ $item->quantity }} {{ $item->unit_snapshot }} × {{ $format($item->price_snapshot) }}</span>
                        <span>{{ $format($item->subtotal) }}</span>
                    </div>
                </div>
            @endforeach

            <div class="rule"></div>

            <div class="row"><span>Subtotal</span><span>{{ $format($order->subtotal) }}</span></div>
            <div class="row"><span>Diskon</span><span>{{ $format($order->discount) }}</span></div>
            <div class="row"><span>Pajak</span><span>{{ $format($order->tax) }}</span></div>
            <div class="row"><span>Biaya layanan</span><span>{{ $format($order->service_charge) }}</span></div>
            <div class="row total-row"><span>Total</span><span>{{ $format($payment->amount) }}</span></div>

            <div class="rule"></div>

            <div class="row"><span>Metode</span><span>{{ $paymentMethodLabel }}</span></div>
            @if ($payment->payment_method === \App\Models\Payment::METHOD_CASH)
                <div class="row"><span>Uang diterima</span><span>{{ $format($payment->received_amount ?? 0) }}</span></div>
                <div class="row"><span>Kembalian</span><span>{{ $format($payment->change_amount) }}</span></div>
            @endif

            <div class="rule"></div>
            <div class="center muted">Terima kasih</div>
        </main>

        <script>
            document.getElementById('print-receipt').addEventListener('click', () => window.print());
        </script>
    </body>
</html>
