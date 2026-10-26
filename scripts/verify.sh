#!/usr/bin/env bash
# Ejecuta localmente las mismas verificaciones que el pipeline (etapas 3 y 4).
set -euo pipefail
cd "$(dirname "$0")/.."

echo "==> [3.1] Estilo de código (Pint)";        vendor/bin/pint --test
echo "==> [3.2] Análisis estático (PHPStan)";    vendor/bin/phpstan analyse --no-progress --memory-limit=1G
echo "==> [3.3] Auditoría de dependencias";      composer audit || echo "(aviso: revisar vulnerabilidades)"
echo "==> [3.4] Build del frontend";             npm run build
echo "==> [4]   Pruebas automatizadas";           php artisan test

echo "Todas las verificaciones pasaron."
