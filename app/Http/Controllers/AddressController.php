<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user.
     */
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get();

        return response()->json($addresses);
    }

    /**
     * Store a new address.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'is_default' => 'boolean',
        ]);

        DB::transaction(function () use ($request, $validated) {
            // If this is set as default, unset all other defaults
            if ($validated['is_default'] ?? false) {
                $request->user()->addresses()->update(['is_default' => false]);
            }

            $request->user()->addresses()->create($validated);
        });

        return response()->json([
            'message' => 'Address added successfully',
            'addresses' => $request->user()->addresses()->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get(),
        ], 201);
    }

    /**
     * Update an existing address.
     */
    public function update(Request $request, Address $address)
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'is_default' => 'boolean',
        ]);

        DB::transaction(function () use ($request, $address, $validated) {
            // If this is set as default, unset all other defaults
            if ($validated['is_default'] ?? false) {
                $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            $address->update($validated);
        });

        return response()->json([
            'message' => 'Address updated successfully',
            'addresses' => $request->user()->addresses()->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get(),
        ]);
    }

    /**
     * Delete an address.
     */
    public function destroy(Request $request, Address $address)
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully',
            'addresses' => $request->user()->addresses()->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get(),
        ]);
    }

    /**
     * Set an address as default.
     */
    public function setDefault(Request $request, Address $address)
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::transaction(function () use ($request, $address) {
            // Unset all other defaults
            $request->user()->addresses()->update(['is_default' => false]);
            
            // Set this one as default
            $address->update(['is_default' => true]);
        });

        return response()->json([
            'message' => 'Default address updated successfully',
            'addresses' => $request->user()->addresses()->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get(),
        ]);
    }
}
