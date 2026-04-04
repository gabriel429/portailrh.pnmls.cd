# PowerShell script to mirror public\build into build for Hostinger deployments
# Usage: .\copy-build-assets.ps1
$source = Join-Path $PSScriptRoot "..\public\build"
$dest = Join-Path $PSScriptRoot "..\build"

Write-Output "Mirroring files from $source to $dest"

if (!(Test-Path $source)) {
    Write-Error "Source build directory not found: $source"
    exit 1
}

if (Test-Path $dest) {
    Remove-Item -Path $dest -Recurse -Force
}

New-Item -ItemType Directory -Force $dest | Out-Null
Copy-Item -Path (Join-Path $source '*') -Destination $dest -Recurse -Force

Write-Output "Mirror complete."
