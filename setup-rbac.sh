#!/bin/bash

# MNC-Grade RBAC Setup Script
# This script sets up the enterprise RBAC system

echo "🚀 Starting MNC-Grade RBAC Setup..."
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Install dependencies
echo -e "${YELLOW}Step 1: Installing dependencies...${NC}"
composer install
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Dependencies installed${NC}"
else
    echo -e "${RED}✗ Failed to install dependencies${NC}"
    exit 1
fi
echo ""

# Step 2: Publish Spatie configuration
echo -e "${YELLOW}Step 2: Publishing Spatie Permission configuration...${NC}"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Configuration published${NC}"
else
    echo -e "${RED}✗ Failed to publish configuration${NC}"
    exit 1
fi
echo ""

# Step 3: Run migrations
echo -e "${YELLOW}Step 3: Running migrations...${NC}"
php artisan migrate
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${RED}✗ Migration failed${NC}"
    exit 1
fi
echo ""

# Step 4: Seed RBAC system
echo -e "${YELLOW}Step 4: Seeding RBAC system...${NC}"
php artisan db:seed --class=RBACSeeder
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ RBAC system seeded${NC}"
else
    echo -e "${RED}✗ Seeding failed${NC}"
    exit 1
fi
echo ""

# Step 5: Clear caches
echo -e "${YELLOW}Step 5: Clearing caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan permission:cache-reset
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

# Step 6: Display routes
echo -e "${YELLOW}Step 6: Verifying routes...${NC}"
echo ""
echo "Public Routes:"
echo "  POST /api/register"
echo "  POST /api/login"
echo "  POST /api/admin/login"
echo ""
echo "User Routes (user, admin, super_admin):"
echo "  GET  /api/cart"
echo "  POST /api/orders"
echo "  GET  /api/addresses"
echo ""
echo "Admin Routes (admin, super_admin):"
echo "  GET  /api/admin/dashboard"
echo "  POST /api/admin/products"
echo "  GET  /api/admin/orders"
echo ""
echo "Super Admin Routes (super_admin only):"
echo "  POST /api/admin/users/{id}/promote"
echo "  POST /api/admin/users/{id}/demote"
echo "  GET  /api/admin/role-change-logs"
echo ""

# Step 7: Display roles and permissions
echo -e "${YELLOW}Step 7: Displaying roles and permissions...${NC}"
php artisan permission:show
echo ""

# Success message
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ MNC-Grade RBAC Setup Complete!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo "Next Steps:"
echo "1. Create a super admin user:"
echo "   php artisan tinker"
echo "   \$user = User::where('email', 'admin@example.com')->first();"
echo "   \$user->assignRole('super_admin');"
echo ""
echo "2. Test the API:"
echo "   curl -X POST http://localhost:8000/api/admin/login \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"email\":\"admin@example.com\",\"password\":\"password\"}'"
echo ""
echo "3. Read the documentation:"
echo "   cat RBAC_SETUP.md"
echo ""
echo -e "${YELLOW}⚠️  Remember to update your frontend to use the new endpoints!${NC}"
echo ""
