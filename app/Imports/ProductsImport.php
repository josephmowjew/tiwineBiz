<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class ProductsImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    protected string $shopId;

    protected string $userId;

    protected array $failures = [];

    protected int $rowCount = 0;

    public function __construct(string $shopId, string $userId)
    {
        $this->shopId = $shopId;
        $this->userId = $userId;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->rowCount++;

        // Look up category by name if provided
        $categoryId = null;
        if (! empty($row['category'])) {
            $category = Category::where('shop_id', $this->shopId)
                ->where('name', $row['category'])
                ->first();
            $categoryId = $category?->id;
        }

        // Look up supplier by name if provided
        $supplierId = null;
        if (! empty($row['supplier'])) {
            $supplier = Supplier::where('shop_id', $this->shopId)
                ->where('name', $row['supplier'])
                ->first();
            $supplierId = $supplier?->id;
        }

        return new Product([
            'shop_id' => $this->shopId,
            'name' => $row['name'],
            'name_chichewa' => $row['name_chichewa'] ?? null,
            'description' => $row['description'] ?? null,
            'sku' => $row['sku'] ?? null,
            'barcode' => $row['barcode'] ?? null,
            'manufacturer_code' => $row['manufacturer_code'] ?? null,
            'category_id' => $categoryId,
            'cost_price' => $row['cost_price'] ?? 0,
            'selling_price' => $row['selling_price'],
            'base_currency' => $row['base_currency'] ?? 'MWK',
            'quantity' => $row['quantity'] ?? 0,
            'unit' => $row['unit'] ?? 'piece',
            'min_stock_level' => $row['min_stock_level'] ?? 0,
            'max_stock_level' => $row['max_stock_level'] ?? null,
            'reorder_point' => $row['reorder_point'] ?? null,
            'reorder_quantity' => $row['reorder_quantity'] ?? null,
            'storage_location' => $row['storage_location'] ?? null,
            'shelf' => $row['shelf'] ?? null,
            'bin' => $row['bin'] ?? null,
            'is_vat_applicable' => isset($row['is_vat_applicable']) ? filter_var($row['is_vat_applicable'], FILTER_VALIDATE_BOOLEAN) : false,
            'vat_rate' => $row['vat_rate'] ?? 16.5,
            'tax_category' => $row['tax_category'] ?? 'standard',
            'primary_supplier_id' => $supplierId,
            'track_batches' => isset($row['track_batches']) ? filter_var($row['track_batches'], FILTER_VALIDATE_BOOLEAN) : false,
            'track_serial_numbers' => isset($row['track_serial_numbers']) ? filter_var($row['track_serial_numbers'], FILTER_VALIDATE_BOOLEAN) : false,
            'has_expiry' => isset($row['has_expiry']) ? filter_var($row['has_expiry'], FILTER_VALIDATE_BOOLEAN) : false,
            'is_active' => isset($row['is_active']) ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'name_chichewa' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'manufacturer_code' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'base_currency' => ['nullable', 'string', 'size:3'],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
            'unit' => ['nullable', 'string', 'max:50'],
            'min_stock_level' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
            'max_stock_level' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
            'reorder_point' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'shelf' => ['nullable', 'string', 'max:50'],
            'bin' => ['nullable', 'string', 'max:50'],
            'is_vat_applicable' => ['nullable', 'boolean'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_category' => ['nullable', 'string', 'max:50'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'track_batches' => ['nullable', 'boolean'],
            'track_serial_numbers' => ['nullable', 'boolean'],
            'has_expiry' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }
}
