# Déploiement vers le serveur de production
$server = "u605154961@194.5.156.2"
$port = 65002
$password = "3915Mbuyamb@"
$remotePath = "/home/u605154961/domains/e-pnmls.cd/public_html"

Write-Host "Déploiement en cours..." -ForegroundColor Yellow

# Utiliser sshpass pour le déploiement automatisé
$command = "cd $remotePath && git pull origin main && php artisan optimize:clear 2>&1"

# Créer un fichier temporaire avec la commande
$tempScript = Join-Path $env:TEMP "deploy_cmd.ps1"
@"
`$server = "$server"
`$port = $port
`$password = "$password"
`$remotePath = "$remotePath"

`$command = "cd `$remotePath && git pull origin main && php artisan optimize:clear"

# Utiliser sshpass
sshpass -p "`$password" ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 -p `$port `$server "`$command"
"@ | Out-File -FilePath $tempScript -Encoding UTF8

# Exécuter via sshpass
try {
    $result = sshpass -p $password ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 -p $port $server $command
    Write-Host $result -ForegroundColor Green
} catch {
    Write-Host "Erreur de connexion: $_" -ForegroundColor Red
    Write-Host "Tentative avec plink.exe..." -ForegroundColor Yellow
    
    # Alternative avec plink (PuTTY)
    $plinkPath = "C:\Program Files\PuTTY\plink.exe"
    if (Test-Path $plinkPath) {
        $cmd = "cd $remotePath && git pull origin main && php artisan optimize:clear"
        & $plinkPath -ssh -P $port -pw $password $server $cmd
    } else {
        Write-Host "Ni sshpass ni plink ne sont disponibles." -ForegroundColor Red
        Write-Host "Veuillez exécuter manuellement sur le serveur :" -ForegroundColor Yellow
        Write-Host "cd $remotePath && git pull origin main && php artisan optimize:clear" -ForegroundColor Cyan
    }
}
