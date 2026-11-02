// Evidencias visuales de las pruebas de lanzamiento (Test/release/run.php).
// Lee resultados.json, abre cada documento creado, guarda capturas y PDF, captura los reportes
// 606/607/608 y escribe informe.md con el resultado de cada caso.
//
// Uso: FS_URL=http://localhost:8089 FS_USER=admin FS_PASS=admin node release-evidence.mjs <carpeta-evidencias>
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { open, login, BASE } from './lib.mjs';

const dir = process.argv[2];
if (!dir) {
  console.error('Uso: node release-evidence.mjs <carpeta-evidencias>');
  process.exit(2);
}
const data = JSON.parse(readFileSync(join(dir, 'resultados.json'), 'utf8'));
mkdirSync(join(dir, 'capturas'), { recursive: true });
mkdirSync(join(dir, 'pdf'), { recursive: true });

const { browser, page } = await open();
const pageErrors = [];
page.on('pageerror', (e) => pageErrors.push(e.message));
await login(page);

const files = {}; // id de caso -> evidencias
const add = (id, kind, path) => (files[id] ??= []).push({ kind, path });

async function screenshot(id, name, url, fullPage = true) {
  await page.goto(`${BASE}/${url}`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(300);
  const rel = `capturas/${id}-${name}.png`;
  await page.screenshot({ path: join(dir, rel), fullPage });
  const bodyText = await page.locator('body').innerText();
  if (/Fatal error|Uncaught|SQLSTATE|Stack trace/i.test(bodyText)) {
    throw new Error(`La página ${url} muestra un error de PHP/SQL`);
  }
  add(id, 'captura', rel);
}

for (const step of data.pasos) {
  let n = 0;
  for (const doc of step.docs) {
    if (!doc.controlador || doc.id === null) continue;
    n++;
    const slug = `${doc.tipo}-${doc.codigo ?? doc.id}`.replace(/[^A-Za-z0-9_-]/g, '');
    try {
      await screenshot(step.id, `${n}-${slug}`, `${doc.controlador}?code=${doc.id}`);
      // PDF de las facturas, con el mismo enlace que usa el botón Imprimir
      if (doc.tipo === 'FacturaCliente' || doc.tipo === 'FacturaProveedor') {
        const response = await page.context().request.get(
          `${BASE}/${doc.controlador}?code=${doc.id}&action=export&option=PDF&lang=es_ES`
        );
        const body = await response.body();
        if (response.ok() && body.subarray(0, 4).toString() === '%PDF') {
          const rel = `pdf/${step.id}-${slug}.pdf`;
          writeFileSync(join(dir, rel), body);
          add(step.id, 'pdf', rel);
        } else {
          (step.evidenciaFallos ??= []).push(`PDF de ${doc.codigo}: respuesta ${response.status()} sin PDF`);
        }
      }
    } catch (error) {
      (step.evidenciaFallos ??= []).push(`${doc.tipo} ${doc.codigo}: ${error.message}`);
    }
  }
}

// Reportes fiscales del mes en curso (el período por defecto es el mes anterior)
async function reportShot(id, tab) {
  await page.goto(`${BASE}/FiscalReports?activetab=${tab}`);
  await page.waitForLoadState('networkidle');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.evaluate((t) => {
      const f = document.createElement('form');
      f.method = 'post';
      f.action = `FiscalReports?activetab=${t}`;
      for (const [k, v] of Object.entries({ activetab: t, filterfecha: 'this-month' })) {
        const i = document.createElement('input');
        i.type = 'hidden';
        i.name = k;
        i.value = v;
        f.appendChild(i);
      }
      document.body.appendChild(f);
      f.submit();
    }, tab),
  ]);
  await page.waitForTimeout(400);
  const rel = `capturas/${id}-${tab}.png`;
  await page.screenshot({ path: join(dir, rel), fullPage: true });
  const bodyText = await page.locator('body').innerText();
  if (/Fatal error|Uncaught|SQLSTATE|Stack trace/i.test(bodyText)) {
    throw new Error(`El reporte ${tab} muestra un error de PHP/SQL`);
  }
  add(id, 'captura', rel);
}
const reportStep = data.pasos.find((s) => s.id === 'R01');
if (reportStep) {
  for (const tab of ['FiscalReports-consolidated', 'FiscalReport606', 'FiscalReport607', 'FiscalReport608']) {
    try {
      await reportShot('R01', tab);
    } catch (error) {
      (reportStep.evidenciaFallos ??= []).push(error.message);
    }
  }
  for (const txt of data.reportes?.txt ?? []) add('R01', 'txt', txt);
}

await browser.close();

// ---------------------------------------------------------------- informe.md
const icon = { OK: '✅', ADVERTENCIA: '⚠️', FALLA: '❌', OMITIDA: '⏭️' };
const total = data.pasos.length;
let md = `# Pruebas de lanzamiento — fsRepublicaDominicana\n\n`;
md += `- Fecha: ${data.fecha}\n- Versión del plugin: ${data.plugin?.version ?? 'desconocida'}\n- Prefijo de datos: \`${data.prefijo}\`\n`;
md += `- Resultado: **${data.resumen.OK}/${total} correctos**, ${data.resumen.ADVERTENCIA ?? 0} con advertencia, ${data.resumen.FALLA} con falla, ${data.resumen.OMITIDA} omitidos\n`;
if (data.reportes) {
  md += `- Reportes ${data.reportes.periodo}: 606 = ${data.reportes['606']} filas, 607 = ${data.reportes['607']}, 608 = ${data.reportes['608']}\n`;
}
if (pageErrors.length) md += `- Errores de JavaScript en el navegador: ${pageErrors.length}\n`;
md += `\n| Caso | Resultado | Descripción |\n|---|---|---|\n`;
for (const s of data.pasos) md += `| ${s.id} | ${icon[s.estado]} ${s.estado} | ${s.titulo} |\n`;
md += `\n## Detalle\n`;
for (const s of data.pasos) {
  md += `\n### ${s.id} — ${s.titulo} ${icon[s.estado]}\n\n`;
  if (s.motivo) md += `> ${s.motivo}\n\n`;
  for (const c of s.checks) md += `- ${c.ok ? '✔' : c.aviso ? '⚠' : '✘'} ${c.detalle}\n`;
  for (const d of s.docs) {
    md += `- Documento: ${d.tipo} \`${d.codigo ?? d.id}\`${d.ncf ? ` · NCF ${d.ncf}` : ''}${d.divisa ? ` · ${d.divisa}` : ''}${d.total !== null ? ` · total ${d.total}` : ''}\n`;
  }
  for (const l of [...new Set(s.logs)]) md += `- Mensaje de FacturaScripts: ${l.replace(/\n/g, ' ')}\n`;
  for (const f of s.evidenciaFallos ?? []) md += `- ⚠ Evidencia incompleta: ${f}\n`;
  for (const e of files[s.id] ?? []) {
    md += e.kind === 'captura' ? `\n![${e.path}](${e.path})\n` : `- ${e.kind.toUpperCase()}: [${e.path}](${e.path})\n`;
  }
}
writeFileSync(join(dir, 'informe.md'), md);
writeFileSync(join(dir, 'resultados.json'), JSON.stringify(data, null, 2));
console.log(`Informe: ${join(dir, 'informe.md')}`);
process.exit(data.resumen.FALLA > 0 || data.pasos.some((s) => s.evidenciaFallos?.length) ? 1 : 0);
