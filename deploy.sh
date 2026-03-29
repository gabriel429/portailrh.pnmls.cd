#!/bin/bash
# Post-deployment script - Runs automatically after git pull

echo "🚀 Post-deployment script starting..."

# 1. Create/recreate symlinks
echo "📁 Creating symlinks..."
rm -rf build images
ln -sf public/build build
ln -sf public/images images

# 2. Clear Laravel caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 3. Set permissions
echo "🔒 Setting permissions..."
chmod -R 755 public/build
chmod -R 755 storage bootstrap/cache

echo "✅ Deployment complete!"
