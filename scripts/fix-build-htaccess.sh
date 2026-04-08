#!/bin/bash
cat > ~/domains/e-pnmls.cd/public_html/build/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine Off
</IfModule>

<IfModule mod_mime.c>
    AddType application/javascript .js
    AddType text/css .css
    AddType application/json .json
    AddType application/manifest+json .webmanifest
    AddType font/woff2 .woff2
    AddType image/svg+xml .svg
</IfModule>

<IfModule mod_headers.c>
    Header set Cache-Control "public, max-age=31536000, immutable"
    Header set X-Content-Type-Options "nosniff"
</IfModule>
EOF
echo "build/.htaccess fixed"
cat ~/domains/e-pnmls.cd/public_html/build/.htaccess
