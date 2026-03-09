<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SizeController extends Controller
{
    /**
     * Display a listing of sizes
     */
    public function index(Request $request)
    {
        $query = Size::query();

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
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
        $sizes = $query->paginate($perPage);

        return response()->json($sizes);
    }

    /**
     * Store a newly created size
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sizes,name',
            'code' => 'required|string|max:50|unique:sizes,code',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $size = Size::create($validated);

        return response()->json([
            'message' => 'Size created successfully',
            'size' => $size,
        ], 201);
    }

    /**
     * Display the specified size
     */
    public function show(Size $size)
    {
        $size->load('products');
        
        return response()->json([
            'size' => $size,
            'products_count' => $size->products()->count(),
        ]);
    }

    /**
     * Update the specified size
     */
    public function update(Request $request, Size $size)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('sizes')->ignore($size->id)],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('sizes')->ignore($size->id)],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $size->update($validated);

        return response()->json([
            'message' => 'Size updated successfully',
            'size' => $size,
        ]);
    }

    /**
     * Remove the specified size
     */
    public function destroy(Size $size)
    {
        // Check if size is used by any products
        $productsCount = $size->products()->count();
        
        if ($productsCount > 0) {
            return response()->json([
                'message' => "Cannot delete size. It is used by {$productsCount} product(s).",
            ], 422);
        }

        $size->delete();

        return response()->json([
            'message' => 'Size deleted successfully',
        ]);
    }

    /**
     * Toggle size active status
     */
    public function toggleStatus(Size $size)
    {
        $size->update(['is_active' => !$size->is_active]);

        return response()->json([
            'message' => 'Size status updated successfully',
            'size' => $size,
        ]);
    }

    /**
     * Bulk update sort order
     */
    public function updateSortOrder(Request $request)
    {
        $validated = $request->validate([
            'sizes' => 'required|array',
            'sizes.*.id' => 'required|exists:sizes,id',
            'sizes.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['sizes'] as $sizeData) {
            Size::where('id', $sizeData['id'])
                ->update(['sort_order' => $sizeData['sort_order']]);
        }

        return response()->json([
            'message' => 'Sort order updated successfully',
        ]);
    }
}
