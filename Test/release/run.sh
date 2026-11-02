#!/usr/bin/env bash
# Pruebas de lanzamiento de fsRepublicaDominicana: ejecuta los casos y deja las evidencias.
#
# Variables:
#   RELEASE_TESTS_CONFIRM=yes   obligatoria: la suite ESCRIBE datos en la base de datos de FS_FOLDER
#   FS_FOLDER                   carpeta de la instalación de FacturaScripts con el plugin activo
#   FS_URL                      URL de esa instalación (p. ej. http://localhost/~usuario/fs2025beta)
#   FS_USER / FS_PASS           usuario administrador (por defecto admin / admin)
#   PHP_BIN                     ejecutable de PHP (por defecto php)
#
# Uso: RELEASE_TESTS_CONFIRM=yes FS_FOLDER=... FS_URL=... FS_PASS=... Test/release/run.sh
set -uo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN="$(cd "$HERE/../.." && pwd)"
: "${FS_FOLDER:?Indique FS_FOLDER (instalación de FacturaScripts de pruebas)}"
: "${FS_URL:?Indique FS_URL (URL de esa instalación)}"
PHP_BIN="${PHP_BIN:-php}"

if [ "${RELEASE_TESTS_CONFIRM:-}" != "yes" ]; then
  echo "Esta suite crea proveedores, clientes, productos y facturas en la base de datos de:" >&2
  echo "  $FS_FOLDER" >&2
  echo "Use una instalación de pruebas y repita con RELEASE_TESTS_CONFIRM=yes" >&2
  exit 2
fi

VERSION="$(sed -n 's/^version *= *//p' "$PLUGIN/facturascripts.ini" | head -1 | tr -d ' \r')"
OUT="$HERE/evidencias/v${VERSION}-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$OUT"

echo "== Casos de prueba (versión $VERSION) =="
FS_FOLDER="$FS_FOLDER" "$PHP_BIN" -d display_errors=0 -d error_reporting=0 "$HERE/run.php" "$OUT"
CASES=$?
if [ ! -f "$OUT/resultados.json" ]; then
  echo "La ejecución no generó resultados.json" >&2
  exit 2
fi

echo "== Evidencias (capturas, PDF e informe) =="
SHOTS="$PLUGIN/docs-tools/screenshots"
[ -d "$SHOTS/node_modules" ] || (cd "$SHOTS" && npm ci && npx playwright install chromium)
(cd "$SHOTS" && FS_URL="$FS_URL" FS_USER="${FS_USER:-admin}" FS_PASS="${FS_PASS:-admin}" node release-evidence.mjs "$OUT")
EVIDENCE=$?

(cd "$OUT/.." && zip -qr "$(basename "$OUT").zip" "$(basename "$OUT")") 2>/dev/null && echo "Paquete: $OUT.zip"
echo "Informe: $OUT/informe.md"
[ "$CASES" -eq 0 ] && [ "$EVIDENCE" -eq 0 ]
