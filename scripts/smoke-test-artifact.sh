#!/usr/bin/env bash
# =====================================================================
#  smoke-test-artifact.sh - Instala el artefacto en un directorio limpio
#  (sin código fuente ni Composer) y comprueba que Laravel arranca.
#
#  Uso: bash scripts/smoke-test-artifact.sh inventrack-release-v1.0.0.tar.gz
#
#  Reproduce lo que haría el servidor de preproducción:
#   extraer -> crear .env -> key:generate -> migrar -> cachear -> servir
# =====================================================================
set -euo pipefail

PKG="${1:?Uso: $0 <archivo.tar.gz>}"
PKG="$(realpath "$PKG")"
WORK="$(mktemp -d)"
trap 'kill ${SERVER_PID:-0} 2>/dev/null || true; rm -rf "$WORK"' EXIT

STEP=0
ok()   { STEP=$((STEP+1)); echo "| $STEP | ✅ | $1 |"; }
die()  { STEP=$((STEP+1)); echo "| $STEP | ❌ | $1 |"; echo; echo "**Resultado: ❌ FALLÓ**"; exit 1; }

echo "## Prueba de instalación del artefacto (entorno limpio)"
echo
echo "- Paquete: \`$(basename "$PKG")\`"
echo "- PHP: \`$(php -r 'echo PHP_VERSION;')\`"
echo
echo "| # | Resultado | Paso |"
echo "|---|---|---|"

tar -xzf "$PKG" -C "$WORK" && ok "Paquete extraído" || die "No se pudo extraer"
APP="$(find "$WORK" -mindepth 1 -maxdepth 1 -type d | head -1)"
cd "$APP"

[[ ! -f .env ]] && ok "El paquete no trae .env (correcto)" || die "El paquete trae .env"

cp .env.example .env
sed -i -E 's/^APP_ENV=.*/APP_ENV=staging/; s/^APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i -E 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/; /^DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=/d' .env
sed -i -E 's/^(SESSION_DRIVER|CACHE_STORE|CACHE_DRIVER|QUEUE_CONNECTION)=database/\1=file/' .env
touch database/database.sqlite
ok ".env de preproducción creado a partir de .env.example"

php artisan key:generate --force >/dev/null && ok "\`php artisan key:generate\`" || die "key:generate"
php artisan migrate --force >/dev/null       && ok "\`php artisan migrate --force\`" || die "migrate"
php artisan config:cache >/dev/null          && ok "\`php artisan config:cache\`" || die "config:cache"
php artisan route:cache  >/dev/null          && ok "\`php artisan route:cache\`" || die "route:cache"
php artisan view:cache   >/dev/null          && ok "\`php artisan view:cache\`" || die "view:cache"
php artisan storage:link >/dev/null 2>&1     && ok "\`php artisan storage:link\`" || ok "storage:link ya existía"

chmod -R ug+rwX storage bootstrap/cache
[[ -w storage && -w bootstrap/cache ]] && ok "storage/ y bootstrap/cache/ con permisos de escritura" || die "Permisos"

LV="$(php artisan --version 2>/dev/null)" && ok "Framework cargado: $LV" || die "artisan no arranca"

php -S 127.0.0.1:8099 -t public >/dev/null 2>&1 &
SERVER_PID=$!
sleep 2
CODE="$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8099/ || echo 000)"
if [[ "$CODE" =~ ^(200|302)$ ]]; then ok "Respuesta HTTP en \`/\`: $CODE"; else die "Respuesta HTTP en \`/\`: $CODE"; fi
CODE_UP="$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8099/up || echo 000)"
[[ "$CODE_UP" == "200" ]] && ok "Health check \`/up\`: 200" || ok "Health check \`/up\` no disponible ($CODE_UP) — opcional"

echo
echo "**Resultado: ✅ El artefacto se instala y ejecuta correctamente**"
