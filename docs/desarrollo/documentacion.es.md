# Cómo se genera esta documentación

La documentación es Markdown en `docs/`, se compila con [MkDocs Material](https://squidfunnel.github.io/mkdocs-material/)
y se publica en GitHub Pages con GitHub Actions.

## Estructura

```text
mkdocs.yml                  configuración del sitio, navegación e idiomas
requirements-docs.txt       dependencias de Python
docs/                       páginas (cada una en .es.md y .en.md)
docs/assets/img/            capturas generadas
docs-tools/docker/          instalación limpia de FacturaScripts + datos sintéticos
docs-tools/screenshots/     script de Playwright que toma las capturas
.github/workflows/docs.yml  compila y publica en GitHub Pages
```

## Escribir o editar páginas

Cada página existe en dos archivos: `pagina.es.md` (idioma por defecto) y `pagina.en.md`. Si falta la traducción,
el sitio muestra la versión en español.

```bash
python3 -m venv .venv && . .venv/bin/activate
pip install -r requirements-docs.txt
mkdocs serve            # http://127.0.0.1:8000
mkdocs build --strict   # igual que el CI: falla ante enlaces rotos
```

Para agregar una página, créela en `docs/` y agréguela a `nav:` en `mkdocs.yml`. Si el título necesita
traducción, añada la entrada en `nav_translations` del idioma `en`.

## Regenerar las capturas

Las capturas **no deben salir de una instalación con datos reales**. Use el entorno limpio:

```bash
# 1. Instalación limpia (FacturaScripts v2026.65 + MariaDB, sin volúmenes persistentes)
docker compose -f docs-tools/docker/docker-compose.yml up -d --build

# 2. Asistente de instalación y activación del plugin
cd docs-tools/screenshots
npm install && npm run setup
npm run all   # install.mjs → setup.mjs (asistente) → enable.mjs (plugin)

# 3. Datos sintéticos (rangos NCF, clientes, facturas, notas)
cd ../..
docker compose -f docs-tools/docker/docker-compose.yml exec -T web \
  php Plugins/fsRepublicaDominicana/docs-tools/docker/seed.php

# 4. Tomar las capturas en docs/assets/img
cd docs-tools/screenshots && npm run capture
```

El contenedor monta el repositorio **en solo lectura**, y la base de datos vive en memoria: al bajar el entorno
(`docker compose ... down`) no queda nada.

!!! info "Datos de las capturas"
    Empresa *Empresa Demo SRL*, RNC `101000000`, cliente *Comercial Demo SRL* y demás datos son inventados.
    El usuario `admin/admin` solo existe dentro del contenedor local.

## Publicación

El flujo `.github/workflows/docs.yml` compila con `mkdocs build --strict` y publica con `actions/deploy-pages`
en cada *push* a `master` que cambie `docs/`, `mkdocs.yml` o los requisitos.

Configuración única en GitHub: **Settings → Pages → Build and deployment → Source: GitHub Actions**.
