# Documentos soportados

El plugin agrega los datos fiscales de la DGII a los documentos de compra y de venta de FacturaScripts. Las capturas de
esta página salen de los documentos que genera la [suite de pruebas de lanzamiento](../desarrollo/pruebas-lanzamiento.md)
en cada versión, así que muestran lo que el plugin hace hoy.

## Resumen

| Documento | Compra | Venta | Datos fiscales del plugin |
|-----------|:------:|:-----:|---------------------------|
| Proveedor / cliente | ✅ | ✅ | RNC o cédula, tipo de comprobante por defecto, retención predeterminada |
| Producto | ✅ | ✅ | Servicio o bien (clasificación del 606), impuestos adicionales |
| Pedido | ✅ | ✅ | Sin NCF: no es un documento fiscal |
| Albarán | ✅ | ✅ | Sin NCF: no es un documento fiscal |
| Factura | ✅ | ✅ | NCF o e-CF, tipo de comprobante, tipo de pago y de movimiento |
| Nota de crédito (04 / 34) | ✅ | ✅ | Importe negativo, tope sobre la factura original |
| Nota de débito (03 / 33) | ✅ | ✅ | Importe positivo, factura original |
| Factura anulada | — | ✅ | Tipo de anulación; sale en el 608 y no en el 607 |
| Comprobante electrónico (e-CF) | — | ✅ | Con el plugin FeRepublicaDominicana: XML firmado y código QR |

Tipos de comprobante soportados: 01 crédito fiscal, 02 consumo, 03 nota de débito, 04 nota de crédito, 14 regímenes
especiales y 15 gubernamental, y los e-CF 31 a 47. Ver [tipos de comprobante](../referencia/tipos-comprobante.md).

## Compras

### Proveedor

![Ficha de proveedor](../assets/img/documentos/compra-proveedor.png)

El proveedor guarda su RNC o cédula y la retención que se aplica a sus facturas. Con eso se llena el 606.

### Pedido y albarán de compra

![Pedido de compra](../assets/img/documentos/compra-pedido.png)

El pedido es el primer paso y no lleva NCF. Se convierte en albarán con el botón de estado, y el albarán en factura.

![Albarán de compra](../assets/img/documentos/compra-albaran.png)

### Factura de compra en pesos

![Factura de compra en pesos dominicanos](../assets/img/documentos/compra-factura-dop.png)

Factura con una línea de mercancía y una de servicio, con ITBIS 18 %. El NCF del proveedor se digita en la factura y
el plugin avisa si ya fue registrado. El 606 separa lo facturado en servicios y bienes según el producto.

### Factura de compra en dólares

![Factura de compra en dólares](../assets/img/documentos/compra-factura-usd.png)

En otra divisa la factura se guarda en esa divisa, con su tasa de cambio, y sigue llevando NCF.

### Nota de crédito de compra

![Nota de crédito de compra](../assets/img/documentos/compra-nota-credito.png)

Se crea desde la pestaña **Devoluciones** de la factura. Es una factura rectificativa con tipo 04 (o 34 si es
electrónica) e importes negativos. En el 606 se informa en positivo, con el NCF que modifica.

### Nota de débito de compra

![Nota de débito de compra](../assets/img/documentos/compra-nota-debito.png)

Cargo adicional de un proveedor sobre una factura anterior: tipo 03 (o 33), importe positivo y factura que modifica.

### Compra con impuestos adicionales

![Factura de compra con ISC y CDT](../assets/img/documentos/compra-impuestos-adicionales.png)

Cada línea puede llevar ISC, CDT, propina legal o primera placa. Los montos se calculan con la tasa del catálogo, o se
digitan a mano cuando manda lo que facturó el proveedor. Ver [impuestos adicionales](../configuracion/impuestos.md).

### Compra con retención de ITBIS y de ISR

![Factura de compra con retención de ITBIS 30 %](../assets/img/documentos/compra-retencion-itbis.png)

La retención de ITBIS (30 % o 100 %) se calcula sobre el ITBIS de la factura y se informa en el 606.

![Factura de compra con retención de ISR por honorarios](../assets/img/documentos/compra-retencion-isr.png)

La retención de ISR (códigos 1 a 9) se informa con su tipo en el 606. Ver [retenciones](../configuracion/retenciones.md).

## Ventas

### Clientes por tipo de comprobante

El tipo de comprobante del cliente decide qué NCF se propone en sus facturas.

![Cliente de consumo final](../assets/img/documentos/venta-cliente-consumo.png)

**Consumo final (02):** no requiere RNC.

![Cliente con valor fiscal](../assets/img/documentos/venta-cliente-credito-fiscal.png)

**Con valor fiscal (01):** requiere RNC o cédula; el plugin puede buscarlo en la base de la DGII.

![Cliente de régimen especial](../assets/img/documentos/venta-cliente-regimen-especial.png)

**Regímenes especiales (14):** para clientes exentos, zonas francas y similares.

### Pedido y albarán de venta

![Pedido de venta](../assets/img/documentos/venta-pedido.png)

![Albarán de venta](../assets/img/documentos/venta-albaran.png)

Igual que en compras: no llevan NCF; el NCF se asigna cuando se genera la factura.

### Factura de consumo final (02)

![Factura de consumo final](../assets/img/documentos/venta-factura-consumo.png)

Al aprobar la factura el plugin toma el siguiente NCF B02 del rango activo.

### Factura de crédito fiscal (01)

![Factura de crédito fiscal](../assets/img/documentos/venta-factura-credito-fiscal.png)

Lleva el NCF B01 y se informa en el 607 con el RNC del cliente.

### Factura de regímenes especiales (14)

![Factura de regímenes especiales](../assets/img/documentos/venta-factura-regimen-especial.png)

### Facturas en dólares y en euros

![Factura de consumo final en dólares](../assets/img/documentos/venta-factura-usd.png)

![Factura de consumo final en euros](../assets/img/documentos/venta-factura-eur.png)

Lleva el mismo tipo de NCF que en pesos; la divisa y la tasa de cambio quedan guardadas en la factura.

### Nota de crédito de venta

![Nota de crédito de venta](../assets/img/documentos/venta-nota-credito.png)

Se genera desde **Devoluciones**: tipo 04, NCF B04 y cantidades negativas. El plugin rechaza la nota con importe
positivo o que, sumada a las demás, supere la factura. Ver [notas de crédito y débito](notas.md).

### Nota de débito de venta

![Nota de débito de venta](../assets/img/documentos/venta-nota-debito.png)

Cargo adicional sobre una factura: tipo 03, NCF B03 e importe positivo.

### Factura anulada

![Factura de venta anulada](../assets/img/documentos/venta-factura-anulada.png)

La factura queda en estado **Anulada**. No sale en el 607 y sí en el 608 con su tipo de anulación.

### Venta con impuestos adicionales

![Factura de venta con ISC y propina legal](../assets/img/documentos/venta-impuestos-adicionales.png)

Mercancía con ISC (cigarrillos, específico más *ad valorem*) y servicio con propina legal del 10 %. El campo
**Otros Imp.** del pie suma los impuestos adicionales sin mezclarlos con el ITBIS.

## Comprobantes electrónicos (e-CF)

Con el plugin **FeRepublicaDominicana** las facturas y notas pueden ser electrónicas: tipos 31, 32, 33, 34 y siguientes,
con XML firmado, fecha de firma, código de seguridad y código QR en el PDF.

![Factura de venta e-CF 31](../assets/img/factura-venta-ecf31.png)

![Nota de crédito electrónica 34](../assets/img/nota-credito-34.png)

![Nota de débito electrónica 33](../assets/img/nota-debito-33.png)

Ver [comprobantes electrónicos](ecf.md).
