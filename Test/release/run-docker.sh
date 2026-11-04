#!/usr/bin/env bash
# Pruebas de lanzamiento en un FacturaScripts desechable con Docker o Podman (imagen oficial,
# ver https://facturascripts.com/descargar/docker). No toca ninguna instalación local.
#
# Hace: levanta MySQL + FacturaScripts, instala sin asistente, copia el plugin, lo activa, corre los
# casos dentro del contenedor y genera capturas, PDF e informe contra http://127.0.0.1:$FS_PORT.
#
# Variables (todas opcionales):
#   CONTAINER_ENGINE  docker o podman           (por defecto: el primero que responda)
#   FS_TAG            etiqueta de la imagen     (latest; beta para la última beta; o 2026.65)
#   FS_PORT           puerto local              (8090)
#   FS_USER/FS_PASS   administrador             (admin / adminadmin)
#   KEEP=1            no borrar los volúmenes al terminar (para inspeccionar el entorno)
#   FRESH=1           borrar los volúmenes antes de empezar (instalación limpia)
#
# Uso: Test/release/run-docker.sh
set -uo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN="$(cd "$HERE/../.." && pwd)"
COMPOSE_FILE="$HERE/docker/docker-compose.yml"
export FS_PORT="${FS_PORT:-8090}"
export FS_TAG="${FS_TAG:-latest}"
FS_USER="${FS_USER:-admin}"
FS_PASS="${FS_PASS:-adminadmin}"
FS_URL="http://127.0.0.1:$FS_PORT"
IN="/var/www/html"

ENGINE="${CONTAINER_ENGINE:-}"
if [ -z "$ENGINE" ]; then
  for e in docker podman; do
    if command -v "$e" >/dev/null 2>&1 && "$e" info >/dev/null 2>&1; then ENGINE="$e"; break; fi
  done
fi
[ -n "$ENGINE" ] || { echo "No hay Docker ni Podman en ejecución (inicie Docker Desktop o 'podman machine start')." >&2; exit 2; }
compose() { "$ENGINE" compose -f "$COMPOSE_FILE" "$@" 2>&1 | grep -v "Executing external compose provider\|^$"; return "${PIPESTATUS[0]}"; }

# Asistente inicial de FacturaScripts (país DOM): carga los impuestos ITBIS, el plan contable y el ejercicio.
# Debe ir antes de cualquier otro arranque: las tablas se crean con los datos del país vigente en ese momento.
wizard() {
  local jar page tok
  jar="$(mktemp)"; page="$(mktemp)"
  tok_of() { grep -o 'name="multireqtoken" value="[^"]*"' "$1" | head -1 | sed 's/.*value="\(.*\)"/\1/'; }
  post() { curl -s -m 900 -c "$jar" -b "$jar" -o "$page" -X POST "$FS_URL/$1" "${@:2}"; }
  curl -s -m 300 -c "$jar" -b "$jar" -o "$page" "$FS_URL/login"
  post login -d fsNick="$FS_USER" -d fsPassword="$FS_PASS" -d action=login -d multireqtoken="$(tok_of "$page")"
  curl -s -m 300 -c "$jar" -b "$jar" -o "$page" -L "$FS_URL/Wizard"
  post Wizard -d action=step1 -d multireqtoken="$(tok_of "$page")" -d "empresa=Empresa de Pruebas SRL" -d codpais=DOM \
    -d "direccion=Calle 1" -d codpostal=10101 -d ciudad="Santo Domingo" -d provincia="Distrito Nacional" -d apartado= -d email=pruebas@example.com
  post Wizard -d action=step2 -d multireqtoken="$(tok_of "$page")" -d cifnif=131000011 -d tipoidfiscal=RNC -d regimeniva=General \
    -d codimpuesto=ITBIS18 -d codpago=CONT -d defaultplan=1 -d ventasinstock=0 -d invoice_start_number=1
  curl -s -m 300 -c "$jar" -b "$jar" -o "$page" "$FS_URL/Wizard"
  post Wizard -d action=step3 -d multireqtoken="$(tok_of "$page")"
  rm -f "$jar" "$page"
}

VERSION="$(sed -n 's/^version *= *//p' "$PLUGIN/facturascripts.ini" | head -1 | tr -d ' \r')"
OUT="$HERE/evidencias/v${VERSION}-docker-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$OUT"

cleanup() {
  if [ "${KEEP:-}" = "1" ]; then
    echo "Entorno conservado en $FS_URL (compose -f $COMPOSE_FILE down -v para borrarlo)"
  else
    compose down -v >/dev/null
  fi
}
trap cleanup EXIT

echo "== Entorno ($ENGINE, facturascripts/facturascripts:$FS_TAG) =="
[ "${FRESH:-}" = "1" ] && compose down -v >/dev/null
compose up -d --wait >/dev/null || compose up -d >/dev/null || { echo "No se pudo levantar el entorno" >&2; exit 2; }
CID="$($ENGINE ps -q --filter "label=com.docker.compose.project=fs-rd-release" --filter "label=com.docker.compose.service=facturascripts" 2>/dev/null | head -1)"
[ -n "$CID" ] || CID="$($ENGINE ps -q --filter "name=fs-rd-release-facturascripts" | head -1)"
[ -n "$CID" ] || { echo "No se encontró el contenedor de FacturaScripts" >&2; exit 2; }
ex() { "$ENGINE" exec "$CID" "$@"; }
exi() { "$ENGINE" exec -i "$CID" "$@"; }

echo "== Instalación =="
for _ in $(seq 1 60); do
  [ "$(curl -s -o /dev/null -w '%{http_code}' "$FS_URL/")" = "200" ] && break
  sleep 2
done
if ! ex test -f "$IN/config.php"; then
  R="$(curl -s -m 600 -X POST "$FS_URL/" -d fs_db_type=mysql -d fs_db_host=mysql -d fs_db_port=3306 \
    -d fs_db_name=facturascripts -d fs_db_user=root -d fs_db_pass=mypassword \
    -d fs_initial_user="$FS_USER" -d fs_initial_pass="$FS_PASS" -d fs_lang=es_DO -d unattended=1)"
  [ "$R" = "OK" ] || { echo "El instalador respondió: ${R:0:200}" >&2; exit 2; }
  wizard
fi
ex sed -i "s#'FS_TIMEZONE', '[^']*'#'FS_TIMEZONE', 'America/Santo_Domingo'#" "$IN/config.php"

echo "== Copiando el plugin =="
ex rm -rf "$IN/Plugins/fsRepublicaDominicana"
ex mkdir -p "$IN/Plugins/fsRepublicaDominicana"
# macOS agrega archivos ._* y atributos extendidos al empaquetar: se evitan, o el deploy los toma por clases.
COPYFILE_DISABLE=1 tar -c --no-xattrs --no-mac-metadata \
  --exclude='._*' --exclude=.DS_Store --exclude=.git --exclude=.idea --exclude=.wolf --exclude=.claude \
  --exclude=.remember --exclude=.playwright-mcp --exclude=docs --exclude=docs-tools --exclude=site \
  --exclude=node_modules --exclude=evidencias --exclude=fsRepublicaDominicana.zip --exclude=Test/release/docker \
  -C "$PLUGIN" -f - . | exi tar -x -C "$IN/Plugins/fsRepublicaDominicana" -f - 2>&1 | grep -v "Ignoring unknown extended header"
ex test -f "$IN/Plugins/fsRepublicaDominicana/facturascripts.ini" || { echo "No se pudo copiar el plugin al contenedor" >&2; exit 2; }
ex chown -R www-data:www-data "$IN/Plugins" "$IN/MyFiles" "$IN/Dinamic" 2>/dev/null

echo "== Activando el plugin y reconstruyendo Dinamic =="
ex php -d display_errors=1 -r '
define("FS_FOLDER", getcwd()); require "vendor/autoload.php"; require "config.php";
FacturaScripts\Core\Kernel::init(); FacturaScripts\Core\Plugins::init();
if (!in_array("fsRepublicaDominicana", FacturaScripts\Core\Plugins::enabled(), true)) {
    if (!FacturaScripts\Core\Plugins::enable("fsRepublicaDominicana")) { fwrite(STDERR, "No se pudo activar el plugin\n"); exit(1); }
}
FacturaScripts\Core\Plugins::deploy(true, true); FacturaScripts\Core\Cache::clear();' || { echo "Falló la activación del plugin" >&2; exit 2; }
ex chown -R www-data:www-data "$IN/Dinamic" "$IN/MyFiles" 2>/dev/null

echo "== Casos de prueba (versión $VERSION) =="
ex rm -rf /tmp/release-out
ex env FS_FOLDER="$IN" php -d display_errors=0 -d error_reporting=0 "$IN/Plugins/fsRepublicaDominicana/Test/release/run.php" /tmp/release-out
CASES=$?
"$ENGINE" cp "$CID:/tmp/release-out/." "$OUT/" >/dev/null
[ -f "$OUT/resultados.json" ] || { echo "La ejecución no generó resultados.json" >&2; exit 2; }

echo "== Evidencias (capturas, PDF e informe) =="
SHOTS="$PLUGIN/docs-tools/screenshots"
[ -d "$SHOTS/node_modules" ] || (cd "$SHOTS" && npm ci && npx playwright install chromium)
(cd "$SHOTS" && FS_URL="$FS_URL" FS_USER="$FS_USER" FS_PASS="$FS_PASS" node release-evidence.mjs "$OUT")
EVIDENCE=$?

(cd "$OUT/.." && zip -qr "$(basename "$OUT").zip" "$(basename "$OUT")") 2>/dev/null && echo "Paquete: $OUT.zip"
echo "Informe: $OUT/informe.md"
[ "$CASES" -eq 0 ] && [ "$EVIDENCE" -eq 0 ]
