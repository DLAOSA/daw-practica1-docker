const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');

(async () => {
  fs.mkdirSync('artifacts', { recursive: true });
  const offline = process.argv.includes('--offline');
  const browser = await chromium.launch();
  try {
    const page = await browser.newPage({ viewport: { width: 1120, height: 900 } });
    const response = await page.goto('http://127.0.0.1:8080/');
    assert.equal(response.status(), offline ? 503 : 200);
    const content = await page.locator('main').innerText();
    assert.match(content, /Versión de PHP\s+8\.3\./);
    assert.doesNotMatch(await response.text(), /<\?php|practica_local|root_local|Fatal error/);

    if (offline) {
      assert.match(content, /No se ha podido conectar con MySQL/);
      assert.doesNotMatch(content, /Conexión correcta/);
      await page.screenshot({ path: 'artifacts/error-conexion.png', fullPage: true });
      console.log('OK: MySQL detenido devuelve HTTP 503 y un mensaje controlado.');
    } else {
      assert.match(content, /Conexión correcta con MySQL mediante PDO/);
      assert.match(content, /Base de datos\s+practica1/);
      assert.match(content, /Versión de MySQL\s+8\.4\./);
      assert.match(content, /\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/);
      await page.screenshot({ path: 'artifacts/resultado.png', fullPage: true });
      await page.setViewportSize({ width: 375, height: 812 });
      assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
      await page.screenshot({ path: 'artifacts/resultado-movil.png', fullPage: true });
      const missing = await page.request.get('http://127.0.0.1:8080/no-existe.php');
      assert.equal(missing.status(), 404);
      console.log('OK: HTTP 200, PHP 8.3, consulta real a MySQL 8.4, móvil y PHP inexistente con 404.');
    }
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
