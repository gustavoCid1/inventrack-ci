# Preparación del entorno en Windows (PowerShell). Equivale a scripts/setup.sh
$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..")

composer install --no-interaction
if (Test-Path package-lock.json) { npm ci } else { npm install }
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) { New-Item database/database.sqlite -ItemType File | Out-Null }
php artisan migrate:fresh --seed --force
npm run build
Write-Host "Listo. Ejecuta: php artisan serve  (usuario: admin@inventrack.test / password)"
