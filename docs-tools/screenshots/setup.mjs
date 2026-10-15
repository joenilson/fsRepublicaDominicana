// Completa el asistente de FacturaScripts con datos sintéticos (ninguno es real).
import { open, login } from './lib.mjs';

const { browser, page } = await open();
await login(page);

if (await page.locator('input[name=direccion]').count()) {
  await page.fill('input[name=empresa]', 'Empresa Demo SRL');
  await page.fill('input[name=direccion]', 'Calle Ficticia 123');
  await page.fill('input[name=ciudad]', 'Santo Domingo');
  await page.fill('input[name=provincia]', 'Distrito Nacional');
  await page.selectOption('select[name=codpais]', 'DOM');
  if (await page.locator('input[name=email]').count()) await page.fill('input[name=email]', 'demo@example.com');
  await page.fill('input[name=telefono1]', '8095550100');
  await page.click('button:has-text("Continuar")');
  await page.waitForLoadState('networkidle');
}
if (await page.locator('input[name=cifnif]').count()) {
  await page.fill('input[name=cifnif]', '101000000');
  await page.click('button:has-text("Continuar")');
  await page.waitForLoadState('networkidle');
}
console.log(page.url(), '|', await page.title());
console.log((await page.innerText('body')).slice(0, 600));
await browser.close();
