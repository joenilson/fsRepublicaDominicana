# Plugin JavaScript

The JavaScript for the document screens lives in `Assets/JS/` and is split by responsibility. Everything hangs from
the `window.fsRD` namespace; only functions that PHP or Twig call from `onclick`/`onchange` attributes remain
global.

## Files

| File | Responsibility | Globals exposed |
|------|----------------|-----------------|
| `fsrd-core.js` | Namespace, messages, `fsRD.post()`, HTML escaping, dates and sales/purchase detection. | `logConsole` (compatibility) |
| `fsrd-ncf.js` | Sequence check, supplier NCF check and customer change. | `verificarCorrelativoNCF`, `purchasesNCFVerify` |
| `fsrd-ecf.js` | e-CF XML reading, preview modal and copy to the form. | `btnParseClick`, `enableParseIfReady`, `useEcfXMLData` |
| `fsrd-taxes.js` | Debit note fields, searchable ISC selector and manual ISC amount. | `toggleNotaDebitoFields` |
| `CommonModals.js` | Generic Bootstrap modals. | `executeModal` and helpers |
| `BusquedaRNCDGII.js` | RNC lookup on customers and suppliers. | `btnVerificarRNC`, `usarInformacionDGII` |

## How they are loaded

The controller clears the `AssetManager` when created, so registering files in `Init::init()` does not work.
Document assets are requested in `assets()` of `SalesFooterHTMLMod` and `PurchasesFooterHTMLMod`, which call
`Lib/DocumentAssets::load()`. Customer and supplier assets are added in `createViews()` of their extensions.

## Conventions

- AJAX responses go through `fsRD.post(operation, action, data)`: it returns the JSON or `null` when the response is
  empty or invalid, and warns through a modal instead of `alert()`.
- **Any text coming from an XML or an external service is escaped** with `fsRD.escapeHtml()` before being built
  into HTML.
- Events on fields the core redraws (such as the invoice footer) are bound by delegation on `document`.
- Receipt types your own company issues on purchases are defined once in PHP
  (`NCFTipo::EMISION_PROPIA_COMPRAS`) and reach the JavaScript in `data-own-types`.
- Messages live in `fsRD.messages`; they can be replaced by defining `window.fsRDMessages` before loading
  `fsrd-core.js`.

## Linting and testing

```bash
npx eslint@9 Assets/JS          # style and common errors (eslint.config.mjs)

cd docs-tools/screenshots
npm run smoke                   # Playwright smoke tests
```

The smoke tests use the [clean Docker environment](documentacion.md): receipt type change in sales and purchases,
NCF verification, debit notes, XML loading (including an XML with malicious HTML), the returns form and the ISC
modal.
