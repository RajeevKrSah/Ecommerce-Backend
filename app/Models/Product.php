<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'sale_price',
        'sku',
        'stock_quantity',
        'is_active',
        'is_featured',
        'category_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $appends = ['current_price', 'in_stock'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'product_colors')
            ->withTimestamps();
    }

    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class, 'product_sizes')
            ->withTimestamps();
    }

    public function getCurrentPriceAttribute(): float
    {
        return $this->sale_price ?? $this->price;
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }
}
