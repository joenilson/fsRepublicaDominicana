// Activa el plugin fsRepublicaDominicana en la instalación de documentación.
import { open, login, BASE } from './lib.mjs';

const { browser, page } = await open();
await login(page);
await page.goto(`${BASE}/AdminPlugins`);
await page.waitForLoadState('networkidle');
const row = page.locator('tr', { hasText: 'fsRepublicaDominicana' }).first();
await row.locator('input[type=checkbox][role=switch]').click();
await page.waitForLoadState('networkidle');
await page.waitForTimeout(8000);
await page.goto(`${BASE}/AdminPlugins`);
await page.waitForLoadState('networkidle');
console.log(await page.locator('tr', { hasText: 'fsRepublicaDominicana' }).first().innerText());
await browser.close();
