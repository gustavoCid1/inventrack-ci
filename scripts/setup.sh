#!/usr/bin/env bash
# Prepara el entorno local de InvenTrack (equivalente a la etapa 2 del pipeline).
set -euo pipefail
cd "$(dirname "$0")/.."

echo "==> Verificando herramientas"
php -v | head -1
composer --version
node -v && npm -v

echo "==> Instalando dependencias PHP"
composer install --no-interaction

echo "==> Instalando dependencias npm"
if [ -f package-lock.json ]; then npm ci; else npm install; fi

echo "==> Configurando .env"
[ -f .env ] || cp .env.example .env
php artisan key:generate

echo "==> Base de datos SQLite con datos de ejemplo"
touch database/database.sqlite
php artisan migrate:fresh --seed --force

echo "==> Compilando frontend"
npm run build

echo "Listo. Ejecuta: php artisan serve  (usuario: admin@inventrack.test / password)"
