<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Color;
use App\Models\Size;

class ColorSizeSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Seed Colors
        $colors = [
            ['name' => 'Black', 'code' => 'black', 'hex_code' => '#000000', 'sort_order' => 1],
            ['name' => 'White', 'code' => 'white', 'hex_code' => '#FFFFFF', 'sort_order' => 2],
            ['name' => 'Red', 'code' => 'red', 'hex_code' => '#FF0000', 'sort_order' => 3],
            ['name' => 'Blue', 'code' => 'blue', 'hex_code' => '#0000FF', 'sort_order' => 4],
            ['name' => 'Green', 'code' => 'green', 'hex_code' => '#00FF00', 'sort_order' => 5],
            ['name' => 'Yellow', 'code' => 'yellow', 'hex_code' => '#FFFF00', 'sort_order' => 6],
            ['name' => 'Pink', 'code' => 'pink', 'hex_code' => '#FFC0CB', 'sort_order' => 7],
            ['name' => 'Purple', 'code' => 'purple', 'hex_code' => '#800080', 'sort_order' => 8],
            ['name' => 'Orange', 'code' => 'orange', 'hex_code' => '#FFA500', 'sort_order' => 9],
            ['name' => 'Brown', 'code' => 'brown', 'hex_code' => '#A52A2A', 'sort_order' => 10],
            ['name' => 'Gray', 'code' => 'gray', 'hex_code' => '#808080', 'sort_order' => 11],
            ['name' => 'Navy', 'code' => 'navy', 'hex_code' => '#000080', 'sort_order' => 12],
        ];

        foreach ($colors as $color) {
            Color::firstOrCreate(
                ['code' => $color['code']],
                $color
            );
        }

        $this->command->info('Colors seeded successfully!');

        // Seed Sizes
        $sizes = [
            ['name' => 'Extra Small', 'code' => 'XS', 'sort_order' => 1],
            ['name' => 'Small', 'code' => 'S', 'sort_order' => 2],
            ['name' => 'Medium', 'code' => 'M', 'sort_order' => 3],
            ['name' => 'Large', 'code' => 'L', 'sort_order' => 4],
            ['name' => 'Extra Large', 'code' => 'XL', 'sort_order' => 5],
            ['name' => '2X Large', 'code' => '2XL', 'sort_order' => 6],
            ['name' => '3X Large', 'code' => '3XL', 'sort_order' => 7],
            ['name' => 'One Size', 'code' => 'OS', 'sort_order' => 8],
        ];

        foreach ($sizes as $size) {
            Size::firstOrCreate(
                ['code' => $size['code']],
                $size
            );
        }

        $this->command->info('Sizes seeded successfully!');
    }
}
