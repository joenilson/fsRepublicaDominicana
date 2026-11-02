# Pruebas de lanzamiento

Antes de publicar una versión nueva del plugin se ejecuta la suite de `Test/release/`. Recorre el flujo completo de
compras, ventas y reportes de la DGII, y deja evidencias (capturas, PDF, archivos TXT e informe) para adjuntar al
release.

!!! danger "Use una instalación de pruebas"
    La suite **escribe datos reales** (proveedores, clientes, productos y facturas con un prefijo propio) en la base de
    datos de la instalación indicada. Nunca la apunte a producción. Por eso `run.sh` exige
    `RELEASE_TESTS_CONFIRM=yes`.

## Casos

| Caso | Prueba | Se verifica |
|------|--------|-------------|
| C01 | Creación de proveedores | Proveedor con RNC y proveedor con cédula |
| C02 | Creación de artículos | Mercancía (con stock) y servicio (sin stock) con ITBIS 18 % |
| C03 | Pedido de compra | Neto 10,000.00 y total 11,800.00 |
| C04 | Albarán de compra | Se genera desde el pedido y hereda el total |
| C05 | Factura de compra en pesos | NCF del proveedor (B01), total 11,800.00 |
| C06 | Factura de compra en dólares | Divisa USD, total 236.00 |
| C07 | Pago de la factura de compra | Recibos pagados y factura marcada como pagada |
| C08 | Nota de crédito de compra | Total negativo y referencia a la factura original |
| V01 | Cliente consumidor final | Tipo de comprobante por defecto 02 |
| V02 | Cliente con valor fiscal | Tipo 01 |
| V03 | Cliente de regímenes especiales | Tipo 14 |
| V04 | Pedido de venta | Total 8,850.00 |
| V05 | Albarán de venta | Se genera desde el pedido |
| V06 | Factura de consumidor final | NCF B02 asignado y factura emitida |
| V07 | Factura de crédito fiscal | NCF B01 |
| V08 | Factura de regímenes especiales | NCF B14 |
| V09 | Consumidor final en dólares | Divisa USD y NCF B02 |
| V10 | Consumidor final en euros | Divisa EUR y NCF B02 |
| V11 | Nota de crédito de venta | NCF B04, total negativo, referencia a la factura |
| V12 | Nota de débito de venta | NCF B03, total positivo, referencia a la factura |
| V13 | Anulación de factura con valor fiscal | Estado «Anulada» |
| R01 | Reportes 606, 607 y 608 | Las facturas aparecen en su reporte, la anulada solo en el 608 y los TXT se generan |

Cada caso que depende de otro se marca **omitido** si el anterior falló, para no llenar el informe de errores en
cadena. Los totales esperados salen de los precios y cantidades fijos de la suite, así que un cambio en el cálculo de
impuestos o de divisas rompe el caso correspondiente.

## Ejecución

```bash
RELEASE_TESTS_CONFIRM=yes \
FS_FOLDER=/ruta/a/facturascripts \
FS_URL=http://localhost/facturascripts \
FS_USER=admin FS_PASS=clave \
Test/release/run.sh
```

Requisitos de la instalación:

- El plugin activo y el Dinamic reconstruido con la versión que se va a publicar.
- Rangos de NCF para los tipos 01, 02, 03, 04 y 14 (la suite crea los que falten con la serie B).
- Un usuario administrador y la divisa DOP, USD y EUR cargadas.
- PHP de línea de comandos (el mismo que usa FacturaScripts) y Node.js para las capturas.

`run.sh` hace tres cosas:

1. `Test/release/run.php` ejecuta los casos con los modelos del plugin y escribe `resultados.json` y los TXT de los
   reportes.
2. `docs-tools/screenshots/release-evidence.mjs` abre cada documento creado en el navegador, guarda la captura y el PDF de
   las facturas, captura los reportes 606, 607 y 608 del mes y escribe `informe.md`.
3. Empaqueta todo en un `.zip`.

El código de salida es distinto de cero si algún caso falla o si falta alguna evidencia.

## Evidencias

Quedan en `Test/release/evidencias/v<versión>-<fecha>/` (esa carpeta no se versiona):

| Archivo | Contenido |
|---------|-----------|
| `informe.md` | Resumen por caso, comprobaciones, documentos creados y capturas incrustadas |
| `resultados.json` | Resultado estructurado, para comparar entre versiones |
| `capturas/` | Una captura por documento y por reporte |
| `pdf/` | PDF de cada factura de compra y de venta |
| `DGII_F_606_*.TXT`, `DGII_F_607_*.TXT`, `DGII_F_608_*.TXT` | Archivos que se enviarían a la DGII |

Adjunte el `.zip` al release de GitHub.

## Qué hacer con una falla

1. Abra `informe.md`: cada comprobación fallida muestra lo esperado y lo obtenido, y los mensajes de error de
   FacturaScripts que se registraron durante el caso.
2. Corrija la causa y vuelva a ejecutar la suite completa; los datos de la ejecución anterior no estorban porque cada
   corrida usa un prefijo nuevo.
3. No publique mientras haya casos en falla; si una falla es un comportamiento conocido, anótela en
   [Limitaciones](../referencia/limitaciones.md) y ajuste la comprobación en `run.php` en el mismo commit.

## Notas

- En PostgreSQL, FacturaScripts rellena los valores por defecto de las columnas con la forma `'17'::character varying`.
  La suite asigna el tipo de pago al crear clientes y proveedores para no depender de ese valor.
- La nota de crédito de venta se crea como lo hace la acción de reembolso del núcleo (sin tipo) y el caso V11 exige que
  el plugin le asigne el 04. En compras, el NCF de la nota lo digita el usuario en el formulario (`numeroncfr`).
- Las facturas se emiten (venta «Emitida», compra «Recibida») antes de revisar los reportes, como hace un usuario;
  en «Boceto» los reportes las muestran como anuladas.
