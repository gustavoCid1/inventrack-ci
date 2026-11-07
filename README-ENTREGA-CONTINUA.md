# Entrega continua – InvenTrack (Laravel 11)

| Dato | Valor |
|---|---|
| Alumno | **[TU NOMBRE COMPLETO]** |
| Matrícula | **[MATRÍCULA]** |
| Grupo / Materia | **[GRUPO] – [MATERIA]** |
| Docente | **[NOMBRE DEL DOCENTE]** |
| Repositorio | `https://github.com/gustavoCid1/inventrack-ci` |
| Versión entregada | `v1.0.0` |

![CI/CD](https://github.com/gustavoCid1/inventrack-ci/actions/workflows/release.yml/badge.svg)

## Contenido del entregable

| Requisito | Archivo |
|---|---|
| Workflow de liberación continua | [`.github/workflows/release.yml`](.github/workflows/release.yml) |
| Configuración de generación de artefactos | `release.yml` (job `build-release`) + [`scripts/build-release.sh`](scripts/build-release.sh) |
| Scripts para preparar el entorno de liberación | [`scripts/setup-preprod-server.sh`](scripts/setup-preprod-server.sh), [`scripts/deploy-preprod.sh`](scripts/deploy-preprod.sh), [`deploy/`](deploy/) |
| Scripts de validación | [`scripts/verify-artifact.sh`](scripts/verify-artifact.sh), [`scripts/smoke-test-artifact.sh`](scripts/smoke-test-artifact.sh) |
| Diseño del proceso + **diagrama** | [`docs/01-diseno-proceso-liberacion.md`](docs/01-diseno-proceso-liberacion.md) |
| Parámetros del ambiente de preproducción | [`docs/02-ambiente-preproduccion.md`](docs/02-ambiente-preproduccion.md) |
| Evidencia de validaciones | [`docs/03-validacion-artefacto.md`](docs/03-validacion-artefacto.md) + [`docs/evidencias/`](docs/evidencias/) |
| Historial de versiones | [`CHANGELOG.md`](CHANGELOG.md) |

## Flujo resumido

```
push/tag → [ci] pruebas → [build-release] artefacto vX.Y.Z → [validate] instalación limpia → [deploy-preprod] servidor de pruebas
```

## Cómo generar una versión

```bash
git add . && git commit -m "feat: ..." && git push          # genera versión candidata vX.Y.Z-rc.N
git tag -a v1.0.0 -m "Liberación 1.0.0" && git push origin v1.0.0   # genera versión oficial
```

## Probar localmente (sin GitHub)

```bash
bash scripts/build-release.sh v1.0.0-local
bash scripts/verify-artifact.sh dist/inventrack-release-v1.0.0-local.tar.gz
bash scripts/smoke-test-artifact.sh dist/inventrack-release-v1.0.0-local.tar.gz
```
