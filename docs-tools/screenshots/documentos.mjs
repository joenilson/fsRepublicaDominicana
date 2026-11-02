// Capturas de los documentos que genera la suite de pruebas de lanzamiento, para la página
// "Documentos soportados" de la documentación. Sin barra de debug. Los documentos salen de resultados.json.
//
// Uso: FS_URL=http://127.0.0.1:8099 FS_USER=admin FS_PASS=clave node documentos.mjs <carpeta-con-resultados.json>
import { mkdirSync, readFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { open, login, gotoRetry, BASE } from './lib.mjs';

const dir = process.argv[2];
if (!dir) {
  console.error('Uso: node documentos.mjs <carpeta-con-resultados.json>');
  process.exit(2);
}
const out = join(dirname(fileURLToPath(import.meta.url)), '../../docs/assets/img/documentos');
mkdirSync(out, { recursive: true });
const data = JSON.parse(readFileSync(join(dir, 'resultados.json'), 'utf8'));

// [caso, tipo de documento, nombre del archivo]
const SHOTS = [
  ['C01', 'Proveedor', 'compra-proveedor'],
  ['C03', 'PedidoProveedor', 'compra-pedido'],
  ['C04', 'AlbaranProveedor', 'compra-albaran'],
  ['C05', 'FacturaProveedor', 'compra-factura-dop'],
  ['C06', 'FacturaProveedor', 'compra-factura-usd'],
  ['C08', 'FacturaProveedor', 'compra-nota-credito'],
  ['C13', 'FacturaProveedor', 'compra-nota-debito'],
  ['C09', 'FacturaProveedor', 'compra-impuestos-adicionales'],
  ['C10', 'FacturaProveedor', 'compra-retencion-itbis'],
  ['C11', 'FacturaProveedor', 'compra-retencion-isr'],
  ['V01', 'Cliente', 'venta-cliente-consumo'],
  ['V02', 'Cliente', 'venta-cliente-credito-fiscal'],
  ['V03', 'Cliente', 'venta-cliente-regimen-especial'],
  ['V04', 'PedidoCliente', 'venta-pedido'],
  ['V05', 'AlbaranCliente', 'venta-albaran'],
  ['V06', 'FacturaCliente', 'venta-factura-consumo'],
  ['V07', 'FacturaCliente', 'venta-factura-credito-fiscal'],
  ['V08', 'FacturaCliente', 'venta-factura-regimen-especial'],
  ['V09', 'FacturaCliente', 'venta-factura-usd'],
  ['V10', 'FacturaCliente', 'venta-factura-eur'],
  ['V11', 'FacturaCliente', 'venta-nota-credito'],
  ['V12', 'FacturaCliente', 'venta-nota-debito'],
  ['V13', 'FacturaCliente', 'venta-factura-anulada'],
  ['V14', 'FacturaCliente', 'venta-impuestos-adicionales'],
];

const { browser, page } = await open();
await login(page);
await page.addInitScript(() => {
  const style = document.createElement('style');
  style.textContent = '.phpdebugbar, #fs-debug-bar, .debugbar { display: none !important; }';
  document.addEventListener('DOMContentLoaded', () => document.head.appendChild(style));
});

let failed = 0;
for (const [id, tipo, name] of SHOTS) {
  const doc = data.pasos.find((s) => s.id === id)?.docs.filter((d) => d.tipo === tipo).pop();
  if (!doc) {
    console.error(`Sin documento ${tipo} en el caso ${id}`);
    failed++;
    continue;
  }
  try {
    await gotoRetry(page, `${BASE}/${doc.controlador}?code=${doc.id}`);
    if (page.url().includes('/login')) throw new Error('sesión perdida');
    await page.waitForTimeout(300);
    await page.screenshot({ path: join(out, `${name}.png`), fullPage: true });
    console.log(`${name}.png <- ${doc.codigo}`);
  } catch (error) {
    console.error(`${name}: ${error.message}`);
    failed++;
  }
}
await browser.close();
process.exit(failed ? 1 : 0);
