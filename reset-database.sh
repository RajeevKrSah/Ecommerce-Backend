#!/bin/bash

# Database Reset Script - Fresh Start with RBAC
# This script will completely reset your database and set up the new RBAC system

echo "🔄 Database Reset & Fresh Start"
echo "================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Warning
echo -e "${RED}⚠️  WARNING: This will DELETE ALL existing data!${NC}"
echo -e "${RED}⚠️  This action cannot be undone!${NC}"
echo ""
read -p "Are you sure you want to continue? (type 'yes' to confirm): " confirm

if [ "$confirm" != "yes" ]; then
    echo -e "${YELLOW}Operation cancelled.${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}Starting database reset...${NC}"
echo ""

# Step 1: Drop all tables and migrate fresh
echo -e "${YELLOW}Step 1: Dropping all tables and running fresh migrations...${NC}"
php artisan migrate:fresh
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database reset complete${NC}"
else
    echo -e "${RED}✗ Migration failed${NC}"
    exit 1
fi
echo ""

# Step 2: Install/Update Spatie Permission
echo -e "${YELLOW}Step 2: Installing Spatie Permission package...${NC}"
composer require spatie/laravel-permission
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Spatie Permission installed${NC}"
else
    echo -e "${RED}✗ Installation failed${NC}"
    exit 1
fi
echo ""

# Step 3: Publish Spatie configuration
echo -e "${YELLOW}Step 3: Publishing Spatie configuration...${NC}"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Configuration published${NC}"
else
    echo -e "${RED}✗ Publishing failed${NC}"
    exit 1
fi
echo ""

# Step 4: Run Spatie migrations
echo -e "${YELLOW}Step 4: Running Spatie migrations...${NC}"
php artisan migrate
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Spatie tables created${NC}"
else
    echo -e "${RED}✗ Migration failed${NC}"
    exit 1
fi
echo ""

# Step 5: Seed RBAC system
echo -e "${YELLOW}Step 5: Seeding RBAC system (roles & permissions)...${NC}"
php artisan db:seed --class=RBACSeeder
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ RBAC system initialized${NC}"
else
    echo -e "${RED}✗ Seeding failed${NC}"
    exit 1
fi
echo ""

# Step 6: Seed sample data (optional)
echo ""
read -p "Do you want to seed sample products and categories? (y/n): " seed_data

if [ "$seed_data" = "y" ] || [ "$seed_data" = "Y" ]; then
    echo -e "${YELLOW}Step 6: Seeding sample data...${NC}"
    php artisan db:seed --class=CategorySeeder
    php artisan db:seed --class=ProductSeeder
    echo -e "${GREEN}✓ Sample data seeded${NC}"
else
    echo -e "${YELLOW}Skipping sample data seeding${NC}"
fi
echo ""

# Step 7: Clear all caches
echo -e "${YELLOW}Step 7: Clearing all caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan permission:cache-reset
echo -e "${GREEN}✓ All caches cleared${NC}"
echo ""

# Step 8: Create default super admin
echo ""
echo -e "${BLUE}Creating default Super Admin account...${NC}"
echo ""

read -p "Enter Super Admin name (default: Super Admin): " admin_name
admin_name=${admin_name:-"Super Admin"}

read -p "Enter Super Admin email (default: superadmin@example.com): " admin_email
admin_email=${admin_email:-"superadmin@example.com"}

read -sp "Enter Super Admin password (default: SecurePass123!): " admin_password
echo ""
admin_password=${admin_password:-"SecurePass123!"}

php artisan tinker --execute="
\$user = App\Models\User::create([
    'name' => '$admin_name',
    'email' => '$admin_email',
    'password' => Hash::make('$admin_password'),
    'email_verified_at' => now(),
]);
\$user->assignRole('super_admin');
echo 'Super Admin created: ' . \$user->email . PHP_EOL;
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Super Admin created successfully${NC}"
else
    echo -e "${RED}✗ Failed to create Super Admin${NC}"
    exit 1
fi
echo ""

# Step 9: Create sample users (optional)
echo ""
read -p "Do you want to create sample test users? (y/n): " create_users

if [ "$create_users" = "y" ] || [ "$create_users" = "Y" ]; then
    echo -e "${YELLOW}Creating sample users...${NC}"
    
    php artisan tinker --execute="
    // Regular User
    \$user1 = App\Models\User::create([
        'name' => 'Test User',
        'email' => 'user@example.com',
        'password' => Hash::make('password123'),
        'email_verified_at' => now(),
    ]);
    \$user1->assignRole('user');
    echo 'User created: ' . \$user1->email . ' (password: password123)' . PHP_EOL;
    
    // Admin User
    \$user2 = App\Models\User::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('password123'),
        'email_verified_at' => now(),
    ]);
    \$user2->assignRole('admin');
    echo 'Admin created: ' . \$user2->email . ' (password: password123)' . PHP_EOL;
    "
    
    echo -e "${GREEN}✓ Sample users created${NC}"
fi
echo ""

# Step 10: Display summary
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ Database Reset Complete!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Summary:${NC}"
echo "  • Database: Fresh and clean"
echo "  • RBAC System: Initialized"
echo "  • Roles: user, admin, super_admin"
echo "  • Permissions: 16 permissions assigned"
echo ""
echo -e "${BLUE}Accounts Created:${NC}"
echo "  • Super Admin: $admin_email"
if [ "$create_users" = "y" ] || [ "$create_users" = "Y" ]; then
    echo "  • Test User: user@example.com (password: password123)"
    echo "  • Test Admin: admin@example.com (password: password123)"
fi
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo "  1. Start the server:"
echo "     ${YELLOW}php artisan serve${NC}"
echo ""
echo "  2. Test admin login:"
echo "     ${YELLOW}curl -X POST http://localhost:8000/api/admin/login \\${NC}"
echo "     ${YELLOW}  -H 'Content-Type: application/json' \\${NC}"
echo "     ${YELLOW}  -d '{\"email\":\"$admin_email\",\"password\":\"$admin_password\"}'${NC}"
echo ""
echo "  3. View all routes:"
echo "     ${YELLOW}php artisan route:list${NC}"
echo ""
echo "  4. Check roles and permissions:"
echo "     ${YELLOW}php artisan permission:show${NC}"
echo ""
echo -e "${GREEN}🎉 Your platform is ready with MNC-grade RBAC!${NC}"
echo ""
