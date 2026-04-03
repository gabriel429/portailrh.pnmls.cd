# 🚨 PRODUCTION ISSUE DIAGNOSIS - MANIFEST CORRUPTION

## Problem Summary

**Production URL**: https://deeppink-rhinoceros-934330.hostingersite.com/dashboard

**Errors Occurring**:
```
1. CSS MIME Type Error:
   Refused to apply style from '/build/assets/app-NUVL19JZ.css' 
   because its MIME type ('text/html') is not a supported stylesheet MIME type

2. JavaScript 404 Errors:
   Failed to load: /build/assets/app-ByQU1_xv.js (404)
   Failed to load: /build/assets/router-8-aeFb5j.js (404)
   Failed to load: /build/assets/syncService-DK_oeT3C.js (404)
```

## Root Cause: MANIFEST CORRUPTION

The `public/build/manifest.json` file is **OUT OF SYNC** with actual built assets.

### Evidence

**What the manifest says:**
```json
"resources/js/app.js": {
  "file": "assets/app-ByQU1_xv.js",     ✅ CORRECT (exists)
  "css": ["assets/app-NUVL19JZ.css"]   ❌ STALE (DOESN'T EXIST)
}
```

**What actually exists on disk:**
- ✅ `public/build/assets/app-ByQU1_xv.js` - EXISTS
- ✅ `public/build/assets/router-BU8_cs-n.js` - EXISTS  
- ✅ `public/build/assets/syncService-DTMCyi0f.js` - EXISTS
- ❌ `public/build/assets/app-NUVL19JZ.css` - **DOES NOT EXIST** (stale hash)

### Why This Happens

1. **Partial build upload**: Only JS files were copied to `public/build/` but not the manifest
2. **Previous build artifacts**: Old manifest still references hashes from a prior build
3. **Service worker cache**: The SW cached the old hash, preventing fallback to new files

### Cascade Effect

1. HTML requests `/build/assets/app-NUVL19JZ.css` (from manifest)
2. File doesn't exist → Apache returns 404
3. 404 response has Content-Type: `text/html` (Apache default for 404 pages)
4. Browser rejects CSS with "MIME type is text/html, not text/css"
5. JS files also fail to load because CSS dependency fails first

## Solution: CLEAN REBUILD

### Option 1: Local Rebuild (Recommended)

```bash
cd c:\wamp64\www\pnmls.cd\pnmls.cd.worktrees\copilot-worktree-2026-04-03T12-30-17

# Clean the corrupted build
rm -rf public/build

# Run fresh build
npm ci --production=false
npm run build

# Verify manifest is correct
cat public/build/manifest.json | grep -A2 "resources/js/app.js"
```

**Expected output:**
```json
"resources/js/app.js": {
  "file": "assets/app-ByQU1_xv.js",
  "name": "app",
  ...
  "css": [
    "assets/app-B8Jvn1vR.css"  ← MUST be the hash of actual CSS file
  ]
}
```

### Option 2: Use Deployment Script

```bash
chmod +x scripts/deploy-hostinger.sh
./scripts/deploy-hostinger.sh
```

This script:
1. ✅ Cleans old builds
2. ✅ Runs fresh npm build
3. ✅ Generates fresh manifest.json
4. ✅ Creates proper .htaccess with MIME types
5. ✅ Verifies all critical files exist

## Files Involved

| File | Status | Fix |
|------|--------|-----|
| `public/build/manifest.json` | ❌ CORRUPTED | Regenerate |
| `public/build/sw.js` | ❌ STALE | Regenerate |
| `public/build/.htaccess` | ✅ OK | No change |
| `public/asset.php` | ✅ OK | No change |
| `build/assets/*.{js,css}` | ✅ OK | Keep as-is |

## Deployment Steps After Rebuild

1. **Local**: Run `npm run build`
2. **Verify**: Check `public/build/manifest.json` has correct hashes
3. **Upload**: Upload entire `public/build/` to Hostinger via cPanel/SSH
   ```bash
   scp -r public/build/ u605154961@deeppink-rhinoceros-934330.hostingersite.com:~/domains/deeppink-rhinoceros-934330.hostingersite.com/public_html/
   ```
4. **Verify Online**: 
   - Check browser console for errors
   - Inspect Network tab for correct MIME types
   - Test: https://deeppink-rhinoceros-934330.hostingersite.com/

## Prevention

- **Add to CI/CD**: Always run `npm run build` as part of deployment
- **Add to git hooks**: Verify manifest before commits
- **Backup before deploy**: Keep backup of previous `public/build/` in case rollback needed
- **Use deployment script**: Always use `scripts/deploy-hostinger.sh` instead of manual uploads

## Timeline

- Previous build: Created assets with hash `app-NUVL19JZ.css`
- Current build: Changed to `app-B8Jvn1vR.css` (dependency update?)
- Deployment: Only JS files uploaded, manifest NOT updated
- Result: Mismatch between manifest and actual files

---

**Status**: Ready for rebuild
**Next Step**: Run `npm run build` and redeploy
