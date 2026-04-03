# PowerShell script to create public\build\assets and copy build\assets into it
# Usage: .\copy-build-assets.ps1
$source = Join-Path $PSScriptRoot "..\build\assets"
$dest = Join-Path $PSScriptRoot "..\public\build\assets"
Write-Output "Copying files from $source to $dest"
New-Item -ItemType Directory -Force $dest | Out-Null
Get-ChildItem -Path $source -Recurse -File | ForEach-Object {
    $target = Join-Path $dest (Resolve-Path -Path $_.FullName).Path.Substring((Resolve-Path -Path $source).Path.Length+1)
    $targetDir = Split-Path $target -Parent
    if (!(Test-Path $targetDir)) { New-Item -ItemType Directory -Force $targetDir | Out-Null }
    Copy-Item -Path $_.FullName -Destination $target -Force
}
Write-Output "Copy complete."
