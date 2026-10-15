// Genera las capturas de docs/assets/img contra la instalación limpia (docs-tools/docker).
// Uso: npm run capture
import { mkdirSync } from 'node:fs';
import { open, login, BASE } from './lib.mjs';

const OUT = new URL('../../docs/assets/img/', import.meta.url).pathname;
mkdirSync(OUT, { recursive: true });

const { browser, page } = await open();
await login(page);

async function shot(name, url, { full = false, before } = {}) {
  await page.goto(`${BASE}/${url.replace(/^\//, '')}`);
  await page.waitForLoadState('networkidle');
  if (before) await before(page);
  await page.waitForTimeout(400);
  await page.screenshot({ path: `${OUT}${name}.png`, fullPage: full });
  console.log('OK', name, '<-', url);
}

async function firstLink(url, pattern) {
  await page.goto(`${BASE}/${url}`);
  await page.waitForLoadState('networkidle');
  const hrefs = await page.$$eval('a', (as) => as.map((a) => a.getAttribute('href') || ''));
  return hrefs.find((h) => new RegExp(pattern).test(h));
}

// Plugins y menú
await shot('plugins', 'AdminPlugins');

// Maestros del plugin
await shot('ncf-rangos', 'ListNCFRango');
await shot('ncf-tipos', 'ListNCFTipo');
await shot('impuestos-adicionales', 'ListImpuestoAdicional');
// Informes fiscales: el período por defecto es el mes anterior; se fuerza "este mes"
async function reportShot(name, tab) {
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
        i.type = 'hidden'; i.name = k; i.value = v;
        f.appendChild(i);
      }
      document.body.appendChild(f);
      f.submit();
    }, tab),
  ]);
  await page.waitForTimeout(400);
  await page.screenshot({ path: `${OUT}${name}.png`, fullPage: false });
  console.log('OK', name);
}
await reportShot('informes-fiscales', 'FiscalReports-consolidated');
await reportShot('informe-606', 'FiscalReport606');
await reportShot('informe-607', 'FiscalReport607');
await reportShot('informe-608', 'FiscalReport608');

// Fichas
await shot('cliente', 'EditCliente?code=CLI0001', { full: true });
await shot('proveedor', 'EditProveedor?code=PRO0001', { full: true });
const prod = await firstLink('ListProducto', 'EditProducto\\?code=');
if (prod) await shot('producto', prod, { full: true });

// Facturas
async function linkByText(url, text) {
  await page.goto(`${BASE}/${url}`);
  await page.waitForLoadState('networkidle');
  return page.locator('tr', { hasText: text }).first().locator('a[href*="?code="]').first().getAttribute('href');
}
const clickTab = (label) => async (p) => {
  await p.locator('a.nav-link', { hasText: label }).first().click();
  await p.waitForLoadState('networkidle');
};

await shot('factura-venta-b01', await linkByText('ListFacturaCliente', 'B0100000001'), { full: true });
await shot('linea-impuestos', await linkByText('ListFacturaCliente', 'B0100000001'), {
  before: async (p) => {
    await p.locator('button:has([data-icon="ellipsis"])').first().click();
    await p.waitForTimeout(800);
  },
});
await shot('factura-venta-ecf31', await linkByText('ListFacturaCliente', 'E310000000001'), { full: true });
await shot('nota-debito-33', await linkByText('ListFacturaCliente', 'E330000000001'), { full: true });
await shot('nota-credito-34', await linkByText('ListFacturaCliente', 'E340000000001'), { full: true });
await shot('devoluciones-venta', await linkByText('ListFacturaCliente', 'E310000000001'), { full: true, before: clickTab('Devoluciones') });
await shot('factura-compra', await linkByText('ListFacturaProveedor', 'B0100000001'), { full: true });

await browser.close();
