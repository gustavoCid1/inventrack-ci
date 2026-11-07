#!/usr/bin/env bash
# =====================================================================
#  setup-preprod-server.sh - Aprovisiona un servidor de PREPRODUCCIÓN
#  Probado para Ubuntu Server 24.04 LTS (VM local, VPS o WSL2).
#
#  Uso (como root o con sudo):
#    sudo DB_PASS='CambiaEstaClave!' APP_DOMAIN=preprod.local bash setup-preprod-server.sh
#
#  Instala: PHP 8.3 + extensiones, Composer, Nginx, MySQL 8,
#           usuario "deploy", estructura de carpetas y sitio Nginx.
# =====================================================================
set -euo pipefail

DB_NAME="${DB_NAME:-inventrack_preprod}"
DB_USER="${DB_USER:-inventrack_preprod}"
DB_PASS="${DB_PASS:?Define DB_PASS (contraseña de la BD de preproducción)}"
APP_DOMAIN="${APP_DOMAIN:-preprod.local}"
BASE="${BASE:-/var/www/inventrack-preprod}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
PHP_V="8.3"

echo "==> 1/7 Paquetes del sistema"
apt-get update -y
apt-get install -y nginx mysql-server unzip curl git acl \
  php${PHP_V}-fpm php${PHP_V}-cli php${PHP_V}-common php${PHP_V}-mysql php${PHP_V}-sqlite3 \
  php${PHP_V}-mbstring php${PHP_V}-xml php${PHP_V}-curl php${PHP_V}-zip php${PHP_V}-bcmath \
  php${PHP_V}-intl php${PHP_V}-gd php${PHP_V}-opcache php${PHP_V}-readline

echo "==> 2/7 Composer"
if ! command -v composer >/dev/null; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
composer --version

echo "==> 3/7 Base de datos de preproducción"
mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> 4/7 Usuario de despliegue '${DEPLOY_USER}'"
id "$DEPLOY_USER" >/dev/null 2>&1 || adduser --disabled-password --gecos "" "$DEPLOY_USER"
usermod -aG www-data "$DEPLOY_USER"
mkdir -p "/home/${DEPLOY_USER}/.ssh" && chmod 700 "/home/${DEPLOY_USER}/.ssh"
touch "/home/${DEPLOY_USER}/.ssh/authorized_keys" && chmod 600 "/home/${DEPLOY_USER}/.ssh/authorized_keys"
chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "/home/${DEPLOY_USER}/.ssh"
# Permite al usuario deploy recargar PHP-FPM sin contraseña (sólo ese comando)
echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload php${PHP_V}-fpm" > /etc/sudoers.d/deploy-fpm
chmod 440 /etc/sudoers.d/deploy-fpm

echo "==> 5/7 Estructura de la aplicación en ${BASE}"
mkdir -p "${BASE}/releases" "${BASE}/shared/storage"
chown -R "${DEPLOY_USER}:www-data" "$BASE"
chmod -R 2775 "${BASE}/shared"
setfacl -R -m g:www-data:rwX -m d:g:www-data:rwX "${BASE}/shared/storage"

if [[ ! -f "${BASE}/shared/.env" ]]; then
  cat > "${BASE}/shared/.env" <<ENV
APP_NAME="InvenTrack Preprod"
APP_ENV=staging
APP_KEY=
APP_DEBUG=false
APP_URL=http://${APP_DOMAIN}

LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
ENV
  # Genera APP_KEY única para este ambiente
  KEY="base64:$(head -c 32 /dev/urandom | base64)"
  sed -i "s|^APP_KEY=.*|APP_KEY=${KEY}|" "${BASE}/shared/.env"
  chown "${DEPLOY_USER}:www-data" "${BASE}/shared/.env"
  chmod 640 "${BASE}/shared/.env"
fi

echo "==> 6/7 Sitio Nginx"
cat > /etc/nginx/sites-available/inventrack-preprod <<NGINX
server {
    listen 80;
    server_name ${APP_DOMAIN};
    root ${BASE}/current/public;
    index index.php;
    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-Robots-Tag "noindex, nofollow";   # preproducción: no indexar

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php${PHP_V}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
NGINX
ln -sfn /etc/nginx/sites-available/inventrack-preprod /etc/nginx/sites-enabled/inventrack-preprod
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "==> 7/7 Verificación"
php -v | head -1
php -m | grep -E -i '^(mbstring|xml|curl|zip|bcmath|intl|pdo_mysql|openssl|tokenizer|ctype|fileinfo)$' | tr '\n' ' '; echo
systemctl is-active nginx php${PHP_V}-fpm mysql

cat <<MSG

Servidor de preproducción listo.
Siguientes pasos:
  1. Agrega la llave pública de GitHub Actions a /home/${DEPLOY_USER}/.ssh/authorized_keys
  2. En GitHub > Settings > Environments > "preproduccion" crea:
       Secrets:   PREPROD_HOST, PREPROD_USER=${DEPLOY_USER}, PREPROD_SSH_KEY
       Variables: PREPROD_URL=http://${APP_DOMAIN}, PREPROD_PATH=${BASE}
  3. Ejecuta el workflow o despliega a mano:
       bash deploy-preprod.sh inventrack-release-v1.0.0.tar.gz v1.0.0 ${BASE}
MSG
