#!/bin/bash

# Railway Database Migration Script
# Run this to create database tables

set -e

echo "🔄 Running database migrations..."

# Run migrations
php artisan migrate --force

echo "✅ Database migrations completed!"

# Optional: Seed database with test data
# php artisan db:seed --force

echo "📊 Database tables created successfully!"