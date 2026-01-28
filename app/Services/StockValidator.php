<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockValidator
{
    /**
     * Check if the requested quantity is available for a product.
     *
     * @param int $productId The product ID to check
     * @param int $quantity The requested quantity
     * @return bool True if sufficient stock is available, false otherwise
     */
    public function isAvailable(int $productId, int $quantity): bool
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return false;
        }
        
        return $product->stock_quantity >= $quantity;
    }

    /**
     * Get the current stock level for a product.
     *
     * @param int $productId The product ID to check
     * @return int The current stock level, or 0 if product doesn't exist
     */
    public function getStockLevel(int $productId): int
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return 0;
        }
        
        return $product->stock_quantity;
    }

    /**
     * Validate multiple items for stock availability.
     *
     * @param array $items Array of items with 'productId' and 'quantity' keys
     * @return array Array of validation results with product details
     */
    public function validateItems(array $items): array
    {
        $results = [];
        
        foreach ($items as $item) {
            $productId = $item['productId'] ?? null;
            $quantity = $item['quantity'] ?? 0;
            
            if (!$productId || $quantity <= 0) {
                $results[] = [
                    'productId' => $productId,
                    'available' => false,
                    'currentStock' => 0,
                    'requested' => $quantity,
                    'error' => 'Invalid product ID or quantity',
                ];
                continue;
            }
            
            $product = Product::find($productId);
            
            if (!$product) {
                $results[] = [
                    'productId' => $productId,
                    'available' => false,
                    'currentStock' => 0,
                    'requested' => $quantity,
                    'error' => 'Product not found',
                ];
                continue;
            }
            
            $available = $product->stock_quantity >= $quantity;
            
            $results[] = [
                'productId' => $productId,
                'available' => $available,
                'currentStock' => $product->stock_quantity,
                'requested' => $quantity,
                'error' => $available ? null : 'Insufficient stock',
            ];
        }
        
        return $results;
    }
}
