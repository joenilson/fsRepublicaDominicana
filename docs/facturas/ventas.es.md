# Facturas de venta

La factura de cliente de FacturaScripts incorpora un bloque de campos fiscales debajo de las observaciones.

![Factura de venta con crédito fiscal](../assets/img/factura-venta-b01.png)

## Campos fiscales

| Campo | Descripción |
|-------|-------------|
| **NCF** | Número del comprobante. Déjelo vacío para que el plugin lo genere al guardar; si lo escribe, se respeta. |
| **Tipo de comprobante** | Se propone el tipo configurado en el cliente. Al cambiarlo se verifica que exista correlativo disponible. |
| **Fecha de vencimiento** | Vigencia del NCF; se propone la del rango si el campo está vacío. Una fecha digitada o ya guardada no se sobrescribe. |
| **Tipo de pago** | Forma de pago de la DGII (efectivo, cheque/transferencia, tarjeta, crédito...). Alimenta el 607. |
| **Tipo de movimiento** | Clasificación del ingreso (por defecto *Ingresos por operaciones*). |
| **Tipo de anulación** | Motivo, solo cuando el comprobante se anula. |
| **Fecha de firma / Código de seguridad** | Datos del e-CF. Ver [comprobantes electrónicos](ecf.md). |
| **Exento / Otros Imp. / Total + Imp** | Total exento de ITBIS, total de impuestos adicionales y total con ellos. Son de solo lectura. |

## Flujo normal

1. Cree la factura y elija el cliente. El tipo de comprobante y el tipo de pago se completan desde su ficha.
2. Agregue las líneas. Si el producto lleva [ISC](../configuracion/impuestos.md) elíjalo en el modal de la línea.
3. Revise el **Tipo de comprobante** y guarde.
4. Al guardar, el plugin asigna el NCF del siguiente correlativo del [rango](../configuracion/rangos.md) activo y
   copia al documento la fecha de vencimiento del rango.

!!! info "Facturas ya emitidas"
    Con la factura cerrada, los campos fiscales quedan de solo lectura. Para corregirla use una
    [nota de crédito o débito](notas.md).

## Unicidad del NCF

No puede haber dos facturas de venta de la misma empresa con el mismo NCF. Si el rango estaba mal configurado y
repite un número, el guardado falla con el error de duplicado.

## Impresión

El PDF de la factura imprime el tipo de comprobante, el NCF y la fecha de vencimiento. Para e-CF también imprime
el código QR, el código de seguridad y la fecha de firma, y para notas imprime el NCF modificado y el código de
modificación.
