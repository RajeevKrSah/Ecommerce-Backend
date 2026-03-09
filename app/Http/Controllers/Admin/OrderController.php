<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        $order->load(['user', 'items.product.images', 'address']);
        
        return response()->json([
            'order' => $order,
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        // Log status change
        \Log::info('Order status updated', [
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'admin_id' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->fresh(['user', 'items.product']),
        ]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $order->update(['payment_status' => $validated['payment_status']]);

        if ($validated['payment_status'] === 'paid' && !$order->paid_at) {
            $order->update(['paid_at' => now()]);
        }

        return response()->json([
            'message' => 'Payment status updated successfully',
            'order' => $order,
        ]);
    }

    /**
     * Get order statistics
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $stats = [
            'total_orders' => Order::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'total_revenue' => Order::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('payment_status', 'paid')
                ->sum('total'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'average_order_value' => Order::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('payment_status', 'paid')
                ->avg('total'),
        ];

        return response()->json($stats);
    }

    /**
     * Export orders
     */
    public function export(Request $request)
    {
        // This would typically generate a CSV or Excel file
        // For now, return JSON data
        $orders = Order::with(['user', 'items.product'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->get();

        return response()->json([
            'message' => 'Export data prepared',
            'orders' => $orders,
        ]);
    }
}
