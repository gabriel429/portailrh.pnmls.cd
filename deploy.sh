#!/bin/bash
set -e

# Post-deployment script - runs automatically after git pull.

echo "Post-deployment script starting..."

# 1. Replace old build files with the freshly committed Vite build.
echo "Replacing old build assets..."
rm -rf build
mkdir -p build
cp -a public/build/. build/

# 2. Recreate public image shortcut.
echo "Creating image symlink..."
rm -rf images
ln -sf public/images images
php artisan storage:link || true

# 3. Clear Laravel caches.
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Run database migrations.
echo "Running migrations..."
php artisan migrate --force

# 5. Set permissions.
echo "Setting permissions..."
chmod -R 755 public/build build
chmod -R 755 storage bootstrap/cache

echo "Deployment complete!"
