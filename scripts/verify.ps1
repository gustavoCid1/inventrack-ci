# Verificaciones locales en Windows (PowerShell). Equivale a scripts/verify.sh
$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..")

vendor/bin/pint --test;                                 if ($LASTEXITCODE) { exit 1 }
vendor/bin/phpstan analyse --no-progress --memory-limit=1G; if ($LASTEXITCODE) { exit 1 }
composer audit
npm run build;                                          if ($LASTEXITCODE) { exit 1 }
php artisan test;                                       if ($LASTEXITCODE) { exit 1 }
Write-Host "Todas las verificaciones pasaron."
