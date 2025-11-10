<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $trans['receipt'] }} - {{ $receiptNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .receipt {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .shop-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .branch-name {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .shop-info {
            font-size: 11px;
            color: #666;
            line-height: 1.6;
        }

        .receipt-type {
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
            text-transform: uppercase;
        }

        .meta-info {
            display: table;
            width: 100%;
            margin: 20px 0;
            font-size: 11px;
        }

        .meta-row {
            display: table-row;
        }

        .meta-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            width: 30%;
        }

        .meta-value {
            display: table-cell;
            padding: 5px 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .items-table th {
            background: #f5f5f5;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #333;
        }

        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .totals {
            margin-top: 20px;
            float: right;
            width: 300px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 12px;
        }

        .totals-row.subtotal,
        .totals-row.discount,
        .totals-row.tax {
            border-bottom: 1px solid #eee;
        }

        .totals-row.grand-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 12px;
            margin-top: 8px;
        }

        .totals-row .label {
            font-weight: bold;
        }

        .payment-info {
            clear: both;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }

        .payment-methods {
            margin: 15px 0;
        }

        .payment-method {
            display: inline-block;
            background: #f5f5f5;
            padding: 5px 15px;
            margin: 5px 5px 5px 0;
            border-radius: 3px;
            font-size: 11px;
        }

        .efd-info {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .efd-info .efd-label {
            font-weight: bold;
            display: inline-block;
            width: 140px;
        }

        .qr-code {
            text-align: center;
            margin: 20px 0;
        }

        .qr-code img {
            max-width: 150px;
            height: auto;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
            font-size: 11px;
            color: #666;
        }

        .thank-you {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .contact-info {
            margin-top: 10px;
            line-height: 1.8;
        }

        @media print {
            body {
                padding: 0;
            }

            .receipt {
                border: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="receipt">
    <!-- Header -->
    <div class="header">
        <div class="shop-name">{{ $shop->name }}</div>
        @if($branch)
            <div class="branch-name">{{ $trans['branch'] }}: {{ $branch->name }}</div>
        @endif
        <div class="shop-info">
            @if($shop->address)
                {{ $shop->address }}<br>
            @endif
            @if($shop->phone)
                {{ $trans['phone'] }}: {{ $shop->phone }}<br>
            @endif
            @if($shop->email)
                {{ $trans['email'] }}: {{ $shop->email }}
            @endif
        </div>
        <div class="receipt-type">
            {{ $isFiscalized ? $trans['tax_invoice'] : $trans['receipt'] }}
        </div>
    </div>

    <!-- Receipt Meta Information -->
    <div class="meta-info">
        <div class="meta-row">
            <div class="meta-label">{{ $trans['receipt_number'] }}:</div>
            <div class="meta-value">{{ $receiptNumber }}</div>
        </div>
        <div class="meta-row">
            <div class="meta-label">{{ $trans['date'] }}:</div>
            <div class="meta-value">{{ $receiptDate->format('d/m/Y') }}</div>
        </div>
        <div class="meta-row">
            <div class="meta-label">{{ $trans['time'] }}:</div>
            <div class="meta-value">{{ $receiptDate->format('H:i:s') }}</div>
        </div>
        @if($customer)
            <div class="meta-row">
                <div class="meta-label">{{ $trans['customer'] }}:</div>
                <div class="meta-value">{{ $customer->name }}</div>
            </div>
        @endif
        @if($servedBy)
            <div class="meta-row">
                <div class="meta-label">{{ $trans['served_by'] }}:</div>
                <div class="meta-value">{{ $servedBy->name }}</div>
            </div>
        @endif
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
        <tr>
            <th>{{ $trans['item'] }}</th>
            <th class="text-center">{{ $trans['qty'] }}</th>
            <th class="text-right">{{ $trans['price'] }}</th>
            @if($items->where('discount_amount', '>', 0)->count() > 0)
                <th class="text-right">{{ $trans['discount'] }}</th>
            @endif
            <th class="text-right">{{ $trans['total'] }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($items as $item)
            <tr>
                <td>
                    {{ $locale === 'ny' && $item->product_name_chichewa ? $item->product_name_chichewa : $item->product_name }}
                    @if($item->notes)
                        <br><small style="color: #666;">{{ $item->notes }}</small>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }} {{ $item->unit }}</td>
                <td class="text-right">{{ $currency }} {{ number_format($item->unit_price, 2) }}</td>
                @if($items->where('discount_amount', '>', 0)->count() > 0)
                    <td class="text-right">
                        @if($item->discount_amount > 0)
                            {{ $currency }} {{ number_format($item->discount_amount, 2) }}
                        @else
                            -
                        @endif
                    </td>
                @endif
                <td class="text-right">{{ $currency }} {{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <div class="totals-row subtotal">
            <span class="label">{{ $trans['subtotal'] }}:</span>
            <span>{{ $currency }} {{ number_format($subtotal, 2) }}</span>
        </div>
        @if($discount > 0)
            <div class="totals-row discount">
                <span class="label">{{ $trans['discount'] }}:</span>
                <span>-{{ $currency }} {{ number_format($discount, 2) }}</span>
            </div>
        @endif
        @if($tax > 0)
            <div class="totals-row tax">
                <span class="label">{{ $trans['tax'] }}:</span>
                <span>{{ $currency }} {{ number_format($tax, 2) }}</span>
            </div>
        @endif
        <div class="totals-row grand-total">
            <span class="label">{{ $trans['grand_total'] }}:</span>
            <span>{{ $currency }} {{ number_format($total, 2) }}</span>
        </div>
    </div>

    <!-- Payment Information -->
    <div class="payment-info">
        <div class="totals-row">
            <span class="label">{{ $trans['amount_paid'] }}:</span>
            <span>{{ $currency }} {{ number_format($amountPaid, 2) }}</span>
        </div>
        @if($balance > 0)
            <div class="totals-row" style="color: #d9534f;">
                <span class="label">{{ $trans['balance'] }}:</span>
                <span>{{ $currency }} {{ number_format($balance, 2) }}</span>
            </div>
        @endif
        @if($changeGiven > 0)
            <div class="totals-row">
                <span class="label">{{ $trans['change'] }}:</span>
                <span>{{ $currency }} {{ number_format($changeGiven, 2) }}</span>
            </div>
        @endif

        @if($paymentMethods && count($paymentMethods) > 0)
            <div class="payment-methods">
                <strong>{{ $trans['payment_method'] }}:</strong><br>
                @foreach($paymentMethods as $method)
                    <span class="payment-method">
                        {{ ucfirst(str_replace('_', ' ', $method['method'])) }}
                        @if(isset($method['amount']))
                            - {{ $currency }} {{ number_format($method['amount'], 2) }}
                        @endif
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <!-- EFD Information (if fiscalized) -->
    @if($isFiscalized)
        <div class="efd-info">
            <div style="margin-bottom: 10px;">
                <span class="efd-label">{{ $trans['efd_number'] }}:</span>
                <span>{{ $efdReceiptNumber }}</span>
            </div>
            @if($efdQrCode)
                <div class="qr-code">
                    <img src="data:image/png;base64,{{ $efdQrCode }}" alt="QR Code">
                    <div style="font-size: 10px; margin-top: 5px;">{{ $trans['scan_qr'] }}</div>
                </div>
            @endif
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div class="thank-you">{{ $trans['thank_you'] }}</div>
        <div class="contact-info">
            @if($shop->phone || $shop->email)
                <strong>{{ $trans['contact_us'] }}:</strong><br>
                @if($shop->phone)
                    {{ $trans['phone'] }}: {{ $shop->phone }}
                @endif
                @if($shop->phone && $shop->email) | @endif
                @if($shop->email)
                    {{ $trans['email'] }}: {{ $shop->email }}
                @endif
            @endif
        </div>
        <div style="margin-top: 15px; font-size: 10px;">
            Powered by TiwineBiz
        </div>
    </div>
</div>
</body>
</html>
