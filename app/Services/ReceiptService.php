<?php

namespace App\Services;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ReceiptService
{
    /**
     * Generate receipt PDF for a sale.
     */
    public function generatePdf(Sale $sale, ?string $locale = 'en'): \Barryvdh\DomPDF\PDF
    {
        // Load sale with relationships
        $sale->load([
            'shop',
            'branch',
            'customer',
            'items.product',
            'servedBy',
        ]);

        // Prepare receipt data
        $data = $this->prepareReceiptData($sale, $locale);

        // Generate PDF
        $pdf = Pdf::loadView('receipts.sale', $data);

        // Configure PDF settings
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);
        $pdf->setOption('margin-right', 0);

        return $pdf;
    }

    /**
     * Generate and download receipt PDF.
     */
    public function downloadPdf(Sale $sale, ?string $locale = 'en'): \Illuminate\Http\Response
    {
        $pdf = $this->generatePdf($sale, $locale);
        $filename = $this->generateFilename($sale);

        return $pdf->download($filename);
    }

    /**
     * Generate and stream receipt PDF (view in browser).
     */
    public function streamPdf(Sale $sale, ?string $locale = 'en'): \Illuminate\Http\Response
    {
        $pdf = $this->generatePdf($sale, $locale);
        $filename = $this->generateFilename($sale);

        return $pdf->stream($filename);
    }

    /**
     * Generate receipt HTML (for email or preview).
     */
    public function generateHtml(Sale $sale, ?string $locale = 'en'): string
    {
        $sale->load([
            'shop',
            'branch',
            'customer',
            'items.product',
            'servedBy',
        ]);

        $data = $this->prepareReceiptData($sale, $locale);

        return View::make('receipts.sale', $data)->render();
    }

    /**
     * Prepare receipt data for rendering.
     */
    protected function prepareReceiptData(Sale $sale, string $locale): array
    {
        $translations = $this->getTranslations($locale);

        return [
            'sale' => $sale,
            'shop' => $sale->shop,
            'branch' => $sale->branch,
            'customer' => $sale->customer,
            'items' => $sale->items,
            'servedBy' => $sale->servedBy,
            'locale' => $locale,
            'trans' => $translations,
            'receiptNumber' => $sale->sale_number,
            'receiptDate' => $sale->sale_date,
            'subtotal' => $sale->subtotal,
            'discount' => $sale->discount_amount,
            'tax' => $sale->tax_amount,
            'total' => $sale->total_amount,
            'amountPaid' => $sale->amount_paid,
            'balance' => $sale->balance,
            'changeGiven' => $sale->change_given,
            'paymentMethods' => $sale->payment_methods,
            'currency' => $sale->currency ?? 'MWK',
            'isFiscalized' => $sale->is_fiscalized,
            'efdReceiptNumber' => $sale->efd_receipt_number,
            'efdQrCode' => $sale->efd_qr_code,
        ];
    }

    /**
     * Generate receipt filename.
     */
    protected function generateFilename(Sale $sale): string
    {
        $saleNumber = str_replace(['/', '\\', ' '], '-', $sale->sale_number);
        $date = $sale->sale_date->format('Y-m-d');

        return "receipt-{$saleNumber}-{$date}.pdf";
    }

    /**
     * Get translations for receipt.
     */
    protected function getTranslations(string $locale): array
    {
        $translations = [
            'en' => [
                'receipt' => 'RECEIPT',
                'tax_invoice' => 'TAX INVOICE',
                'receipt_number' => 'Receipt #',
                'date' => 'Date',
                'time' => 'Time',
                'served_by' => 'Served By',
                'customer' => 'Customer',
                'item' => 'Item',
                'qty' => 'Qty',
                'price' => 'Price',
                'discount' => 'Discount',
                'total' => 'Total',
                'subtotal' => 'Subtotal',
                'tax' => 'Tax',
                'grand_total' => 'Grand Total',
                'amount_paid' => 'Amount Paid',
                'balance' => 'Balance',
                'change' => 'Change',
                'payment_method' => 'Payment Method',
                'cash' => 'Cash',
                'mobile_money' => 'Mobile Money',
                'credit' => 'Credit',
                'bank_transfer' => 'Bank Transfer',
                'thank_you' => 'Thank you for your business!',
                'contact_us' => 'Contact Us',
                'phone' => 'Phone',
                'email' => 'Email',
                'address' => 'Address',
                'branch' => 'Branch',
                'efd_number' => 'EFD Receipt #',
                'fiscal_signature' => 'Fiscal Signature',
                'scan_qr' => 'Scan QR Code to Verify',
            ],
            'ny' => [ // Chichewa
                'receipt' => 'RISITI',
                'tax_invoice' => 'INVOICE YA MSONKHO',
                'receipt_number' => 'Nambala ya Risiti',
                'date' => 'Tsiku',
                'time' => 'Nthawi',
                'served_by' => 'Wothandizidwa Ndi',
                'customer' => 'Kasitomala',
                'item' => 'Chinthu',
                'qty' => 'Kuchuluka',
                'price' => 'Mtengo',
                'discount' => 'Chotsika',
                'total' => 'Zonse',
                'subtotal' => 'Chiwerengero',
                'tax' => 'Msonkho',
                'grand_total' => 'Zonse Pamodzi',
                'amount_paid' => 'Cholipidwa',
                'balance' => 'Chobwereka',
                'change' => 'Chobwerera',
                'payment_method' => 'Njira Yolipirira',
                'cash' => 'Ndalama',
                'mobile_money' => 'Mobile Money',
                'credit' => 'Ngongole',
                'bank_transfer' => 'Kusamutsa Ndalama',
                'thank_you' => 'Zikomo kwambiri!',
                'contact_us' => 'Tiyandikire',
                'phone' => 'Foni',
                'email' => 'Imelo',
                'address' => 'Adilesi',
                'branch' => 'Nthambi',
                'efd_number' => 'Nambala ya EFD',
                'fiscal_signature' => 'Siginecha ya Msonkho',
                'scan_qr' => 'Scan QR Code kuti Mutsimikizire',
            ],
        ];

        return $translations[$locale] ?? $translations['en'];
    }

    /**
     * Format currency for display.
     */
    public function formatCurrency(float $amount, string $currency = 'MWK'): string
    {
        return $currency.' '.number_format($amount, 2);
    }

    /**
     * Get payment method display name.
     */
    public function getPaymentMethodName(string $method, string $locale = 'en'): string
    {
        $translations = $this->getTranslations($locale);

        $methodMap = [
            'cash' => $translations['cash'],
            'mobile_money' => $translations['mobile_money'],
            'airtel_money' => 'Airtel Money',
            'mpamba' => 'TNM Mpamba',
            'credit' => $translations['credit'],
            'bank_transfer' => $translations['bank_transfer'],
        ];

        return $methodMap[$method] ?? ucfirst(str_replace('_', ' ', $method));
    }
}
