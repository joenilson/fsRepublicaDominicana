import { chromium } from 'playwright';

export const BASE = process.env.FS_URL ?? 'http://localhost:8089';
export const USER = process.env.FS_USER ?? 'admin';
export const PASS = process.env.FS_PASS ?? 'admin';

export async function open() {
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport: { width: 1366, height: 850 }, locale: 'es-DO' });
  const page = await context.newPage();
  return { browser, page };
}

export async function login(page) {
  await page.goto(`${BASE}/login`);
  await page.fill('input[name=fsNick]', USER);
  await page.fill('input[name=fsPassword]', PASS);
  await page.click('button[type=submit]');
  await page.waitForLoadState('networkidle');
}
