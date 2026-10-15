# How this documentation is built

The documentation is Markdown under `docs/`, built with [MkDocs Material](https://squidfunnel.github.io/mkdocs-material/)
and published to GitHub Pages with GitHub Actions.

## Layout

```text
mkdocs.yml                  site configuration, navigation and languages
requirements-docs.txt       Python dependencies
docs/                       pages (each as .es.md and .en.md)
docs/assets/img/            generated screenshots
docs-tools/docker/          clean FacturaScripts install + synthetic data
docs-tools/screenshots/     Playwright script that takes the screenshots
.github/workflows/docs.yml  builds and publishes to GitHub Pages
```

## Writing or editing pages

Every page has two files: `page.es.md` (default language) and `page.en.md`. If a translation is missing, the site
shows the Spanish version.

```bash
python3 -m venv .venv && . .venv/bin/activate
pip install -r requirements-docs.txt
mkdocs serve            # http://127.0.0.1:8000
mkdocs build --strict   # same as CI: fails on broken links
```

To add a page, create it in `docs/` and add it to `nav:` in `mkdocs.yml`. If the title needs translating, add an
entry under `nav_translations` for the `en` language.

## Regenerating screenshots

Screenshots **must never come from an installation with real data**. Use the clean environment:

```bash
# 1. Clean install (FacturaScripts v2026.65 + MariaDB, no persistent volumes)
docker compose -f docs-tools/docker/docker-compose.yml up -d --build

# 2. Installer wizard and plugin activation
cd docs-tools/screenshots
npm install && npm run setup
npm run all   # install.mjs → setup.mjs (wizard) → enable.mjs (plugin)

# 3. Synthetic data (NCF ranges, customers, invoices, notes)
cd ../..
docker compose -f docs-tools/docker/docker-compose.yml exec -T web \
  php Plugins/fsRepublicaDominicana/docs-tools/docker/seed.php

# 4. Take the screenshots into docs/assets/img
cd docs-tools/screenshots && npm run capture
```

The container mounts the repository **read only**, and the database lives in memory: when the environment is
brought down (`docker compose ... down`) nothing remains.

!!! info "Screenshot data"
    Company *Empresa Demo SRL*, RNC `101000000`, customer *Comercial Demo SRL* and everything else is made up.
    The `admin/admin` user only exists inside the local container.

## Publishing

The `.github/workflows/docs.yml` workflow builds with `mkdocs build --strict` and publishes with
`actions/deploy-pages` on every push to `master` that touches `docs/`, `mkdocs.yml` or the requirements.

One-time GitHub setup: **Settings → Pages → Build and deployment → Source: GitHub Actions**.
