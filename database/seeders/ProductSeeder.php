<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Get categories
        $electronics = Category::where('slug', 'electronics')->first();
        $clothing = Category::where('slug', 'clothing')->first();
        $books = Category::where('slug', 'books')->first();
        $homeGarden = Category::where('slug', 'home-garden')->first();
        $sports = Category::where('slug', 'sports')->first();

        // Get colors
        $black = Color::where('code', 'black')->first();
        $white = Color::where('code', 'white')->first();
        $red = Color::where('code', 'red')->first();
        $blue = Color::where('code', 'blue')->first();
        $green = Color::where('code', 'green')->first();
        $gray = Color::where('code', 'gray')->first();
        $navy = Color::where('code', 'navy')->first();
        $pink = Color::where('code', 'pink')->first();

        // Get sizes
        $xs = Size::where('code', 'XS')->first();
        $s = Size::where('code', 'S')->first();
        $m = Size::where('code', 'M')->first();
        $l = Size::where('code', 'L')->first();
        $xl = Size::where('code', 'XL')->first();
        $xxl = Size::where('code', '2XL')->first();
        $os = Size::where('code', 'OS')->first();

        $products = [
            // Electronics
            [
                'name' => 'Wireless Headphones',
                'slug' => 'wireless-headphones',
                'description' => 'High-quality wireless headphones with active noise cancellation, 30-hour battery life, and premium sound quality. Perfect for music lovers and professionals.',
                'short_description' => 'Premium wireless headphones with noise cancellation',
                'price' => 199.99,
                'sale_price' => 149.99,
                'sku' => 'WH-001',
                'stock_quantity' => 50,
                'is_featured' => true,
                'category_id' => $electronics->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800', 'is_primary' => true, 'sort_order' => 0],
                    ['image_url' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=800', 'is_primary' => false, 'sort_order' => 1],
                ],
                'colors' => [$black->id, $white->id, $gray->id],
            ],
            [
                'name' => 'Smart Watch Pro',
                'slug' => 'smart-watch-pro',
                'description' => 'Feature-rich smartwatch with fitness tracking, heart rate monitor, GPS, and water resistance. Track your health and stay connected.',
                'short_description' => 'Advanced fitness tracking smartwatch',
                'price' => 299.99,
                'sku' => 'SW-001',
                'stock_quantity' => 30,
                'is_featured' => true,
                'category_id' => $electronics->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800', 'is_primary' => true, 'sort_order' => 0],
                    ['image_url' => 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=800', 'is_primary' => false, 'sort_order' => 1],
                ],
                'colors' => [$black->id, $blue->id, $red->id],
            ],
            [
                'name' => 'Bluetooth Speaker',
                'slug' => 'bluetooth-speaker',
                'description' => 'Portable Bluetooth speaker with 360-degree sound, waterproof design, and 12-hour battery life. Perfect for outdoor adventures.',
                'short_description' => 'Waterproof portable Bluetooth speaker',
                'price' => 79.99,
                'sale_price' => 59.99,
                'sku' => 'BS-001',
                'stock_quantity' => 75,
                'is_featured' => false,
                'category_id' => $electronics->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
                'colors' => [$black->id, $blue->id, $red->id, $green->id],
            ],
            [
                'name' => 'Wireless Mouse',
                'slug' => 'wireless-mouse',
                'description' => 'Ergonomic wireless mouse with precision tracking, long battery life, and comfortable grip. Ideal for work and gaming.',
                'short_description' => 'Ergonomic wireless mouse',
                'price' => 39.99,
                'sku' => 'WM-001',
                'stock_quantity' => 100,
                'is_featured' => false,
                'category_id' => $electronics->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
                'colors' => [$black->id, $white->id],
            ],

            // Clothing
            [
                'name' => 'Premium Cotton T-Shirt',
                'slug' => 'premium-cotton-t-shirt',
                'description' => '100% organic cotton t-shirt with a comfortable fit. Soft, breathable, and perfect for everyday wear. Available in multiple colors and sizes.',
                'short_description' => 'Comfortable organic cotton tee',
                'price' => 29.99,
                'sale_price' => 19.99,
                'sku' => 'TS-001',
                'stock_quantity' => 200,
                'is_featured' => true,
                'category_id' => $clothing->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800', 'is_primary' => true, 'sort_order' => 0],
                    ['image_url' => 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800', 'is_primary' => false, 'sort_order' => 1],
                ],
                'colors' => [$black->id, $white->id, $blue->id, $red->id, $gray->id],
                'sizes' => [$s->id, $m->id, $l->id, $xl->id, $xxl->id],
            ],
            [
                'name' => 'Denim Jeans',
                'slug' => 'denim-jeans',
                'description' => 'Classic fit denim jeans made from premium quality fabric. Durable, comfortable, and stylish for any occasion.',
                'short_description' => 'Classic fit premium denim jeans',
                'price' => 79.99,
                'sale_price' => 59.99,
                'sku' => 'DJ-001',
                'stock_quantity' => 80,
                'is_featured' => true,
                'category_id' => $clothing->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
                'colors' => [$blue->id, $black->id],
                'sizes' => [$s->id, $m->id, $l->id, $xl->id],
            ],
            [
                'name' => 'Hoodie Sweatshirt',
                'slug' => 'hoodie-sweatshirt',
                'description' => 'Cozy hoodie sweatshirt with soft fleece lining. Perfect for casual wear and staying warm in cooler weather.',
                'short_description' => 'Comfortable fleece-lined hoodie',
                'price' => 49.99,
                'sku' => 'HS-001',
                'stock_quantity' => 60,
                'is_featured' => false,
                'category_id' => $clothing->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
                'colors' => [$black->id, $gray->id, $navy->id, $red->id],
                'sizes' => [$s->id, $m->id, $l->id, $xl->id, $xxl->id],
            ],
            [
                'name' => 'Summer Dress',
                'slug' => 'summer-dress',
                'description' => 'Light and breezy summer dress with floral print. Perfect for warm weather and casual outings.',
                'short_description' => 'Floral print summer dress',
                'price' => 59.99,
                'sale_price' => 44.99,
                'sku' => 'SD-001',
                'stock_quantity' => 45,
                'is_featured' => true,
                'category_id' => $clothing->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
                'colors' => [$blue->id, $pink->id, $white->id],
                'sizes' => [$xs->id, $s->id, $m->id, $l->id, $xl->id],
            ],

            // Books
            [
                'name' => 'Modern Web Development',
                'slug' => 'modern-web-development',
                'description' => 'Comprehensive guide to modern web development with React, Node.js, and best practices. Includes practical examples and real-world projects.',
                'short_description' => 'Learn modern web development',
                'price' => 49.99,
                'sku' => 'BK-001',
                'stock_quantity' => 50,
                'is_featured' => true,
                'category_id' => $books->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
            ],
            [
                'name' => 'Python Programming',
                'slug' => 'python-programming',
                'description' => 'Complete Python programming guide for beginners to advanced. Learn data structures, algorithms, and real-world applications.',
                'short_description' => 'Master Python programming',
                'price' => 44.99,
                'sku' => 'BK-002',
                'stock_quantity' => 40,
                'is_featured' => false,
                'category_id' => $books->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
            ],
            [
                'name' => 'Design Thinking',
                'slug' => 'design-thinking',
                'description' => 'Explore the principles of design thinking and user experience. Learn to create intuitive and beautiful digital products.',
                'short_description' => 'UX and design principles',
                'price' => 39.99,
                'sale_price' => 29.99,
                'sku' => 'BK-003',
                'stock_quantity' => 35,
                'is_featured' => false,
                'category_id' => $books->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
            ],

            // Home & Garden
            [
                'name' => 'Indoor Plant Set',
                'slug' => 'indoor-plant-set',
                'description' => 'Set of 3 low-maintenance indoor plants perfect for home or office. Includes decorative pots and care instructions.',
                'short_description' => 'Set of 3 indoor plants with pots',
                'price' => 89.99,
                'sale_price' => 69.99,
                'sku' => 'HG-001',
                'stock_quantity' => 25,
                'is_featured' => true,
                'category_id' => $homeGarden->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1485955900006-10f4d324d411?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
            ],
            [
                'name' => 'LED Desk Lamp',
                'slug' => 'led-desk-lamp',
                'description' => 'Modern LED desk lamp with adjustable brightness and color temperature. Energy-efficient and eye-friendly design.',
                'short_description' => 'Adjustable LED desk lamp',
                'price' => 45.99,
                'sku' => 'HG-002',
                'stock_quantity' => 55,
                'is_featured' => false,
                'category_id' => $homeGarden->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
                'colors' => [$black->id, $white->id],
            ],

            // Sports
            [
                'name' => 'Yoga Mat',
                'slug' => 'yoga-mat',
                'description' => 'Premium non-slip yoga mat with extra cushioning. Eco-friendly material, perfect for yoga, pilates, and fitness exercises.',
                'short_description' => 'Non-slip premium yoga mat',
                'price' => 34.99,
                'sale_price' => 24.99,
                'sku' => 'SP-001',
                'stock_quantity' => 70,
                'is_featured' => true,
                'category_id' => $sports->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
                'colors' => [$blue->id, $pink->id, $green->id, $black->id],
            ],
            [
                'name' => 'Resistance Bands Set',
                'slug' => 'resistance-bands-set',
                'description' => 'Set of 5 resistance bands with different resistance levels. Perfect for strength training, stretching, and rehabilitation.',
                'short_description' => '5-piece resistance bands set',
                'price' => 29.99,
                'sku' => 'SP-002',
                'stock_quantity' => 90,
                'is_featured' => false,
                'category_id' => $sports->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1598289431512-b97b0917affc?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
            ],
            [
                'name' => 'Water Bottle',
                'slug' => 'water-bottle',
                'description' => 'Insulated stainless steel water bottle keeps drinks cold for 24 hours or hot for 12 hours. BPA-free and leak-proof.',
                'short_description' => 'Insulated stainless steel bottle',
                'price' => 24.99,
                'sku' => 'SP-003',
                'stock_quantity' => 120,
                'is_featured' => false,
                'category_id' => $sports->id,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=800', 'is_primary' => true, 'sort_order' => 0],
                ],
                'colors' => [$black->id, $blue->id, $red->id, $white->id, $green->id],
            ],
        ];

        foreach ($products as $productData) {
            $images = $productData['images'] ?? [];
            $colors = $productData['colors'] ?? [];
            $sizes = $productData['sizes'] ?? [];
            
            unset($productData['images'], $productData['colors'], $productData['sizes']);

            $product = Product::create($productData);

            // Add images
            foreach ($images as $image) {
                $product->images()->create($image);
            }

            // Attach colors
            if (!empty($colors)) {
                $product->colors()->attach($colors);
            }

            // Attach sizes
            if (!empty($sizes)) {
                $product->sizes()->attach($sizes);
            }
        }

        $this->command->info('Products seeded successfully with ' . count($products) . ' products!');
    }
}
