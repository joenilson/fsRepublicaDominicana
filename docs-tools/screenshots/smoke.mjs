// Pruebas de humo del JavaScript del plugin contra la instalación limpia (docs-tools/docker).
// Uso: npm run smoke   (requiere haber ejecutado: npm run all  y  seed.php)
import assert from 'node:assert/strict';
import { writeFileSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { open, login, BASE } from './lib.mjs';

const { browser, page } = await open();
const results = [];
const failures = [];

async function test(name, fn) {
  try {
    await fn();
    results.push(`ok   ${name}`);
  } catch (error) {
    failures.push(name);
    results.push(`FAIL ${name}\n     ${String(error.message).split('\n')[0]}`);
  }
}

/** Acciones AJAX del plugin enviadas durante `action`. */
async function ajaxActions(action) {
  const sent = [];
  const listener = (request) => {
    const body = request.postData() || '';
    const match = body.match(/(?:^|&)action=([^&]+)/);
    if (request.method() === 'POST' && match) sent.push(decodeURIComponent(match[1]));
  };
  page.on('request', listener);
  await action();
  await page.waitForTimeout(1200);
  page.off('request', listener);
  return sent;
}

async function invoiceLink(listUrl, ncf) {
  await page.goto(`${BASE}/${listUrl}`);
  await page.waitForLoadState('networkidle');
  const href = await page.locator('tr', { hasText: ncf }).first().locator('a[href*="?code="]').first().getAttribute('href');
  return href.replace(/^\//, '');
}

const pageErrors = [];
page.on('pageerror', (error) => pageErrors.push(error.message));

await login(page);
const salesUrl = await invoiceLink('ListFacturaCliente', 'B0100000001');
const purchaseUrl = await invoiceLink('ListFacturaProveedor', 'B0100000001');

await test('ventas: un cambio de tipo de comprobante hace una sola consulta de correlativo', async () => {
  await page.goto(`${BASE}/${salesUrl}`);
  await page.waitForLoadState('networkidle');
  const sent = await ajaxActions(() => page.selectOption("select[name='tipocomprobante']", '02'));
  assert.equal(sent.filter((a) => a === 'busca_correlativo').length, 1, `acciones: ${sent}`);
});

await test('ventas: los campos de nota de débito solo se ven con el tipo 33', async () => {
  await page.goto(`${BASE}/${salesUrl}`);
  await page.waitForLoadState('networkidle');
  await page.selectOption("select[name='tipocomprobante']", '33');
  assert.equal(await page.locator('#divIdFacturaRect').isVisible(), true);
  assert.equal(await page.locator('#divNcfCodigoModificacion').isVisible(), true);
  await page.selectOption("select[name='tipocomprobante']", '01');
  assert.equal(await page.locator('#divIdFacturaRect').isVisible(), false);
});

await test('compras: tipos de proveedor no consultan correlativo, los propios sí', async () => {
  await page.goto(`${BASE}/${purchaseUrl}`);
  await page.waitForLoadState('networkidle');
  const own = await page.getAttribute("select[name='tipocomprobante']", 'data-own-types');
  assert.equal(own, '11,13,41,43');
  const foreign = await ajaxActions(() => page.selectOption("select[name='tipocomprobante']", '01'));
  assert.equal(foreign.filter((a) => a === 'busca_correlativo').length, 0, `acciones: ${foreign}`);
  const mine = await ajaxActions(() => page.selectOption("select[name='tipocomprobante']", '41'));
  assert.equal(mine.filter((a) => a === 'busca_correlativo').length, 1, `acciones: ${mine}`);
});

await test('compras: verificar un NCF ya registrado muestra el aviso', async () => {
  await page.goto(`${BASE}/${purchaseUrl}`);
  await page.waitForLoadState('networkidle');
  await page.click('#btnVerifyNCF');
  await page.waitForSelector('#ncfExists.show', { timeout: 10000 });
  assert.match(await page.innerText('#ncfExists'), /NCF Ya registrado/);
});

await test('compras: XML e-CF se muestra escapado y se aplica al formulario', async () => {
  const dir = mkdtempSync(join(tmpdir(), 'ecf-'));
  const file = join(dir, 'ecf.xml');
  writeFileSync(file, `<?xml version="1.0" encoding="UTF-8"?>
<ECF><Encabezado><IdDoc><TipoeCF>31</TipoeCF><eNCF>E310000000099</eNCF><FechaVencimientoSecuencia>31-12-2028</FechaVencimientoSecuencia></IdDoc>
<Emisor><RNCEmisor>132000002</RNCEmisor><RazonSocialEmisor>&lt;img src=x onerror="window.__xss=1"&gt;</RazonSocialEmisor>
<FechaEmision>14-10-2026</FechaEmision></Emisor>
<Totales><MontoTotal>1180.00</MontoTotal></Totales></Encabezado>
<DetallesItems><Item><NumeroLinea>1</NumeroLinea><NombreItem>&lt;b&gt;Producto&lt;/b&gt;</NombreItem></Item></DetallesItems>
<FechaHoraFirma>14-10-2026 10:30:00</FechaHoraFirma></ECF>`);
  await page.goto(`${BASE}/${purchaseUrl}`);
  await page.waitForLoadState('networkidle');
  await page.setInputFiles('#xmlFile', file);
  assert.equal(await page.locator('#btnParseXml').isDisabled(), false);
  await page.click('#btnParseXml');
  await page.waitForSelector('#xmlResultModal.show', { timeout: 10000 });
  const summary = await page.innerText('#xmlSummary');
  assert.match(summary, /E310000000099/);
  assert.match(summary, /<img src=x/, 'el texto del XML debe mostrarse literal, no interpretarse');
  assert.equal(await page.evaluate(() => window.__xss), undefined);
  assert.equal(await page.locator('#xmlItems b').count(), 0, 'las etiquetas del XML no deben convertirse en HTML');
  await page.click('#xmlResultModal button:has-text("Usar")');
  await page.waitForTimeout(500);
  assert.equal(await page.inputValue("input[name='numeroncf']"), 'E310000000099');
  assert.equal(await page.inputValue("select[name='tipocomprobante']"), '31');
  // el vencimiento es el FechaVencimientoSecuencia del XML
  assert.equal(await page.inputValue("input[name='ncffechavencimiento']"), '2028-12-31');
});

await test('la fecha de vencimiento digitada no se sobrescribe y la vacía o automática sí', async () => {
  await page.goto(`${BASE}/${salesUrl}`);
  await page.waitForLoadState('networkidle');
  const steps = await page.evaluate(() => {
    const name = 'ncffechavencimiento';
    const input = window.jQuery(`input[name='${name}']`);
    const out = {};
    input.val('2031-02-03');                            // digitada por el contador
    out.manual = [window.fsRD.autoFill(name, '2027-12-31'), input.val()];
    input.val('');
    out.empty = [window.fsRD.autoFill(name, '2027-12-31'), input.val()];
    out.again = [window.fsRD.autoFill(name, '2028-12-31'), input.val()];   // sigue siendo automática
    input.val('2032-05-06');                            // el contador la cambia después
    out.edited = [window.fsRD.autoFill(name, '2029-12-31'), input.val()];
    return out;
  });
  assert.deepEqual(steps.manual, [false, '2031-02-03']);
  assert.deepEqual(steps.empty, [true, '2027-12-31']);
  assert.deepEqual(steps.again, [true, '2028-12-31']);
  assert.deepEqual(steps.edited, [false, '2032-05-06']);
});

await test('compras: un XML sin vencimiento conserva la fecha digitada', async () => {
  const dir = mkdtempSync(join(tmpdir(), 'ecf-'));
  const file = join(dir, 'ecf34.xml');
  writeFileSync(file, `<?xml version="1.0" encoding="UTF-8"?>
<ECF><Encabezado><IdDoc><TipoeCF>34</TipoeCF><eNCF>E340000000088</eNCF></IdDoc>
<Emisor><RNCEmisor>132000002</RNCEmisor><FechaEmision>14-10-2026</FechaEmision></Emisor></Encabezado></ECF>`);
  await page.goto(`${BASE}/${purchaseUrl}`);
  await page.waitForLoadState('networkidle');
  await page.fill("input[name='ncffechavencimiento']", '2030-01-15');
  await page.setInputFiles('#xmlFile', file);
  await page.click('#btnParseXml');
  await page.waitForSelector('#xmlResultModal.show', { timeout: 10000 });
  await page.click('#xmlResultModal button:has-text("Usar")');
  await page.waitForTimeout(500);
  assert.equal(await page.inputValue("input[name='numeroncf']"), 'E340000000088');
  assert.equal(await page.inputValue("input[name='ncffechavencimiento']"), '2030-01-15');
});

await test('ventas: el XML de una devolución llena los campos con sufijo r', async () => {
  const dir = mkdtempSync(join(tmpdir(), 'ecf-'));
  const file = join(dir, 'nc.xml');
  writeFileSync(file, `<?xml version="1.0" encoding="UTF-8"?>
<ECF><Encabezado><IdDoc><TipoeCF>34</TipoeCF><eNCF>E340000000077</eNCF></IdDoc>
<Emisor><RNCEmisor>101000000</RNCEmisor><FechaEmision>14-10-2026</FechaEmision></Emisor></Encabezado>
<FechaHoraFirma>14-10-2026 11:00:00-04:00</FechaHoraFirma></ECF>`);
  await page.goto(`${BASE}/${salesUrl}`);
  await page.waitForLoadState('networkidle');
  await page.locator('a.nav-link', { hasText: 'Devoluciones' }).first().click();
  await page.waitForSelector('#xmlFileR', { state: 'visible' });
  await page.setInputFiles('#xmlFileR', file);
  await page.click('#btnParseXmlR');
  await page.waitForSelector('#xmlResultModal.show', { timeout: 10000 });
  await page.click('#xmlResultModal button:has-text("Usar")');
  await page.waitForTimeout(500);
  assert.equal(await page.inputValue("select[name='tipocomprobanter']"), '34');
  assert.equal(await page.inputValue("input[name='ecf_fecha_firmar']"), '2026-10-14T11:00'); // el navegador omite los segundos en cero
});

await test('productos/ISC: el modal de línea tiene selector con búsqueda', async () => {
  await page.goto(`${BASE}/${salesUrl}`);
  await page.waitForLoadState('networkidle');
  await page.locator('button:has([data-icon="ellipsis"])').first().click();
  await page.waitForSelector('.modal.show select[data-isc-select]', { state: 'attached' });
  await page.waitForTimeout(500);
  assert.ok(await page.locator('.modal.show .select2-container').count() > 0, 'select2 no se inicializó');
});

await test('sin errores de JavaScript sin capturar en las pantallas probadas', async () => {
  assert.deepEqual(pageErrors, []);
});

await browser.close();
console.log(results.join('\n'));
console.log(`\n${results.length - failures.length}/${results.length} pruebas correctas`);
process.exit(failures.length === 0 ? 0 : 1);
