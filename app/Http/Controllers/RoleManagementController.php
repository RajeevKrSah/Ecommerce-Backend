<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RoleChangeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RoleManagementController extends Controller
{
    /**
     * Promote user to a higher role
     */
    public function promote(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,super_admin',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Prevent self-promotion
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot promote yourself'
            ], 403);
        }

        $newRole = $request->role;
        $oldRole = $user->getPrimaryRole();

        // Validate promotion path
        if (!$this->isValidPromotion($oldRole, $newRole)) {
            return response()->json([
                'message' => "Invalid promotion from {$oldRole} to {$newRole}"
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Remove old role and assign new role
            $user->syncRoles([$newRole]);

            // Log the role change
            RoleChangeLog::logChange(
                performedBy: $request->user()->id,
                targetUserId: $user->id,
                oldRole: $oldRole,
                newRole: $newRole,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                reason: $request->reason
            );

            DB::commit();

            Log::info('User promoted', [
                'performed_by' => $request->user()->id,
                'target_user' => $user->id,
                'old_role' => $oldRole,
                'new_role' => $newRole,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => "User promoted to {$newRole} successfully",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $newRole,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('User promotion failed', [
                'error' => $e->getMessage(),
                'target_user' => $user->id,
            ]);

            return response()->json([
                'message' => 'Promotion failed'
            ], 500);
        }
    }

    /**
     * Demote user to a lower role
     */
    public function demote(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:user,admin',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Prevent self-demotion
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot demote yourself'
            ], 403);
        }

        $newRole = $request->role;
        $oldRole = $user->getPrimaryRole();

        // Validate demotion path
        if (!$this->isValidDemotion($oldRole, $newRole)) {
            return response()->json([
                'message' => "Invalid demotion from {$oldRole} to {$newRole}"
            ], 422);
        }

        // Prevent removing last super_admin
        if ($oldRole === 'super_admin' && $this->isLastSuperAdmin()) {
            return response()->json([
                'message' => 'Cannot demote the last super admin'
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Remove old role and assign new role
            $user->syncRoles([$newRole]);

            // Log the role change
            RoleChangeLog::logChange(
                performedBy: $request->user()->id,
                targetUserId: $user->id,
                oldRole: $oldRole,
                newRole: $newRole,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                reason: $request->reason
            );

            DB::commit();

            Log::info('User demoted', [
                'performed_by' => $request->user()->id,
                'target_user' => $user->id,
                'old_role' => $oldRole,
                'new_role' => $newRole,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => "User demoted to {$newRole} successfully",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $newRole,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('User demotion failed', [
                'error' => $e->getMessage(),
                'target_user' => $user->id,
            ]);

            return response()->json([
                'message' => 'Demotion failed'
            ], 500);
        }
    }

    /**
     * Get role change logs
     */
    public function getRoleChangeLogs(Request $request)
    {
        $logs = RoleChangeLog::with(['performer', 'targetUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($logs);
    }

    /**
     * Get role change logs for a specific user
     */
    public function getUserRoleChangeLogs(Request $request, User $user)
    {
        $logs = RoleChangeLog::with(['performer'])
            ->where('target_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'current_role' => $user->getPrimaryRole(),
            ],
            'logs' => $logs,
        ]);
    }

    /**
     * Check if promotion is valid
     */
    private function isValidPromotion(string $oldRole, string $newRole): bool
    {
        $validPromotions = [
            'user' => ['admin', 'super_admin'],
            'admin' => ['super_admin'],
        ];

        return isset($validPromotions[$oldRole]) && in_array($newRole, $validPromotions[$oldRole]);
    }

    /**
     * Check if demotion is valid
     */
    private function isValidDemotion(string $oldRole, string $newRole): bool
    {
        $validDemotions = [
            'super_admin' => ['admin', 'user'],
            'admin' => ['user'],
        ];

        return isset($validDemotions[$oldRole]) && in_array($newRole, $validDemotions[$oldRole]);
    }

    /**
     * Check if this is the last super admin
     */
    private function isLastSuperAdmin(): bool
    {
        return User::role('super_admin')->count() <= 1;
    }
}
