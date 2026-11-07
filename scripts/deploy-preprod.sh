#!/usr/bin/env bash
# =====================================================================
#  deploy-preprod.sh - Instala un artefacto en el servidor de preproducción
#  Se ejecuta EN EL SERVIDOR (lo invoca el job deploy-preprod por SSH,
#  o manualmente después de descargar el artefacto).
#
#  Uso: bash deploy-preprod.sh <artefacto.tar.gz> <version> [/var/www/inventrack-preprod]
#
#  Estructura (releases atómicas, permite rollback):
#   /var/www/inventrack-preprod/
#     ├── releases/v1.0.0/  releases/v1.0.1/ ...
#     ├── shared/.env               <- variables del ambiente (NO van en el artefacto)
#     ├── shared/storage/           <- logs, sesiones, archivos subidos (persisten)
#     └── current -> releases/v1.0.1  (Nginx apunta a current/public)
# =====================================================================
set -euo pipefail

PKG="${1:?Uso: $0 <artefacto.tar.gz> <version> [ruta_base]}"
VERSION="${2:?Falta version}"
BASE="${3:-/var/www/inventrack-preprod}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
WEB_GROUP="${WEB_GROUP:-www-data}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"

RELEASE_DIR="${BASE}/releases/${VERSION//+/_}"
SHARED="${BASE}/shared"

log() { echo "[$(date +%H:%M:%S)] $*"; }

log "Desplegando ${VERSION} en ${BASE}"
mkdir -p "${BASE}/releases" "${SHARED}/storage"

[[ -f "${SHARED}/.env" ]] || { echo "ERROR: falta ${SHARED}/.env (ver docs/02-ambiente-preproduccion.md)"; exit 1; }

# 1. Extraer el paquete en su propia carpeta de release
rm -rf "$RELEASE_DIR"; mkdir -p "$RELEASE_DIR"
tar -xzf "$PKG" -C "$RELEASE_DIR" --strip-components=1
log "Paquete extraido en ${RELEASE_DIR}"

# 2. Verificar requisitos de plataforma (versión PHP + extensiones)
if command -v composer >/dev/null; then
  composer check-platform-reqs --no-dev --working-dir="$RELEASE_DIR" \
    || { echo "ERROR: el servidor no cumple los requisitos de PHP/extensiones"; exit 1; }
fi

# 3. Enlazar recursos compartidos (.env y storage persisten entre versiones)
if [[ ! -d "${SHARED}/storage/framework" ]]; then
  cp -a "${RELEASE_DIR}/storage/." "${SHARED}/storage/"
fi
rm -rf "${RELEASE_DIR}/storage"
ln -sfn "${SHARED}/storage" "${RELEASE_DIR}/storage"
ln -sfn "${SHARED}/.env"    "${RELEASE_DIR}/.env"

# 4. Permisos para storage/ y bootstrap/cache/
chgrp -R "$WEB_GROUP" "${SHARED}/storage" "${RELEASE_DIR}/bootstrap/cache" 2>/dev/null || true
chmod -R ug+rwX "${SHARED}/storage" "${RELEASE_DIR}/bootstrap/cache"
find "${SHARED}/storage" "${RELEASE_DIR}/bootstrap/cache" -type d -exec chmod g+s {} +

# 5. Preparar la aplicación
cd "$RELEASE_DIR"
php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan optimize          # config:cache + route:cache + view:cache + event:cache
log "Migraciones y caches listas"

# 6. Activar la versión (cambio atómico del symlink)
ln -sfn "$RELEASE_DIR" "${BASE}/current.tmp"
mv -Tf "${BASE}/current.tmp" "${BASE}/current"
log "current -> ${RELEASE_DIR}"

# 7. Recargar PHP-FPM (limpia OPcache)
sudo -n systemctl reload "$PHP_FPM_SERVICE" 2>/dev/null || log "Aviso: no se pudo recargar ${PHP_FPM_SERVICE} (recargar manualmente)"

# 8. Health check
APP_URL="$(grep -E '^APP_URL=' "${SHARED}/.env" | cut -d= -f2- | tr -d '"')"
if [[ -n "$APP_URL" ]] && command -v curl >/dev/null; then
  CODE="$(curl -sk -o /dev/null -w '%{http_code}' "${APP_URL%/}/up" || echo 000)"
  log "Health check ${APP_URL%/}/up -> HTTP ${CODE}"
  if [[ "$CODE" != "200" ]]; then
    PREV="$(ls -1dt "${BASE}"/releases/* | sed -n 2p)"
    if [[ -n "$PREV" ]]; then
      ln -sfn "$PREV" "${BASE}/current.tmp" && mv -Tf "${BASE}/current.tmp" "${BASE}/current"
      log "ROLLBACK a ${PREV}"
    fi
    exit 1
  fi
fi

# 9. Conservar sólo las últimas N versiones
ls -1dt "${BASE}"/releases/* | tail -n +$((KEEP_RELEASES+1)) | xargs -r rm -rf
echo "${VERSION}  $(date -u +%Y-%m-%dT%H:%M:%SZ)" >> "${BASE}/deploy-history.log"
log "Despliegue de ${VERSION} completado"
