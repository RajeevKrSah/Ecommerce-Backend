#!/bin/bash

# Railway Deployment Script for SecureAuth Backend
# This script prepares the Laravel application for Railway deployment

set -e

echo "🚀 Preparing SecureAuth Backend for Railway Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}[DEPLOY]${NC} $1"
}

# Check if we're in the backend directory
if [ ! -f "artisan" ]; then
    print_error "Please run this script from the backend directory"
    exit 1
fi

print_header "Step 1: Installing Production Dependencies"
composer install --no-dev --optimize-autoloader --no-interaction
print_status "Production dependencies installed"

print_header "Step 2: Generating Application Key"
if [ ! -f ".env" ]; then
    cp .env.example .env
    print_status "Environment file created from example"
fi

# Generate key if not exists
if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate --no-interaction
    print_status "Application key generated"
else
    print_status "Application key already exists"
fi

print_header "Step 3: Optimizing Application"
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_status "Application optimized for production"

print_header "Step 4: Setting File Permissions"
chmod -R 755 storage bootstrap/cache
print_status "File permissions set"

print_header "Step 5: Validating Configuration"
php artisan config:show app --json > /dev/null
if [ $? -eq 0 ]; then
    print_status "Configuration validation passed"
else
    print_error "Configuration validation failed"
    exit 1
fi

print_status "✅ Backend prepared for Railway deployment!"

echo ""
echo "📋 Next Steps:"
echo "1. Push your code to GitHub"
echo "2. Connect your GitHub repo to Railway"
echo "3. Add PostgreSQL database service"
echo "4. Set environment variables in Railway dashboard"
echo "5. Deploy!"
echo ""

print_warning "Don't forget to:"
echo "- Set APP_URL to your Railway domain"
echo "- Set APP_FRONTEND_URL to your Vercel domain"
echo "- Configure database environment variables"
echo "- Set up proper CORS origins"
echo ""

print_status "Deployment preparation completed!"