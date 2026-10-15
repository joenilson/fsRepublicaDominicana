// Paso 1: asistente de instalación web de FacturaScripts (idioma es_DO, base de datos del compose).
import { open, BASE } from './lib.mjs';

const { browser, page } = await open();
await page.goto(`${BASE}/`);
await page.waitForLoadState('networkidle');
if (page.url().includes('login')) {
  console.log('FacturaScripts ya está instalado');
  await browser.close();
  process.exit(0);
}
await page.selectOption('#fs_lang', 'es_DO');
await page.waitForLoadState('networkidle');
await page.fill("input[name='fs_initial_user']", 'admin');
await page.fill("input[name='fs_initial_pass']", 'admin');
await page.fill("input[name='fs_db_user']", 'fsdocs');
await page.fill("input[name='fs_db_pass']", 'fsdocs');
await page.click("button[type='submit']");
await page.waitForSelector('text=Iniciar sesión', { timeout: 180000 });
console.log('Instalación completada');
await browser.close();
