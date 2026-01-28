<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = Cart::with(['items.product.images'])
            ->firstOrCreate(['user_id' => $request->user()->id]);

        return response()->json([
            'cart' => [
                'id' => $cart->id,
                'items' => $cart->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'slug' => $item->product->slug,
                            'price' => $item->product->price,
                            'sale_price' => $item->product->sale_price,
                            'current_price' => $item->product->current_price,
                            'stock_quantity' => $item->product->stock_quantity,
                            'in_stock' => $item->product->in_stock,
                            'image' => $item->product->images->first()?->image_url,
                        ],
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                    ];
                }),
                'subtotal' => $cart->subtotal,
                'total_items' => $cart->total_items,
            ],
        ]);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Validate stock
        if ($product->stock_quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock available',
                'available_stock' => $product->stock_quantity,
            ], 422);
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        DB::beginTransaction();
        try {
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($cartItem) {
                $newQuantity = $cartItem->quantity + $validated['quantity'];
                
                if ($product->stock_quantity < $newQuantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Cannot add more items. Insufficient stock',
                        'available_stock' => $product->stock_quantity,
                        'current_in_cart' => $cartItem->quantity,
                    ], 422);
                }

                $cartItem->update(['quantity' => $newQuantity]);
            } else {
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $validated['quantity'],
                    'price' => $product->current_price,
                ]);
            }

            DB::commit();

            $cart->load(['items.product.images']);

            return response()->json([
                'message' => 'Product added to cart',
                'cart' => [
                    'id' => $cart->id,
                    'items' => $cart->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'slug' => $item->product->slug,
                                'price' => $item->product->price,
                                'sale_price' => $item->product->sale_price,
                                'current_price' => $item->product->current_price,
                                'stock_quantity' => $item->product->stock_quantity,
                                'in_stock' => $item->product->in_stock,
                                'image' => $item->product->images->first()?->image_url,
                            ],
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->total,
                        ];
                    }),
                    'subtotal' => $cart->subtotal,
                    'total_items' => $cart->total_items,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add item to cart'], 500);
        }
    }

    public function update(Request $request, CartItem $cartItem)
    {
        if ($cartItem->cart->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        if ($cartItem->product->stock_quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock available',
                'available_stock' => $cartItem->product->stock_quantity,
            ], 422);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);
        $cart = $cartItem->cart->load(['items.product.images']);

        return response()->json([
            'message' => 'Cart updated',
            'cart' => [
                'id' => $cart->id,
                'items' => $cart->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'slug' => $item->product->slug,
                            'price' => $item->product->price,
                            'sale_price' => $item->product->sale_price,
                            'current_price' => $item->product->current_price,
                            'stock_quantity' => $item->product->stock_quantity,
                            'in_stock' => $item->product->in_stock,
                            'image' => $item->product->images->first()?->image_url,
                        ],
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                    ];
                }),
                'subtotal' => $cart->subtotal,
                'total_items' => $cart->total_items,
            ],
        ]);
    }

    public function remove(Request $request, CartItem $cartItem)
    {
        if ($cartItem->cart->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cart = $cartItem->cart;
        $cartItem->delete();
        $cart->load(['items.product.images']);

        return response()->json([
            'message' => 'Item removed from cart',
            'cart' => [
                'id' => $cart->id,
                'items' => $cart->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'slug' => $item->product->slug,
                            'price' => $item->product->price,
                            'sale_price' => $item->product->sale_price,
                            'current_price' => $item->product->current_price,
                            'stock_quantity' => $item->product->stock_quantity,
                            'in_stock' => $item->product->in_stock,
                            'image' => $item->product->images->first()?->image_url,
                        ],
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                    ];
                }),
                'subtotal' => $cart->subtotal,
                'total_items' => $cart->total_items,
            ],
        ]);
    }

    public function clear(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();
        
        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json(['message' => 'Cart cleared']);
    }
}
