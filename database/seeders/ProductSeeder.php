<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $electronics = Category::where('slug', 'electronics')->first();
        $clothing = Category::where('slug', 'clothing')->first();
        $books = Category::where('slug', 'books')->first();

        $products = [
            [
                'name' => 'Wireless Headphones',
                'slug' => 'wireless-headphones',
                'description' => 'High-quality wireless headphones with noise cancellation and 30-hour battery life.',
                'short_description' => 'Premium wireless headphones with noise cancellation',
                'price' => 199.99,
                'sale_price' => 149.99,
                'sku' => 'WH-001',
                'stock_quantity' => 50,
                'is_featured' => true,
                'category_id' => $electronics->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500', 'is_primary' => true, 'sort_order' => 0],
                ]
            ],
            [
                'name' => 'Smart Watch',
                'slug' => 'smart-watch',
                'description' => 'Feature-rich smartwatch with fitness tracking, heart rate monitor, and GPS.',
                'short_description' => 'Advanced fitness tracking smartwatch',
                'price' => 299.99,
                'sku' => 'SW-001',
                'stock_quantity' => 30,
                'is_featured' => true,
                'category_id' => $electronics->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500', 'is_primary' => true, 'sort_order' => 0],
                ]
            ],
            [
                'name' => 'Cotton T-Shirt',
                'slug' => 'cotton-t-shirt',
                'description' => '100% organic cotton t-shirt, comfortable and breathable.',
                'short_description' => 'Comfortable organic cotton tee',
                'price' => 29.99,
                'sale_price' => 19.99,
                'sku' => 'TS-001',
                'stock_quantity' => 100,
                'category_id' => $clothing->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500', 'is_primary' => true, 'sort_order' => 0],
                ]
            ],
            [
                'name' => 'Programming Book',
                'slug' => 'programming-book',
                'description' => 'Comprehensive guide to modern web development with practical examples.',
                'short_description' => 'Learn modern web development',
                'price' => 49.99,
                'sku' => 'BK-001',
                'stock_quantity' => 25,
                'category_id' => $books->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=500', 'is_primary' => true, 'sort_order' => 0],
                ]
            ],
        ];

        foreach ($products as $productData) {
            $images = $productData['images'];
            unset($productData['images']);

            $product = Product::create($productData);

            foreach ($images as $image) {
                $product->images()->create($image);
            }
        }
    }
}
