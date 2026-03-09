<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RBACSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'access-profile',
            'manage-own-cart',
            'manage-own-orders',
            'manage-own-addresses',
            
            // Admin permissions
            'access-admin-dashboard',
            'manage-products',
            'manage-orders',
            'manage-inventory',
            'view-all-orders',
            'update-order-status',
            
            // Super Admin permissions
            'manage-users',
            'manage-roles',
            'promote-users',
            'demote-users',
            'view-audit-logs',
            'manage-admins',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // USER ROLE
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo([
            'access-profile',
            'manage-own-cart',
            'manage-own-orders',
            'manage-own-addresses',
        ]);

        // ADMIN ROLE
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'access-profile',
            'manage-own-cart',
            'manage-own-orders',
            'manage-own-addresses',
            'access-admin-dashboard',
            'manage-products',
            'manage-orders',
            'manage-inventory',
            'view-all-orders',
            'update-order-status',
        ]);

        // SUPER ADMIN ROLE
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // Migrate existing users with role column to Spatie roles
        $this->migrateExistingRoles();

        $this->command->info('RBAC system initialized successfully!');
        $this->command->info('Roles created: user, admin, super_admin');
        $this->command->info('Permissions assigned to each role');
    }

    /**
     * Migrate existing users from role column to Spatie roles
     */
    private function migrateExistingRoles(): void
    {
        // Check if role column still exists
        if (!Schema::hasColumn('users', 'role')) {
            $this->command->info('Role column already removed. Skipping migration.');
            return;
        }

        $users = User::all();

        foreach ($users as $user) {
            $roleName = $user->role ?? 'user';
            
            // Ensure role exists
            if (!in_array($roleName, ['user', 'admin', 'super_admin'])) {
                $roleName = 'user';
            }

            // Assign role using Spatie
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
        }

        $this->command->info("Migrated {$users->count()} users to Spatie permission system");
    }
}
