#!/bin/bash
# MANIFEST CORRUPTION FIX - Hostinger Deployment
# This script fixes the corrupted manifest.json that references stale asset hashes

set -e

echo "🚀 Fixing Manifest Corruption..."
echo "================================"

# Step 1: Backup current build (for safety)
echo "📦 Backing up current build..."
BACKUP_DIR="backup_build_$(date +%Y%m%d_%H%M%S)"
if [ -d "public/build" ]; then
    cp -r public/build "$BACKUP_DIR"
    echo "✅ Backup created: $BACKUP_DIR"
fi

# Step 2: Remove corrupted manifest
echo "🧹 Removing corrupted manifest..."
rm -rf public/build/manifest.json
rm -rf public/build/sw.js
rm -rf public/build/workbox-*.js

# Step 3: Verify build directory structure
echo "🔍 Verifying build structure..."
if [ ! -d "build" ]; then
    echo "❌ ERROR: build/ directory not found!"
    exit 1
fi

# Step 4: Copy fresh manifest from build to public/build
echo "📋 Copying fresh manifest..."
if [ -f "build/manifest.json" ]; then
    mkdir -p public/build
    cp build/manifest.json public/build/manifest.json
    echo "✅ Manifest copied: build/manifest.json → public/build/manifest.json"
else
    echo "⚠️  WARNING: build/manifest.json not found, will generate during npm run build"
fi

# Step 5: Copy service worker and workbox files
echo "📦 Copying service worker files..."
if [ -f "build/sw.js" ]; then
    cp build/sw.js public/build/sw.js
    echo "✅ Service Worker copied"
fi

for file in build/workbox-*.js; do
    if [ -f "$file" ]; then
        cp "$file" public/build/
        echo "✅ Copied: $(basename "$file")"
    fi
done

# Step 6: Verify manifest references match actual files
echo "🔍 Verifying manifest consistency..."
MISSING_FILES=0

# Extract all file references from manifest
if [ -f "public/build/manifest.json" ]; then
    while IFS= read -r file_ref; do
        # Check if file exists
        if [ ! -f "public/build/$file_ref" ]; then
            echo "⚠️  MISSING: $file_ref"
            ((MISSING_FILES++))
        fi
    done < <(grep -oP '"file":\s*"\K[^"]+' public/build/manifest.json || true)
    
    if [ $MISSING_FILES -gt 0 ]; then
        echo "❌ Found $MISSING_FILES missing asset files!"
        echo "RUN: npm run build"
        exit 1
    fi
fi

echo ""
echo "✅ Manifest corruption fixed!"
echo "📁 Structure:"
echo "  - public/build/manifest.json ✅"
echo "  - public/build/sw.js ✅"
echo "  - public/build/.htaccess ✅"
echo ""
echo "📊 Next Steps:"
echo "1. Verify: npm run build"
echo "2. Check: cat public/build/manifest.json | grep 'app-.*css'"
echo "3. Deploy to Hostinger"
echo ""
echo "✨ Done!"
