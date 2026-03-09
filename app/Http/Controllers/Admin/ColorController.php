<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ColorController extends Controller
{
    /**
     * Display a listing of colors
     */
    public function index(Request $request)
    {
        $query = Color::query();

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('hex_code', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $colors = $query->paginate($perPage);

        return response()->json($colors);
    }

    /**
     * Store a newly created color
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:colors,name',
            'code' => 'required|string|max:50|unique:colors,code',
            'hex_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $color = Color::create($validated);

        return response()->json([
            'message' => 'Color created successfully',
            'color' => $color,
        ], 201);
    }

    /**
     * Display the specified color
     */
    public function show(Color $color)
    {
        $color->load('products');
        
        return response()->json([
            'color' => $color,
            'products_count' => $color->products()->count(),
        ]);
    }

    /**
     * Update the specified color
     */
    public function update(Request $request, Color $color)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('colors')->ignore($color->id)],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('colors')->ignore($color->id)],
            'hex_code' => 'sometimes|required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $color->update($validated);

        return response()->json([
            'message' => 'Color updated successfully',
            'color' => $color,
        ]);
    }

    /**
     * Remove the specified color
     */
    public function destroy(Color $color)
    {
        // Check if color is used by any products
        $productsCount = $color->products()->count();
        
        if ($productsCount > 0) {
            return response()->json([
                'message' => "Cannot delete color. It is used by {$productsCount} product(s).",
            ], 422);
        }

        $color->delete();

        return response()->json([
            'message' => 'Color deleted successfully',
        ]);
    }

    /**
     * Toggle color active status
     */
    public function toggleStatus(Color $color)
    {
        $color->update(['is_active' => !$color->is_active]);

        return response()->json([
            'message' => 'Color status updated successfully',
            'color' => $color,
        ]);
    }

    /**
     * Bulk update sort order
     */
    public function updateSortOrder(Request $request)
    {
        $validated = $request->validate([
            'colors' => 'required|array',
            'colors.*.id' => 'required|exists:colors,id',
            'colors.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['colors'] as $colorData) {
            Color::where('id', $colorData['id'])
                ->update(['sort_order' => $colorData['sort_order']]);
        }

        return response()->json([
            'message' => 'Sort order updated successfully',
        ]);
    }
}
