# Facturas de compra

En la factura de proveedor el NCF **lo entrega el proveedor**, así que se escribe a mano y el plugin ofrece
una verificación contra duplicados.

![Factura de compra](../assets/img/factura-compra.png)

## Campos fiscales

| Campo | Descripción |
|-------|-------------|
| **NCF del proveedor** | Número del comprobante recibido. Tiene un botón de búsqueda que verifica si ya fue registrado. |
| **Tipo de comprobante** | Solo aparecen los tipos marcados para compras en el [catálogo](../referencia/tipos-comprobante.md). |
| **Fecha de vencimiento** | Vigencia del NCF del proveedor. |
| **Tipo de pago** | Forma de pago pactada. Por defecto *Efectivo*. |
| **Tipo de movimiento** | Clasificación del gasto (01–11, por defecto *Compras y gastos que formarán parte del costo de venta*). |
| **Tipo de anulación** | Motivo, para comprobantes anulados. |

## Verificar un NCF antes de guardar

Escriba el NCF, elija el proveedor y pulse el botón de lupa junto al campo NCF.

- Si el NCF **no existe** para ese proveedor, el botón se pone verde y el plugin completa el *Tipo de comprobante*
  con los dígitos 2 y 3 del NCF. No propone fecha de vencimiento ni de firma: vienen del XML o las digita usted.
- Si ya fue registrado, el botón se pone rojo y un aviso indica la factura y la fecha donde ya existe.

## Comprobantes que emite su empresa

Los comprobantes **11** (compras), **13** (gastos menores), **41** y **43** (equivalentes electrónicos) los emite
su propia empresa al comprar a quien no entrega comprobante. Para esos el plugin **genera el NCF** desde sus
[rangos](../configuracion/rangos.md), igual que en ventas.

## Reportes

Los datos de la factura alimentan el [reporte 606](../reportes/fiscales.md): separa servicios y bienes según el
campo *Es servicio* del producto, y reporta el ITBIS, ISC y otros impuestos de las líneas.

!!! note "Unicidad"
    El plugin **no** impone un índice único de NCF en facturas de proveedor, porque dos proveedores distintos pueden
    entregar el mismo número. El control contra duplicados es la verificación del botón de lupa, que busca el NCF
    para el mismo proveedor.
