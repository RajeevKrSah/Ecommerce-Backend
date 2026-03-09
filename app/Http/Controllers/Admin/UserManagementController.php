<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RoleChangeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role !== 'all') {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'locked') {
                $query->where('locked_until', '>', now());
            } elseif ($request->status === 'active') {
                $query->where(function($q) {
                    $q->whereNull('locked_until')
                      ->orWhere('locked_until', '<=', now());
                });
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load(['roles', 'orders', 'addresses']);
        
        return response()->json([
            'user' => $user,
            'orders_count' => $user->orders()->count(),
            'total_spent' => $user->orders()->where('payment_status', 'paid')->sum('total'),
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:8',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh('roles'),
        ]);
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 422);
        }

        // Prevent deleting last super admin
        if ($user->hasRole('super_admin')) {
            $superAdminCount = User::role('super_admin')->count();
            if ($superAdminCount <= 1) {
                return response()->json([
                    'message' => 'Cannot delete the last super admin',
                ], 422);
            }
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Lock/Unlock user account
     */
    public function toggleLock(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot lock your own account',
            ], 422);
        }

        if ($user->locked_until && $user->locked_until > now()) {
            // Unlock
            $user->update([
                'locked_until' => null,
                'failed_login_attempts' => 0,
            ]);
            $message = 'User account unlocked successfully';
        } else {
            // Lock for 24 hours
            $user->update([
                'locked_until' => now()->addHours(24),
            ]);
            $message = 'User account locked successfully';
        }

        return response()->json([
            'message' => $message,
            'user' => $user,
        ]);
    }

    /**
     * Get user statistics
     */
    public function statistics()
    {
        $stats = [
            'total_users' => User::count(),
            'users_by_role' => [
                'user' => User::role('user')->count(),
                'admin' => User::role('admin')->count(),
                'super_admin' => User::role('super_admin')->count(),
            ],
            'locked_accounts' => User::where('locked_until', '>', now())->count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'active_users' => User::whereHas('orders')->distinct()->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get role change logs
     */
    public function roleChangeLogs(Request $request)
    {
        $query = RoleChangeLog::with(['performer', 'targetUser'])
            ->orderBy('created_at', 'desc');

        if ($request->has('user_id')) {
            $query->where('target_user_id', $request->user_id);
        }

        $logs = $query->paginate(20);

        return response()->json($logs);
    }
}
