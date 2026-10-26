# Guía para subir InvenTrack a GitHub y obtener las evidencias

Tiempo estimado: 30–45 minutos.

## 0. Instala lo necesario (si no lo tienes)

| Programa | Descarga | Comprobar |
|---|---|---|
| Git | https://git-scm.com/downloads | `git --version` |
| PHP 8.2+ (Windows: Laragon o XAMPP con PHP 8.3) | https://laragon.org | `php -v` |
| Composer | https://getcomposer.org/download | `composer --version` |
| Node.js 20 LTS | https://nodejs.org | `node -v` |

> Si no puedes instalar PHP/Node, sáltate el paso 2: GitHub Actions instala todo por ti y la entrega sigue siendo válida.

## 1. Descomprime el proyecto

Descomprime `inventrack.zip` en una carpeta (por ejemplo `Documentos/inventrack`) y abre una terminal ahí.

## 2. (Opcional) Pruébalo en tu computadora

```bash
composer install
npm install            # genera package-lock.json (súbelo también)
cp .env.example .env   # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed   # si pregunta por crear database.sqlite, responde "yes"
npm run build
php artisan test       # deben salir 42 passed
php artisan serve      # http://localhost:8000  admin@inventrack.test / password
```

## 3. Crea el repositorio en GitHub

1. Entra a https://github.com/new
2. Nombre: `inventrack` · Público · **sin** README, .gitignore ni licencia.
3. Pulsa **Create repository**.

## 4. Sube el código

```bash
git init
git add .
git commit -m "InvenTrack: aplicación, pruebas y pipeline de CI"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/inventrack.git
git push -u origin main
```

## 5. Revisa el pipeline y toma las capturas

1. En tu repositorio abre la pestaña **Actions**: verás "CI InvenTrack" ejecutándose. → **Captura 1**
2. Entra a la ejecución y luego al job **Build y pruebas (PHP 8.3)**; espera a que todos los pasos estén en verde (unos 2–4 minutos). → **Captura 2**
3. Regresa a la página de la ejecución y baja hasta **Resultado del pipeline InvenTrack** (la tabla con las 42 pruebas). → **Captura 3**
4. Página principal del repositorio con los archivos y el README. → **Captura 4**

Si algún paso sale en rojo: ábrelo, copia el error y corrígelo (o pídeme ayuda con el texto del error). Toma captura del fallo y de la corrección: la actividad pide documentar los problemas.

## 6. (Recomendado) Evidencia con pull request

```bash
git checkout -b mejora-readme
# edita cualquier línea del README.md
git commit -am "Actualiza README"
git push -u origin mejora-readme
```

En GitHub, pulsa **Compare & pull request** → **Create pull request**. El pipeline correrá sobre el PR y mostrará el check verde antes del merge. → **Captura 5**. Después haz **Merge**.

Opcional: *Settings → Branches → Add rule* para `main` → marca **Require status checks to pass before merging** y elige el check "Build y pruebas (PHP 8.3)".

## 7. Completa los entregables

- **README.md**: reemplaza `USUARIO` por tu usuario de GitHub (2 veces, en el badge).
- **Reporte Word**: busca los textos resaltados en amarillo (**INSERTAR CAPTURA / INSERTAR URL**) y pega ahí las capturas y la URL del repositorio.
- **Excel**: hoja *Verificaciones CI*, celda amarilla G10 → escribe el resultado de `npm run build` en GitHub Actions (por ejemplo "Aprobada").
- Guarda tus capturas también en `docs/evidencias/` y súbelas con `git add . && git commit -m "Evidencias de GitHub Actions" && git push`.
