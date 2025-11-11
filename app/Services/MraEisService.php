<?php

namespace App\Services;

use App\Models\EfdTransaction;
use App\Models\Sale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MraEisService
{
    protected string $baseUrl;

    protected string $clientId;

    protected string $clientSecret;

    protected bool $enabled;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.mra_eis.base_url', 'https://eis-api.mra.mw');
        $this->clientId = config('services.mra_eis.client_id', '');
        $this->clientSecret = config('services.mra_eis.client_secret', '');
        $this->enabled = config('services.mra_eis.enabled', false);
        $this->timeout = config('services.mra_eis.timeout', 30);
    }

    /**
     * Submit an invoice to MRA EIS for fiscalization.
     */
    public function fiscalizeInvoice(Sale $sale): array
    {
        if (! $this->enabled) {
            return [
                'success' => false,
                'message' => 'MRA EIS integration is disabled',
                'simulation' => true,
            ];
        }

        try {
            // Get authentication token
            $token = $this->authenticate();

            if (! $token) {
                throw new \Exception('Failed to authenticate with MRA EIS');
            }

            // Prepare invoice data
            $invoiceData = $this->prepareInvoiceData($sale);

            // Submit to MRA EIS API
            $response = Http::withToken($token)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/api/v1/invoices", $invoiceData);

            if ($response->successful()) {
                $data = $response->json();

                // Create EFD transaction record
                $efdTransaction = EfdTransaction::create([
                    'shop_id' => $sale->shop_id,
                    'sale_id' => $sale->id,
                    'efd_device_id' => $sale->efd_device_id ?? 'EIS-API',
                    'fiscal_receipt_number' => $data['fiscal_receipt_number'] ?? null,
                    'fiscal_signature' => $data['fiscal_signature'] ?? null,
                    'qr_code_data' => $data['qr_code'] ?? null,
                    'verification_url' => $data['verification_url'] ?? null,
                    'total_amount' => $sale->total_amount,
                    'vat_amount' => $sale->tax_amount,
                    'mra_response_code' => $response->status(),
                    'mra_response_message' => $data['message'] ?? 'Success',
                    'mra_acknowledgement' => $data,
                    'transmitted_at' => now(),
                    'transmission_status' => 'success',
                    'created_at' => now(),
                ]);

                // Update sale with EFD information
                $sale->update([
                    'is_fiscalized' => true,
                    'efd_receipt_number' => $data['fiscal_receipt_number'] ?? null,
                    'efd_qr_code' => $data['qr_code'] ?? null,
                    'efd_fiscal_signature' => $data['fiscal_signature'] ?? null,
                    'efd_transmitted_at' => now(),
                    'efd_response' => $data,
                ]);

                Log::info('MRA EIS: Invoice fiscalized successfully', [
                    'sale_id' => $sale->id,
                    'fiscal_receipt_number' => $data['fiscal_receipt_number'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => 'Invoice fiscalized successfully',
                    'fiscal_receipt_number' => $data['fiscal_receipt_number'] ?? null,
                    'qr_code' => $data['qr_code'] ?? null,
                    'verification_url' => $data['verification_url'] ?? null,
                    'efd_transaction_id' => $efdTransaction->id,
                ];
            }

            // Handle API errors
            $errorData = $response->json();
            $this->recordFailedTransmission($sale, $response->status(), $errorData);

            Log::error('MRA EIS: Failed to fiscalize invoice', [
                'sale_id' => $sale->id,
                'status' => $response->status(),
                'error' => $errorData,
            ]);

            return [
                'success' => false,
                'message' => $errorData['message'] ?? 'Failed to fiscalize invoice',
                'error_code' => $response->status(),
                'error_details' => $errorData,
            ];

        } catch (\Exception $e) {
            Log::error('MRA EIS: Exception during fiscalization', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);

            $this->recordFailedTransmission($sale, 500, ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Exception during fiscalization: '.$e->getMessage(),
                'exception' => true,
            ];
        }
    }

    /**
     * Authenticate with MRA EIS API and get access token.
     */
    protected function authenticate(): ?string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/v1/auth/token", [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['access_token'] ?? null;
            }

            Log::error('MRA EIS: Authentication failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('MRA EIS: Authentication exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Prepare invoice data for MRA EIS API submission.
     */
    protected function prepareInvoiceData(Sale $sale): array
    {
        $sale->load(['items', 'customer', 'shop', 'branch']);

        $items = $sale->items->map(function ($item) {
            return [
                'item_code' => $item->product_sku ?? $item->product_id,
                'item_description' => $item->product_name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount,
                'taxable' => (bool) $item->is_taxable,
                'tax_rate' => (float) $item->tax_rate,
                'tax_amount' => (float) $item->tax_amount,
                'subtotal' => (float) $item->subtotal,
                'total' => (float) $item->total,
            ];
        });

        return [
            'invoice_number' => $sale->sale_number,
            'invoice_date' => $sale->sale_date->toIso8601String(),
            'invoice_type' => 'SALE',
            'seller' => [
                'business_name' => $sale->shop->business_name,
                'tpin' => $sale->shop->tpin,
                'vat_number' => $sale->shop->vat_registration_number,
                'address' => $sale->shop->address,
                'branch_name' => $sale->branch->name ?? null,
            ],
            'buyer' => $sale->customer ? [
                'name' => $sale->customer->name,
                'phone' => $sale->customer->phone,
                'email' => $sale->customer->email,
                'address' => $sale->customer->address,
            ] : [
                'name' => 'Walk-in Customer',
                'phone' => null,
                'email' => null,
                'address' => null,
            ],
            'items' => $items->toArray(),
            'totals' => [
                'subtotal' => (float) $sale->subtotal,
                'discount_amount' => (float) $sale->discount_amount,
                'tax_amount' => (float) $sale->tax_amount,
                'total_amount' => (float) $sale->total_amount,
            ],
            'payment' => [
                'payment_status' => $sale->payment_status,
                'amount_paid' => (float) $sale->amount_paid,
                'balance' => (float) $sale->balance,
                'payment_methods' => $sale->payment_methods ?? [],
            ],
            'currency' => $sale->currency ?? 'MWK',
            'exchange_rate' => (float) ($sale->exchange_rate ?? 1.0),
        ];
    }

    /**
     * Record a failed transmission attempt.
     */
    protected function recordFailedTransmission(Sale $sale, int $statusCode, array $errorData): void
    {
        EfdTransaction::create([
            'shop_id' => $sale->shop_id,
            'sale_id' => $sale->id,
            'efd_device_id' => $sale->efd_device_id ?? 'EIS-API',
            'total_amount' => $sale->total_amount,
            'vat_amount' => $sale->tax_amount,
            'mra_response_code' => $statusCode,
            'mra_response_message' => $errorData['message'] ?? 'Failed',
            'mra_acknowledgement' => $errorData,
            'transmission_status' => 'failed',
            'retry_count' => 0,
            'next_retry_at' => now()->addMinutes(5),
            'created_at' => now(),
        ]);
    }

    /**
     * Retry failed fiscalizations.
     */
    public function retryFailedFiscalizations(int $limit = 10): array
    {
        $failedTransactions = EfdTransaction::where('transmission_status', 'failed')
            ->where('retry_count', '<', 3)
            ->where('next_retry_at', '<=', now())
            ->with('sale')
            ->limit($limit)
            ->get();

        $results = [
            'total' => $failedTransactions->count(),
            'successful' => 0,
            'failed' => 0,
        ];

        foreach ($failedTransactions as $transaction) {
            if (! $transaction->sale) {
                continue;
            }

            $result = $this->fiscalizeInvoice($transaction->sale);

            if ($result['success']) {
                $results['successful']++;
                $transaction->update(['transmission_status' => 'success']);
            } else {
                $results['failed']++;
                $transaction->increment('retry_count');
                $transaction->update([
                    'last_retry_at' => now(),
                    'next_retry_at' => now()->addMinutes(5 * ($transaction->retry_count + 1)),
                ]);
            }
        }

        return $results;
    }

    /**
     * Verify a fiscalized invoice with MRA.
     */
    public function verifyInvoice(string $fiscalReceiptNumber): array
    {
        if (! $this->enabled) {
            return [
                'success' => false,
                'message' => 'MRA EIS integration is disabled',
            ];
        }

        try {
            $token = $this->authenticate();

            if (! $token) {
                throw new \Exception('Failed to authenticate with MRA EIS');
            }

            $response = Http::withToken($token)
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/api/v1/invoices/{$fiscalReceiptNumber}/verify");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to verify invoice',
                'error' => $response->json(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception during verification: '.$e->getMessage(),
            ];
        }
    }
}
