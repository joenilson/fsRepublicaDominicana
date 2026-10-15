# JavaScript del plugin

El JavaScript de las pantallas de documentos vive en `Assets/JS/` y se divide por responsabilidad. Todo cuelga del
espacio de nombres `window.fsRD`; solo las funciones que PHP o Twig llaman desde atributos `onclick`/`onchange`
siguen siendo globales.

## Archivos

| Archivo | Responsabilidad | Globales que expone |
|---------|-----------------|---------------------|
| `fsrd-core.js` | Espacio de nombres, textos, `fsRD.post()`, escape de HTML, fechas y detección de venta o compra. | `logConsole` (compatibilidad) |
| `fsrd-ncf.js` | Verificación de correlativo, verificación del NCF del proveedor y cambio de cliente. | `verificarCorrelativoNCF`, `purchasesNCFVerify` |
| `fsrd-ecf.js` | Lectura del XML del e-CF, vista previa en modal y copia al formulario. | `btnParseClick`, `enableParseIfReady`, `useEcfXMLData` |
| `fsrd-taxes.js` | Campos de nota de débito, selector con búsqueda del ISC y monto manual de ISC. | `toggleNotaDebitoFields` |
| `CommonModals.js` | Modales Bootstrap genéricos. | `executeModal` y auxiliares |
| `BusquedaRNCDGII.js` | Consulta de RNC en clientes y proveedores. | `btnVerificarRNC`, `usarInformacionDGII` |

## Cómo se cargan

El controlador limpia el `AssetManager` al crearse, por lo que registrar archivos en `Init::init()` no funciona.
Los assets de los documentos se piden en `assets()` de `SalesFooterHTMLMod` y `PurchasesFooterHTMLMod`, que llaman a
`Lib/DocumentAssets::load()`. Los de cliente y proveedor se agregan en `createViews()` de sus extensiones.

## Convenciones

- Las respuestas AJAX pasan por `fsRD.post(operacion, accion, datos)`: devuelve el JSON o `null` si la respuesta
  está vacía o no es válida, y avisa por modal en vez de `alert()`.
- **Todo texto que venga de un XML o de un servicio externo se escapa** con `fsRD.escapeHtml()` antes de armarlo
  como HTML.
- Los eventos de campos que el núcleo vuelve a dibujar (como el pie de la factura) se enlazan por delegación en
  `document`.
- Los tipos de comprobante que emite la propia empresa en compras se definen una sola vez en PHP
  (`NCFTipo::EMISION_PROPIA_COMPRAS`) y llegan al JavaScript en `data-own-types`.
- Los textos están en `fsRD.messages`; se pueden reemplazar definiendo `window.fsRDMessages` antes de cargar
  `fsrd-core.js`.

## Revisar y probar

```bash
npx eslint@9 Assets/JS          # estilo y errores comunes (eslint.config.mjs)

cd docs-tools/screenshots
npm run smoke                   # pruebas de humo con Playwright
```

Las pruebas de humo usan el [entorno limpio con Docker](documentacion.md): cambio de tipo de comprobante en
ventas y compras, verificación de NCF, notas de débito, carga de XML (incluido un XML con HTML malicioso), formulario
de devoluciones y modal de ISC.
