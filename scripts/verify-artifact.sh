#!/usr/bin/env bash
# =====================================================================
#  verify-artifact.sh - Verifica el contenido de un artefacto de liberación
#
#  Uso:  bash scripts/verify-artifact.sh dist/inventrack-release-v1.0.0.tar.gz
#
#  Comprueba:
#   A. Que contiene los archivos necesarios para ejecutar Laravel
#   B. Que NO contiene .env, credenciales, llaves ni archivos de desarrollo
#   C. Que no hay secretos escritos en el código (patrones comunes)
#  Imprime un reporte en Markdown y termina con código 1 si algo falla.
# =====================================================================
set -uo pipefail

PKG="${1:?Uso: $0 <archivo.tar.gz>}"
[[ -f "$PKG" ]] || { echo "No existe: $PKG" >&2; exit 1; }

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
tar -xzf "$PKG" -C "$WORK"
TOP="$(find "$WORK" -mindepth 1 -maxdepth 1 -type d | head -1)"
[[ -n "$TOP" ]] || TOP="$WORK"

FAIL=0
pass() { echo "| ✅ | $1 |"; }
fail() { echo "| ❌ | $1 |"; FAIL=1; }

echo "## Verificación del artefacto"
echo
echo "- **Archivo:** \`$(basename "$PKG")\`"
echo "- **Tamaño:** $(du -h "$PKG" | cut -f1)"
echo "- **SHA-256:** \`$(sha256sum "$PKG" | cut -d' ' -f1)\`"
echo "- **Versión:** \`$(cat "$TOP/VERSION" 2>/dev/null || echo 'N/D')\`"
echo "- **Fecha:** $(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo

# ---------------------------------------------------------------------
echo "### A. Archivos requeridos"
echo
echo "| Resultado | Elemento |"
echo "|---|---|"
REQUIRED=(
  artisan composer.json composer.lock VERSION
  app bootstrap/app.php config public/index.php routes resources
  database/migrations
  vendor/autoload.php vendor/laravel/framework
  storage/app storage/framework/cache storage/framework/sessions
  storage/framework/views storage/logs bootstrap/cache
  .env.example
)
# Si el proyecto usa Vite, el manifest compilado es obligatorio
# (evita el error "Vite manifest not found" en el servidor)
[[ -f "$TOP/package.json" ]] && REQUIRED+=(public/build/manifest.json)
for r in "${REQUIRED[@]}"; do
  if [[ -e "$TOP/$r" ]]; then pass "\`$r\` presente"; else fail "\`$r\` FALTA"; fi
done

# ---------------------------------------------------------------------
echo
echo "### B. Archivos sensibles o de desarrollo (no deben existir)"
echo
echo "| Resultado | Regla |"
echo "|---|---|"
check_absent() {   # $1 = descripción, $2.. = expresión para find
  local desc="$1"; shift
  local found
  found="$(cd "$TOP" && find . \( "$@" \) -print 2>/dev/null | grep -v '^\./vendor/' | head -5)"
  if [[ -z "$found" ]]; then pass "$desc"; else fail "$desc → $(echo "$found" | tr '\n' ' ')"; fi
}
check_absent "Sin archivo \`.env\` (sólo se permite .env.example)" \
  -name '.env' -o -name '.env.*' ! -name '.env.example'
check_absent "Sin carpeta \`.git/\`"             -name '.git' -type d
check_absent "Sin \`node_modules/\`"             -name 'node_modules' -type d
check_absent "Sin llaves privadas / certificados" \
  -name '*.pem' -o -name '*.key' -o -name 'id_rsa*' -o -name '*.p12' -o -name '*.pfx'
check_absent "Sin \`auth.json\` de Composer"     -name 'auth.json'
check_absent "Sin logs (\`*.log\`)"               -name '*.log'
check_absent "Sin bases SQLite locales"          -name '*.sqlite'
check_absent "Sin carpeta \`tests/\` ni phpunit"  -path './tests' -o -name 'phpunit.xml*'
check_absent "Sin workflows \`.github/\`"         -name '.github' -type d

# Dependencias de desarrollo NO instaladas
DEV_PKGS=(phpunit/phpunit mockery/mockery fakerphp/faker laravel/pint larastan/larastan nunomaduro/collision laravel/sail)
DEV_FOUND=""
for d in "${DEV_PKGS[@]}"; do [[ -d "$TOP/vendor/$d" ]] && DEV_FOUND+="$d "; done
if [[ -z "$DEV_FOUND" ]]; then pass "Dependencias de desarrollo excluidas (\`--no-dev\`)"
else fail "Dependencias de desarrollo presentes: $DEV_FOUND"; fi

# ---------------------------------------------------------------------
echo
echo "### C. Búsqueda de secretos en el código (excluye vendor/)"
echo
echo "| Resultado | Patrón |"
echo "|---|---|"
scan() {  # $1 = descripción, $2 = regex
  local hits
  hits="$(grep -rIlE --exclude-dir=vendor --exclude='.env.example' "$2" "$TOP" 2>/dev/null \
          | sed "s|$TOP/||" | head -5)"
  if [[ -z "$hits" ]]; then pass "$1"; else fail "$1 → $(echo "$hits" | tr '\n' ' ')"; fi
}
scan "Sin APP_KEY real"                 'APP_KEY=base64:[A-Za-z0-9+/=]{20,}'
scan "Sin llaves privadas embebidas"    '-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----'
scan "Sin llaves de AWS"                'AKIA[0-9A-Z]{16}'
scan "Sin tokens de GitHub"             'gh[pousr]_[A-Za-z0-9]{30,}'
scan "Sin contraseñas de BD escritas"   "DB_PASSWORD=[^[:space:]\"']{3,}"

# ---------------------------------------------------------------------
echo
echo "### Resumen"
echo
echo "- Archivos en el paquete: $(find "$TOP" -type f | wc -l)"
echo "- Archivos de la aplicación (sin vendor): $(find "$TOP" -type f -not -path '*/vendor/*' | wc -l)"
if [[ $FAIL -eq 0 ]]; then
  echo "- **Resultado global: ✅ APROBADO** — el artefacto puede entregarse a preproducción."
else
  echo "- **Resultado global: ❌ RECHAZADO** — corrige los puntos marcados antes de liberar."
fi
exit $FAIL
