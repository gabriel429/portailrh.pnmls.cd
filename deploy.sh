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
rm -rf public/storage
ln -sf ../storage/app/public public/storage

# 3. Clear Laravel caches.
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Run database migrations.
echo "Running migrations..."
php artisan migrate --force

# 5. Set permissions without marking tracked assets as executable.
echo "Setting permissions..."
find public/build build -type d -exec chmod 755 {} \;
find public/build build -type f -exec chmod 644 {} \;
find storage bootstrap/cache -type d -exec chmod 755 {} \;
find storage bootstrap/cache -type f -exec chmod 644 {} \;

echo "Deployment complete!"
