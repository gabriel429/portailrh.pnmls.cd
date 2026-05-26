# Deploiement vers le serveur de production
$server = "u605154961@194.5.156.2"
$port = 65002
$remotePath = "/home/u605154961/domains/e-pnmls.cd/public_html"

Write-Host "Déploiement en cours..." -ForegroundColor Yellow

$command = "cd $remotePath && git pull origin main && bash deploy.sh 2>&1"
$password = $env:PNMLS_SSH_PASSWORD

try {
    if ($password -and (Get-Command sshpass -ErrorAction SilentlyContinue)) {
        $result = sshpass -p $password ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 -p $port $server $command
    } else {
        $result = ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 -p $port $server $command
    }

    Write-Host $result -ForegroundColor Green
} catch {
    Write-Host "Erreur de connexion: $_" -ForegroundColor Red
    Write-Host "Tentative avec plink.exe..." -ForegroundColor Yellow
    
    # Alternative avec plink (PuTTY)
    $plinkPath = "C:\Program Files\PuTTY\plink.exe"
    if (Test-Path $plinkPath) {
        $cmd = "cd $remotePath && git pull origin main && bash deploy.sh"
        if ($password) {
            & $plinkPath -ssh -batch -P $port -pw $password $server $cmd
        } else {
            & $plinkPath -ssh -batch -P $port $server $cmd
        }
    } else {
        Write-Host "Ni sshpass ni plink ne sont disponibles." -ForegroundColor Red
        Write-Host "Configurez une cle SSH ou definissez PNMLS_SSH_PASSWORD localement." -ForegroundColor Yellow
        Write-Host "Veuillez exécuter manuellement sur le serveur :" -ForegroundColor Yellow
        Write-Host "cd $remotePath && git pull origin main && bash deploy.sh" -ForegroundColor Cyan
    }
}
