# Parte 3. Configuración del ambiente de preproducción

> Alumno: **[gustavo angel cid flores ]** · Matrícula: **[222310413]** · Fecha: **[06/11/26]**

## 3.1 Tipo de ambiente

| Parámetro | Valor |
|---|---|
| Tipo | Máquina virtual (VirtualBox / VMware / WSL2) **[ajusta: VM, VPS, hosting autorizado]** |
| Sistema operativo | Ubuntu Server 24.04 LTS |
| Recursos | 2 vCPU · 2 GB RAM · 20 GB disco |
| Nombre / IP | `preprod.local` · `[IP de tu VM]` |
| Aprovisionamiento | `scripts/setup-preprod-server.sh` |

## 3.2 Parámetros de configuración

| Componente | Requisito Laravel | Configuración del ambiente | Comando de verificación |
|---|---|---|---|
| **PHP** | ≥ 8.2 | PHP 8.3 (FPM + CLI) | `php -v` |
| **Composer** | 2.x | Composer 2 en `/usr/local/bin/composer` | `composer --version` |
| **Extensiones PHP** | ctype, curl, fileinfo, mbstring, openssl, pdo, tokenizer, xml | + `pdo_mysql, zip, bcmath, intl, gd, opcache, sqlite3` | `php -m` |
| **Servidor web** | Raíz en `public/` | Nginx → `/var/www/inventrack-preprod/current/public` (`deploy/nginx/inventrack-preprod.conf`) | `nginx -t` |
| **Procesador PHP** | — | PHP-FPM por socket `/run/php/php8.3-fpm.sock` | `systemctl status php8.3-fpm` |
| **Base de datos** | MySQL 8 / MariaDB / PostgreSQL / SQLite | MySQL 8, BD `inventrack_preprod`, usuario propio con permisos sólo sobre esa BD | `mysql -u inventrack_preprod -p -e "SHOW DATABASES;"` |
| **Variables de entorno** | `.env` | `/var/www/inventrack-preprod/shared/.env` (plantilla `deploy/.env.preprod.example`), `APP_ENV=staging`, `APP_DEBUG=false`, `APP_KEY` propia | `php artisan about` |
| **Permisos** | `storage/` y `bootstrap/cache/` escribibles | Dueño `deploy:www-data`, modo `2775`, ACL `g:www-data:rwX` | `ls -la shared/storage` |
| **Usuario de despliegue** | — | `deploy` (sin contraseña, acceso sólo por llave SSH, miembro de `www-data`) | `id deploy` |

## 3.3 Estructura de carpetas en el servidor

```
/var/www/inventrack-preprod/
├── current -> releases/v1.0.1     # versión activa (Nginx apunta aquí)
├── releases/
│   ├── v1.0.0/                    # versiones anteriores (rollback)
│   └── v1.0.1/
├── shared/
│   ├── .env                       # variables del ambiente (permiso 640)
│   └── storage/                   # logs, sesiones, archivos subidos
└── deploy-history.log             # bitácora de despliegues
```

`.env` y `storage/` viven en `shared/` y se enlazan en cada versión, así **el artefacto nunca trae configuración ni datos** y los archivos persisten entre despliegues.

## 3.4 Variables independientes por ambiente

| Variable | Desarrollo (local) | CI (GitHub Actions) | Preproducción |
|---|---|---|---|
| `APP_ENV` | `local` | `testing` | `staging` |
| `APP_DEBUG` | `true` | `true` | `false` |
| `APP_KEY` | propia | generada en cada ejecución | propia, generada en el servidor |
| `DB_CONNECTION` | mysql/sqlite | sqlite | mysql |
| `DB_DATABASE` | `laravel` | `database/database.sqlite` | `inventrack_preprod` |
| `MAIL_MAILER` | `log` | `array` | `log` |
| `LOG_LEVEL` | `debug` | `debug` | `info` |

## 3.5 Secretos y variables en GitHub

**Settings → Environments → New environment → `preproduccion`**

| Tipo | Nombre | Valor |
|---|---|---|
| Secret | `PREPROD_HOST` | IP o dominio del servidor |
| Secret | `PREPROD_USER` | `deploy` |
| Secret | `PREPROD_SSH_KEY` | Llave **privada** generada con `ssh-keygen -t ed25519 -f gh_deploy -N ""` |
| Variable | `PREPROD_URL` | `http://preprod.local` |
| Variable | `PREPROD_PATH` | `/var/www/inventrack-preprod` |

La llave **pública** (`gh_deploy.pub`) se agrega a `/home/deploy/.ssh/authorized_keys` en el servidor. Opcional: en el environment activar **Required reviewers** para que un docente o líder apruebe cada entrega.

> Si tu VM no es accesible desde Internet (lo normal en una VM local), el job `deploy-preprod` se **omite con un aviso** y la entrega se hace manualmente (sección 3.6).

## 3.6 Instalación manual del artefacto

```bash
# En tu máquina: descargar el artefacto de la última ejecución
gh run download --name inventrack-release-v1.0.0 --dir ./descarga
scp descarga/inventrack-release-v1.0.0.tar.gz* scripts/deploy-preprod.sh deploy@preprod.local:/tmp/

# En el servidor
cd /tmp && sha256sum -c inventrack-release-v1.0.0.tar.gz.sha256
bash /tmp/deploy-preprod.sh /tmp/inventrack-release-v1.0.0.tar.gz v1.0.0 /var/www/inventrack-preprod
```

## 3.7 Rollback

```bash
cd /var/www/inventrack-preprod
ls -1t releases/                         # ver versiones disponibles
ln -sfn releases/v1.0.0 current.tmp && mv -Tf current.tmp current
sudo systemctl reload php8.3-fpm
```
