<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders.
     *
     * Supports filtering by:
     * - shop_id, supplier_id, status
     * - from_date, to_date (order_date)
     * - search (po_number)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $accessibleShopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('shopUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        $query = PurchaseOrder::query()
            ->with(['shop', 'supplier', 'creator', 'approver', 'items'])
            ->whereIn('shop_id', $accessibleShopIds);

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $query->where('po_number', 'like', '%'.$request->search.'%');
        }

        $sortBy = $request->input('sort_by', 'order_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $purchaseOrders = $query->paginate(15);

        return PurchaseOrderResource::collection($purchaseOrders);
    }

    /**
     * Store a newly created purchase order with items.
     */
    public function store(StorePurchaseOrderRequest $request): PurchaseOrderResource
    {
        $user = $request->user();

        // Verify shop access
        Shop::where('id', $request->shop_id)
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('shopUsers', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->firstOrFail();

        // Auto-generate PO number if not provided
        $poNumber = $request->po_number ?? 'PO-'.now()->format('Ymd').'-'.str_pad(
            PurchaseOrder::where('shop_id', $request->shop_id)
                ->whereDate('created_at', today())
                ->count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

        // Calculate totals from items
        $items = $request->items;
        $subtotal = collect($items)->sum('subtotal');

        $total = $subtotal
            + ($request->tax_amount ?? 0)
            + ($request->freight_cost ?? 0)
            + ($request->insurance_cost ?? 0)
            + ($request->customs_duty ?? 0)
            + ($request->clearing_fee ?? 0)
            + ($request->transport_cost ?? 0)
            + ($request->other_charges ?? 0);

        // Create purchase order
        $purchaseOrder = PurchaseOrder::create([
            'shop_id' => $request->shop_id,
            'supplier_id' => $request->supplier_id,
            'po_number' => $poNumber,
            'subtotal' => $subtotal,
            'tax_amount' => $request->tax_amount ?? 0,
            'freight_cost' => $request->freight_cost ?? 0,
            'insurance_cost' => $request->insurance_cost ?? 0,
            'customs_duty' => $request->customs_duty ?? 0,
            'clearing_fee' => $request->clearing_fee ?? 0,
            'transport_cost' => $request->transport_cost ?? 0,
            'other_charges' => $request->other_charges ?? 0,
            'total_amount' => $total,
            'currency' => $request->currency ?? 'MWK',
            'exchange_rate' => $request->exchange_rate,
            'amount_in_base_currency' => $request->amount_in_base_currency,
            'status' => $request->status ?? 'draft',
            'order_date' => $request->order_date,
            'expected_delivery_date' => $request->expected_delivery_date,
            'shipping_method' => $request->shipping_method,
            'tracking_number' => $request->tracking_number,
            'border_point' => $request->border_point,
            'clearing_agent_name' => $request->clearing_agent_name,
            'clearing_agent_phone' => $request->clearing_agent_phone,
            'customs_entry_number' => $request->customs_entry_number,
            'documents' => $request->documents ?? [],
            'notes' => $request->notes,
            'internal_notes' => $request->internal_notes,
            'created_by' => $user->id,
        ]);

        // Create purchase order items
        foreach ($items as $item) {
            $purchaseOrder->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_code' => $item['product_code'] ?? null,
                'quantity_ordered' => $item['quantity_ordered'],
                'quantity_received' => $item['quantity_received'] ?? 0,
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['subtotal'],
                'is_complete' => ($item['quantity_received'] ?? 0) >= $item['quantity_ordered'],
                'notes' => $item['notes'] ?? null,
            ]);
        }

        return new PurchaseOrderResource($purchaseOrder->load(['shop', 'supplier', 'creator', 'items']));
    }

    /**
     * Display the specified purchase order.
     */
    public function show(Request $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $user = $request->user();

        $accessibleShopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('shopUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        if (! $accessibleShopIds->contains($purchaseOrder->shop_id)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this purchase order.');
        }

        return new PurchaseOrderResource($purchaseOrder->load(['shop', 'supplier', 'creator', 'approver', 'items.product']));
    }

    /**
     * Update the specified purchase order.
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $user = $request->user();

        $accessibleShopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('shopUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        if (! $accessibleShopIds->contains($purchaseOrder->shop_id)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this purchase order.');
        }

        $data = $request->only([
            'status', 'expected_delivery_date', 'actual_delivery_date',
            'shipping_method', 'tracking_number', 'border_point',
            'clearing_agent_name', 'clearing_agent_phone', 'customs_entry_number',
            'freight_cost', 'insurance_cost', 'customs_duty', 'clearing_fee',
            'transport_cost', 'other_charges', 'tax_amount',
            'documents', 'notes', 'internal_notes',
        ]);

        // Set status-related timestamps
        if (isset($data['status'])) {
            if ($data['status'] === 'sent' && ! $purchaseOrder->sent_at) {
                $data['sent_at'] = now();
            }
            if ($data['status'] === 'confirmed' && ! $purchaseOrder->confirmed_at) {
                $data['confirmed_at'] = now();
                $data['approved_by'] = $user->id;
            }
        }

        // Recalculate total if costs changed
        if ($request->hasAny(['tax_amount', 'freight_cost', 'insurance_cost', 'customs_duty', 'clearing_fee', 'transport_cost', 'other_charges'])) {
            $data['total_amount'] = $purchaseOrder->subtotal
                + ($data['tax_amount'] ?? $purchaseOrder->tax_amount)
                + ($data['freight_cost'] ?? $purchaseOrder->freight_cost)
                + ($data['insurance_cost'] ?? $purchaseOrder->insurance_cost)
                + ($data['customs_duty'] ?? $purchaseOrder->customs_duty)
                + ($data['clearing_fee'] ?? $purchaseOrder->clearing_fee)
                + ($data['transport_cost'] ?? $purchaseOrder->transport_cost)
                + ($data['other_charges'] ?? $purchaseOrder->other_charges);
        }

        $purchaseOrder->update($data);

        return new PurchaseOrderResource($purchaseOrder->load(['shop', 'supplier', 'creator', 'approver', 'items']));
    }

    /**
     * Cancel the specified purchase order.
     */
    public function destroy(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $user = $request->user();

        $accessibleShopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('shopUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        if (! $accessibleShopIds->contains($purchaseOrder->shop_id)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this purchase order.');
        }

        // Can only cancel draft or sent orders
        if (! in_array($purchaseOrder->status, ['draft', 'sent'])) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Can only cancel draft or sent purchase orders.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return response()->noContent();
    }
}
