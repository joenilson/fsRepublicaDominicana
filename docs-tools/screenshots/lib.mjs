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

// Navega con reintentos: un servidor local de pruebas puede cortar la conexión de alguna petición suelta.
export async function gotoRetry(page, url, tries = 6) {
  let lastError;
  for (let attempt = 1; attempt <= tries; attempt++) {
    try {
      const response = await page.goto(url);
      await page.waitForLoadState('networkidle');
      if (response && response.status() < 500) return response;
      lastError = new Error(`HTTP ${response?.status()} en ${url}`);
    } catch (error) {
      lastError = error;
    }
    await page.waitForTimeout(700);
  }
  throw lastError;
}

export async function login(page) {
  // Se inicia sesión con una petición directa y con reintentos: si el servidor corta la conexión a mitad
  // del envío, el token de la página de login ya quedó usado y el navegador mostraría "Petición duplicada".
  const request = page.context().request;
  let lastError;
  for (let attempt = 1; attempt <= 5; attempt++) {
    try {
      const html = await (await request.get(`${BASE}/login`)).text();
      const token = /name="multireqtoken" value="([^"]+)"/.exec(html)?.[1];
      if (!token) throw new Error('No se encontró el token del formulario de login');
      await request.post(`${BASE}/login`, {
        form: { multireqtoken: token, action: 'login', fsNick: USER, fsPassword: PASS },
        maxRedirects: 0,
      });
      await gotoRetry(page, `${BASE}/Dashboard`);
      if (!page.url().includes('/login')) return;
      lastError = new Error('No se pudo iniciar sesión (revise FS_USER y FS_PASS)');
    } catch (error) {
      lastError = error;
    }
    await page.waitForTimeout(500);
  }
  throw lastError;
}
