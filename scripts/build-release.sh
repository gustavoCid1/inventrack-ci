#!/usr/bin/env bash
# =====================================================================
#  build-release.sh - Prepara una versión liberable del proyecto Laravel
#
#  Uso:   bash scripts/build-release.sh v1.0.0
#  Salida: dist/inventrack-release-<version>.tar.gz
#          dist/inventrack-release-<version>.tar.gz.sha256
#          dist/release.json
#
#  Diferencias respecto al ejemplo básico (tar de todo el directorio):
#   - Parte de "git archive": sólo entra lo versionado, así un .env,
#     una llave o un log local NUNCA pueden colarse en el paquete.
#   - Instala dependencias de PRODUCCIÓN (--no-dev) y autoload optimizado.
#   - Compila assets (Vite) si existe package.json, sin node_modules.
#   - Escribe el paquete fuera del directorio empaquetado (evita el error
#     "file changed as we read it" del ejemplo, que comprime en ".").
#   - Genera checksum SHA-256 y metadatos de la versión.
# =====================================================================
set -euo pipefail

VERSION="${1:-}"
if [[ -z "$VERSION" ]]; then
  echo "Uso: $0 <version>   (ej. v1.0.0)" >&2
  exit 1
fi

APP_SLUG="${APP_NAME_SLUG:-inventrack-release}"
SAFE_VERSION="${VERSION//+/_}"              # '+' no es seguro en nombres de archivo/artefacto
PKG_NAME="${APP_SLUG}-${SAFE_VERSION}"
ROOT_DIR="$(git rev-parse --show-toplevel)"
BUILD_DIR="${ROOT_DIR}/build"
STAGE_DIR="${BUILD_DIR}/${PKG_NAME}"
DIST_DIR="${ROOT_DIR}/dist"

echo "==> Preparando liberacion ${VERSION}"
rm -rf "$BUILD_DIR" "$DIST_DIR"
mkdir -p "$STAGE_DIR" "$DIST_DIR"

# ---------------------------------------------------------------------
# 1. Exportar SOLO archivos versionados del commit actual
# ---------------------------------------------------------------------
echo "==> Exportando codigo versionado (git archive)"
git -C "$ROOT_DIR" archive --format=tar HEAD | tar -x -C "$STAGE_DIR"

# ---------------------------------------------------------------------
# 2. Eliminar lo que no se necesita para ejecutar en otro entorno
# ---------------------------------------------------------------------
echo "==> Eliminando archivos de desarrollo"
DEV_PATHS=(
  .github .gitattributes .gitignore .editorconfig .styleci.yml
  tests phpunit.xml phpunit.xml.dist .phpunit.result.cache
  docker-compose.yml docker-compose.yaml
  docs build dist scripts deploy README-ENTREGA-CONTINUA.md
  .env .env.ci .env.testing .env.backup .env.production
  phpstan.neon pint.json reports
)
for p in "${DEV_PATHS[@]}"; do rm -rf "${STAGE_DIR:?}/${p}"; done
find "$STAGE_DIR" -name '*.sqlite' -path '*/database/*' -delete

# ---------------------------------------------------------------------
# 3. Dependencias de PRODUCCIÓN (sin paquetes de desarrollo)
# ---------------------------------------------------------------------
echo "==> Instalando dependencias de produccion (composer --no-dev)"
composer install \
  --working-dir="$STAGE_DIR" \
  --no-dev --no-interaction --prefer-dist --no-progress \
  --optimize-autoloader --classmap-authoritative --no-scripts
# Nota: --no-scripts evita ejecutar artisan sin .env; Laravel regenera
# bootstrap/cache/packages.php automáticamente en el primer arranque.

# ---------------------------------------------------------------------
# 4. Assets de frontend (Vite) - se compilan y se descarta node_modules
# ---------------------------------------------------------------------
if [[ -f "$STAGE_DIR/package.json" ]]; then
  echo "==> Compilando assets (npm ci && npm run build)"
  ( cd "$STAGE_DIR" && npm ci --no-audit --no-fund && npm run build )
  rm -rf "$STAGE_DIR/node_modules"
fi

# ---------------------------------------------------------------------
# 5. Estructura de storage/ y bootstrap/cache/ vacía pero presente
# ---------------------------------------------------------------------
echo "==> Preparando estructura de storage/"
for d in storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache; do
  mkdir -p "$STAGE_DIR/$d"
  find "$STAGE_DIR/$d" -mindepth 1 -maxdepth 1 ! -name '.gitignore' -exec rm -rf {} +
  [[ -f "$STAGE_DIR/$d/.gitignore" ]] || printf '*\n!.gitignore\n' > "$STAGE_DIR/$d/.gitignore"
done

# ---------------------------------------------------------------------
# 6. Metadatos de la versión
# ---------------------------------------------------------------------
COMMIT="$(git -C "$ROOT_DIR" rev-parse HEAD)"
BUILD_DATE="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo "$VERSION" > "$STAGE_DIR/VERSION"
cat > "$DIST_DIR/release.json" <<JSON
{
  "name": "${APP_SLUG}",
  "version": "${VERSION}",
  "commit": "${COMMIT}",
  "built_at": "${BUILD_DATE}",
  "php": "$(php -r 'echo PHP_VERSION;')",
  "run_id": "${GITHUB_RUN_ID:-local}",
  "repository": "${GITHUB_REPOSITORY:-local}"
}
JSON
cp "$DIST_DIR/release.json" "$STAGE_DIR/release.json"

# ---------------------------------------------------------------------
# 7. Empaquetar + checksum
# ---------------------------------------------------------------------
echo "==> Generando ${PKG_NAME}.tar.gz"
tar -C "$BUILD_DIR" -czf "$DIST_DIR/${PKG_NAME}.tar.gz" "$PKG_NAME"
( cd "$DIST_DIR" && sha256sum "${PKG_NAME}.tar.gz" > "${PKG_NAME}.tar.gz.sha256" )

echo "==> Listo"
ls -lh "$DIST_DIR"
cat "$DIST_DIR/${PKG_NAME}.tar.gz.sha256"
