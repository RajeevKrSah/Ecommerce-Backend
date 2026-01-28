<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic devices and accessories', 'sort_order' => 1],
            ['name' => 'Clothing', 'description' => 'Fashion and apparel', 'sort_order' => 2],
            ['name' => 'Books', 'description' => 'Books and publications', 'sort_order' => 3],
            ['name' => 'Home & Garden', 'description' => 'Home improvement and garden supplies', 'sort_order' => 4],
            ['name' => 'Sports', 'description' => 'Sports equipment and accessories', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => \Illuminate\Support\Str::slug($category['name']),
                'description' => $category['description'],
                'is_active' => true,
                'sort_order' => $category['sort_order'],
            ]);
        }
    }
}
