<?php

/**
 * Quick script to create admin users
 * Run: php create-admin-users.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "\n=================================\n";
echo "Creating Admin Users\n";
echo "=================================\n\n";

try {
    // Create Super Admin
    $superAdmin = User::updateOrCreate(
        ['email' => 'superadmin@test.com'],
        [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]
    );
    
    // Ensure role exists and assign
    if (!$superAdmin->hasRole('super_admin')) {
        $superAdmin->syncRoles(['super_admin']);
    }
    
    echo "✓ Super Admin created/updated\n";
    echo "  Email: superadmin@test.com\n";
    echo "  Password: password\n";
    echo "  Role: super_admin\n\n";

    // Create Admin
    $admin = User::updateOrCreate(
        ['email' => 'admin@test.com'],
        [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]
    );
    
    if (!$admin->hasRole('admin')) {
        $admin->syncRoles(['admin']);
    }
    
    echo "✓ Admin created/updated\n";
    echo "  Email: admin@test.com\n";
    echo "  Password: password\n";
    echo "  Role: admin\n\n";

    // Create Regular User
    $user = User::updateOrCreate(
        ['email' => 'user@test.com'],
        [
            'name' => 'Test User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]
    );
    
    if (!$user->hasRole('user')) {
        $user->syncRoles(['user']);
    }
    
    echo "✓ Regular User created/updated\n";
    echo "  Email: user@test.com\n";
    echo "  Password: password\n";
    echo "  Role: user\n\n";

    echo "=================================\n";
    echo "All accounts created successfully!\n";
    echo "=================================\n\n";

    // Verify
    echo "Verification:\n";
    echo "---------------------------------\n";
    
    $adminUsers = User::role(['admin', 'super_admin'])->get(['id', 'name', 'email']);
    echo "Admin users in database: " . $adminUsers->count() . "\n";
    
    foreach ($adminUsers as $u) {
        $role = $u->roles->first()->name ?? 'no role';
        echo "  - {$u->name} ({$u->email}) - Role: {$role}\n";
    }
    
    echo "\n";
    echo "You can now login at:\n";
    echo "  Frontend: http://localhost:3000/admin/login\n";
    echo "  Use: admin@test.com / password\n";
    echo "  Or: superadmin@test.com / password\n\n";

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nMake sure you have:\n";
    echo "1. Run migrations: php artisan migrate\n";
    echo "2. Run RBAC seeder: php artisan db:seed --class=RBACSeeder\n";
    echo "3. Database is accessible\n\n";
    exit(1);
}
