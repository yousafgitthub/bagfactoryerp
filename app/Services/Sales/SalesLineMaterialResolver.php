<?php

namespace App\Services\Sales;

use App\Models\BagProduct;
use App\Models\BagProductVariant;
class SalesLineMaterialResolver
{
    /**
     * @return array{
     *     bag_product_id: int|null,
     *     bag_product_variant_id: int|null,
     *     material_id: int,
     *     material_variant_id: int|null,
     *     unit_of_measurement_id: int,
     *     size_label: string|null,
     *     color_label: string|null,
     * }
     */
    public function resolve(array $row): array
    {
        $variantId = isset($row['bag_product_variant_id']) ? (int) $row['bag_product_variant_id'] : null;
        $productId = isset($row['bag_product_id']) ? (int) $row['bag_product_id'] : null;

        if ($variantId) {
            $variant = BagProductVariant::query()->with(['product.color', 'color'])->findOrFail($variantId);
            $product = $variant->product;
            $materialId = $variant->saleable_material_id;
            if (! $materialId) {
                throw new SalesException('Bag variant has no saleable inventory material linked.');
            }

            return [
                'bag_product_id' => $product?->id,
                'bag_product_variant_id' => $variant->id,
                'material_id' => (int) $materialId,
                'material_variant_id' => $variant->saleable_material_variant_id,
                'unit_of_measurement_id' => (int) ($product?->unit_of_measurement_id ?? $row['unit_of_measurement_id']),
                'size_label' => $product?->size_label,
                'color_label' => $variant->color?->name ?? $product?->color?->name,
            ];
        }

        if (! empty($row['material_id'])) {
            return [
                'bag_product_id' => $productId,
                'bag_product_variant_id' => null,
                'material_id' => (int) $row['material_id'],
                'material_variant_id' => $row['material_variant_id'] ?? null,
                'unit_of_measurement_id' => (int) $row['unit_of_measurement_id'],
                'size_label' => $row['size_label'] ?? null,
                'color_label' => $row['color_label'] ?? null,
            ];
        }

        if ($productId) {
            $product = BagProduct::query()->with('color')->findOrFail($productId);
            throw new SalesException('Select a bag product variant or finished-goods material for product '.$product->code);
        }

        throw new SalesException('Sales line requires a product variant or material.');
    }
}