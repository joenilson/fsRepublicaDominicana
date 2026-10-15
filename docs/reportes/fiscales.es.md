# Reportes fiscales

**Informes → República Dominicana → Informes Fiscales** reúne los reportes que se presentan a la DGII.

![Reportes fiscales](../assets/img/informes-fiscales.png)

| Pestaña | Contenido |
|---------|-----------|
| **Reporte Consolidado** | Todas las facturas de venta del período, con ITBIS, tipo de NCF, movimiento, pago y anulación. Sirve para cuadrar. |
| **Reporte 606** | Compras de bienes y servicios (facturas de proveedor). |
| **Reporte 607** | Ventas de bienes y servicios (facturas de cliente). |
| **Reporte 608** | Comprobantes anulados. |

## Filtrar por período

!!! warning "El período por defecto es el mes anterior"
    Al abrir la página se muestra el **mes anterior**, que es el que normalmente se declara. Para ver el mes en curso
    cambie el selector de período a **Este mes** o indique las fechas inicial y final.

## Reporte 607 (ventas)

![Reporte 607 con facturas y notas del período](../assets/img/informe-607.png)

Por cada factura del período muestra:

- RNC o cédula del cliente y tipo de identificación;
- NCF y, en las notas de crédito y débito, el **NCF modificado**;
- tipo de movimiento (ingreso) y fecha;
- importe, ITBIS, **ISC**, otros impuestos y propina legal;
- desglose por forma de pago: efectivo, cheque/transferencia, tarjeta, crédito, bonos, permuta y otros;
- estado (*Activo* o *Anulado*).

## Reporte 606 (compras)

![Reporte 606](../assets/img/informe-606.png)

Muestra por factura de proveedor: RNC, tipo de gasto (tipo de movimiento), NCF y NCF modificado, fechas de
comprobante y pago, **monto en servicios y en bienes** (según *Es servicio* del producto), ITBIS facturado, ISC,
otros impuestos y propina legal.

## Reporte 608 (anulados)

![Reporte 608](../assets/img/informe-608.png)

Lista los NCF anulados con el **tipo de anulación** y la fecha. Para que una factura figure aquí, cámbiela al estado
**Anulada** y registre el *Tipo de Anulación*.

## Exportar para la DGII

En cada pestaña (606, 607 o 608), use el botón de impresión/exportación y elija **TXT DGII**. El plugin genera el
archivo con el nombre:

```text
DGII_F_<reporte>_<RNC de la empresa>_<año>_<mes>.TXT
```

La primera línea es el encabezado: `reporte|RNC|AAAAMM|cantidad de registros`, seguida de un registro por línea con
campos separados por `|`.

!!! warning "Valide antes de presentar"
    Compare siempre el TXT con las plantillas oficiales de la DGII antes de cargarlo. Están pendientes de verificar:
    las columnas de ISC, otros impuestos y propina legal en el TXT, la fecha de pago del 606 y el signo de las notas
    de crédito electrónicas (34). Vea [Limitaciones](../referencia/limitaciones.md).
