<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Color extends Model
{
    protected $fillable = [
        'name',
        'code',
        'hex_code',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get products that have this color
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_colors')
            ->withTimestamps();
    }

    /**
     * Scope to get only active colors
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
