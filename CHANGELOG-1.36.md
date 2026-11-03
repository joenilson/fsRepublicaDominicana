# fsRepublicaDominicana 1.36

Localización contable y fiscal para República Dominicana. Esta versión se centra en el cumplimiento con la DGII: impuestos selectivos por monto, retenciones, notas de crédito y débito con validación, reportes 606/607/608 corregidos y el timbre QR del e-CF.

**Requisitos:** FacturaScripts 2026 o superior (antes 2025). PHP 8.2 o superior (el QR usa `chillerlan/php-qrcode`).

> **Importante al actualizar:** reconstruya el sistema desde Administrador → Plugins → *Reconstruir*. Esta versión agrega tablas y columnas en tablas del núcleo, y las traducciones nuevas no aparecen sin ese paso.

---

## Novedades

### Timbre QR para comprobantes electrónicos (e-CF)
- El PDF de las facturas e-CF muestra el código QR de la DGII, el código de seguridad y la fecha de firma.
- Soporta los ambientes de pruebas (`testecf`), certificación (`certecf`) y producción.
- Cuando el comprador no tiene RNC (tipos 43, 46 y 47) se omite el parámetro `RncComprador` de la URL, porque con el parámetro vacío la consulta de la DGII no encontraba el comprobante.

### Impuestos adicionales por monto (ISC, CDT, propina legal)
- Los impuestos adicionales ya no se calculan solo por porcentaje: ahora también por **monto por unidad** y por **litro de alcohol absoluto**, con **AdValorem** opcional (con o sin el ISC específico en su base).
- Se instalan los códigos **006 a 022** (alcohol y cigarrillos).
- Productos: nuevos campos de grados de alcohol y contenido en litros.
- Las líneas guardan los montos calculados (ISC específico y AdValorem, CDT, propina legal y primera placa). Las líneas anteriores se siguen calculando como porcentaje.
- El selector de impuestos adicionales en las líneas tiene búsqueda e indica si es monto o porcentaje.
- **Compras:** el monto del ISC de cada línea se puede digitar a mano, porque en compras manda lo que facturó el proveedor.
- Los reportes 606 y 607 incluyen ISC, otros impuestos y propina legal.

### Retenciones de ISR e ITBIS
- El catálogo de retenciones distingue el tipo (ISR o ITBIS) y el código ISR 1 a 9 del 606.
- `RETEN30` y `RETEN100` se calculan como porcentaje del ITBIS facturado.
- Se guarda el ITBIS retenido, la renta retenida y el tipo de retención por factura.
- El PDF muestra la retención y la descuenta del total.
- Los reportes y el TXT del 606 y del 607 llenan ITBIS retenido, renta retenida y tipo de retención.

### Notas de crédito y de débito
- **Código de modificación y factura a modificar** en las notas de débito (tipo 33), con los códigos 1 a 5 de la DGII. Aplica a ventas y compras.
- En devoluciones solo se ofrecen notas de crédito (04 y 34). Las notas de débito se generan creando una nueva factura.
- El PDF imprime el NCF modificado y el código de modificación.
- **Las notas de reembolso toman el tipo 04** (34 si la factura original es electrónica) en lugar de consumir un NCF B01.
- **Validaciones al guardar:**
  - La nota de crédito (04, 34) debe restar. Se rechaza con importe positivo.
  - La nota de débito (03, 33) debe sumar. Se rechaza con importe negativo.
  - La suma de las notas de crédito vigentes de una factura no puede superar el total de esa factura.
  - La factura que se modifica debe estar vigente y ser del mismo cliente o proveedor, de la misma empresa y de la misma divisa.
  - Un documento negativo que rectifica una factura debe ser nota de crédito.

---

## Correcciones

### Reportes 606, 607 y 608
- **607:** las facturas anuladas ya no salen con sus montos en el reporte ni en el TXT. Van en el 608.
- **606:** las notas de crédito de compra se informan en positivo. El signo depende del importe y no del estado del documento.
- **TXT 606:** ahora tiene los **23 campos oficiales** del Formato 606. El campo 14 (ITBIS llevado al costo) va vacío y el campo 15 lleva todo el ITBIS por adelantar.
- **TXT 607:** los importes por forma de pago de las notas salen en positivo.
- **Filtro de período:** corregido el error 500 al filtrar por período en los informes fiscales (606, 607, 608 y consolidado).
- **TXT del 607:** ya no escribe un aviso de PHP dentro del archivo cuando la factura no modifica ningún NCF.

### Impuestos adicionales
- Una línea con monto cero ya no se recalcula como porcentaje. Antes, un producto sin grados de alcohol podía generar un ISC de 632.58 % sobre la base.
- Las actualizaciones ya no borran los códigos 006 a 022.

### Documentos y pantallas
- Los botones de carga de XML en compras y devoluciones funcionan de nuevo.
- Se escapan el código del impuesto y el id de línea en el HTML de las líneas.
- Se escapa el texto que llega del XML y del servicio de RNC (corrige un XSS almacenado).
- En compras, la verificación del correlativo usa el formulario correcto.
- La fecha de vencimiento del NCF sale de la fecha de vencimiento de la secuencia del XML, y una fecha digitada por el contador no se sobrescribe.
- Ya no se inventa la fecha de firma al verificar el NCF de un proveedor.
- Se evita pasar valores nulos al tipo de comprobante en compras sin tipo.

---

## Cambios internos
- Migrado `DataBaseWhere` a `Core\Where`. Los informes fiscales extienden `Template\JoinModel`.
- JavaScript reorganizado en módulos (`fsrd-core`, `fsrd-ncf`, `fsrd-ecf`, `fsrd-taxes`) bajo el espacio de nombres `fsRD`. Se eliminó el código sin uso.
- El plugin carga el autoload de `vendor` al iniciar.
- Traducciones sincronizadas en los 24 idiomas.
- Suite de pruebas de lanzamiento con 37 casos y evidencias (capturas, PDF e informe): compras, ventas, ISC, retenciones, notas, divisas y reportes DGII.

## Obsoleto
- Las acciones AJAX `busca_tipo`, `busca_pago`, `busca_movimiento` y `busca_tipoanulacion` quedan obsoletas y se eliminarán en la 1.38.

## Documentación
Nueva documentación en español e inglés, con una página de documentos soportados y capturas de cada documento.

---

## Pendiente de validar con su contador
- Las fórmulas del ISC específico están parametrizadas y pendientes de validar con la Ley 253-12 y la descripción técnica del e-CF de la DGII.
- El campo 14 del 606 (ITBIS llevado al costo) se deja vacío. Si su contador lleva ITBIS al costo o a proporcionalidad, hay que informarlo aparte.
- Los códigos ISR 1 a 9 del 606 vienen de fuente secundaria. Confírmelos con el instructivo vigente de la DGII.
- Las retenciones admiten una sola por documento. En el 607 la fecha de retención es la fecha de la factura, y las facturas anteriores no tienen desglose.
- Las notas guardadas antes de las validaciones que no las cumplan no se podrán volver a guardar.
