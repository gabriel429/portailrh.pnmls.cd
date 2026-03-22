# PowerShell script to download PHP 8.3 and MariaDB 11.4 portable for Windows
# Usage: powershell -ExecutionPolicy Bypass -File desktop/setup-binaries.ps1

$ErrorActionPreference = "Stop"

$DESKTOP_DIR = Join-Path $PSScriptRoot ""
$PHP_DIR = Join-Path $DESKTOP_DIR "bin\php"
$MARIADB_DIR = Join-Path $DESKTOP_DIR "bin\mariadb"
$TEMP_DIR = Join-Path $env:TEMP "portailrh-setup"

# PHP 8.3 NTS (Non-Thread Safe) x64 for Windows
$PHP_URL = "https://windows.php.net/downloads/releases/php-8.3.30-nts-Win32-vs16-x64.zip"
$PHP_ZIP = Join-Path $TEMP_DIR "php.zip"

# MariaDB 11.4 portable x64 for Windows
$MARIADB_URL = "https://archive.mariadb.org/mariadb-11.4.4/winx64-packages/mariadb-11.4.4-winx64.zip"
$MARIADB_ZIP = Join-Path $TEMP_DIR "mariadb.zip"

function Write-Step($msg) {
    Write-Host "`n==> $msg" -ForegroundColor Cyan
}

# Create temp directory
if (-not (Test-Path $TEMP_DIR)) {
    New-Item -ItemType Directory -Path $TEMP_DIR -Force | Out-Null
}

# ─── Download and extract PHP ────────────────────────
if (Test-Path (Join-Path $PHP_DIR "php.exe")) {
    Write-Step "PHP already present, skipping..."
} else {
    Write-Step "Downloading PHP 8.3..."
    if (-not (Test-Path $PHP_ZIP)) {
        Invoke-WebRequest -Uri $PHP_URL -OutFile $PHP_ZIP -UseBasicParsing
    }

    Write-Step "Extracting PHP..."
    if (Test-Path $PHP_DIR) { Remove-Item $PHP_DIR -Recurse -Force }
    New-Item -ItemType Directory -Path $PHP_DIR -Force | Out-Null
    Expand-Archive -Path $PHP_ZIP -DestinationPath $PHP_DIR -Force

    # Configure php.ini for Laravel
    $phpIniSource = Join-Path $PHP_DIR "php.ini-development"
    $phpIniDest = Join-Path $PHP_DIR "php.ini"
    Copy-Item $phpIniSource $phpIniDest

    # Enable required extensions
    $ini = Get-Content $phpIniDest
    $ini = $ini -replace '^;extension_dir = "ext"', 'extension_dir = "ext"'
    $ini = $ini -replace '^;extension=curl', 'extension=curl'
    $ini = $ini -replace '^;extension=fileinfo', 'extension=fileinfo'
    $ini = $ini -replace '^;extension=gd', 'extension=gd'
    $ini = $ini -replace '^;extension=intl', 'extension=intl'
    $ini = $ini -replace '^;extension=mbstring', 'extension=mbstring'
    $ini = $ini -replace '^;extension=mysqli', 'extension=mysqli'
    $ini = $ini -replace '^;extension=openssl', 'extension=openssl'
    $ini = $ini -replace '^;extension=pdo_mysql', 'extension=pdo_mysql'
    $ini = $ini -replace '^;extension=zip', 'extension=zip'
    $ini = $ini -replace '^;extension=sodium', 'extension=sodium'
    Set-Content $phpIniDest $ini

    Write-Step "PHP 8.3 installed to $PHP_DIR"
}

# ─── Download and extract MariaDB ────────────────────
if (Test-Path (Join-Path $MARIADB_DIR "bin\mysqld.exe")) {
    Write-Step "MariaDB already present, skipping..."
} else {
    Write-Step "Downloading MariaDB 11.4..."
    if (-not (Test-Path $MARIADB_ZIP)) {
        Invoke-WebRequest -Uri $MARIADB_URL -OutFile $MARIADB_ZIP -UseBasicParsing
    }

    Write-Step "Extracting MariaDB..."
    $extractDir = Join-Path $TEMP_DIR "mariadb-extract"
    if (Test-Path $extractDir) { Remove-Item $extractDir -Recurse -Force }
    Expand-Archive -Path $MARIADB_ZIP -DestinationPath $extractDir -Force

    # MariaDB zip contains a subdirectory like mariadb-11.4.4-winx64
    $subDir = Get-ChildItem $extractDir -Directory | Select-Object -First 1
    if (Test-Path $MARIADB_DIR) { Remove-Item $MARIADB_DIR -Recurse -Force }
    Move-Item $subDir.FullName $MARIADB_DIR

    # Clean up extracted temp
    Remove-Item $extractDir -Recurse -Force -ErrorAction SilentlyContinue

    Write-Step "MariaDB 11.4 installed to $MARIADB_DIR"
}

# ─── Verify ──────────────────────────────────────────
Write-Step "Verifying installations..."

$phpExe = Join-Path $PHP_DIR "php.exe"
if (Test-Path $phpExe) {
    $phpVersion = & $phpExe -v 2>&1 | Select-Object -First 1
    Write-Host "  PHP: $phpVersion" -ForegroundColor Green
} else {
    Write-Host "  PHP: NOT FOUND" -ForegroundColor Red
}

$mysqldExe = Join-Path $MARIADB_DIR "bin\mysqld.exe"
if (Test-Path $mysqldExe) {
    $mariaVersion = & $mysqldExe --version 2>&1 | Select-Object -First 1
    Write-Host "  MariaDB: $mariaVersion" -ForegroundColor Green
} else {
    Write-Host "  MariaDB: NOT FOUND" -ForegroundColor Red
}

# ─── Cleanup temp ────────────────────────────────────
Write-Step "Cleaning up temporary files..."
Remove-Item $TEMP_DIR -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "`n==> Setup complete! You can now run: npm run electron:dev" -ForegroundColor Green
