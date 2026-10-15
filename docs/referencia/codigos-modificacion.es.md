# Códigos de modificación

Indican **qué corrige** una nota de crédito o débito, o el contexto de ciertos comprobantes. Se guardan en la tabla
`rd_ncfmodificacion` y en el campo `ncfcodigomodificacion` de las facturas de venta y de compra.

| Código | Descripción | Aplica a |
|:------:|-------------|----------|
| 1 | Anula el NCF modificado | Notas de crédito/débito electrónicas (33, 34) |
| 2 | Corrige texto del comprobante fiscal modificado | Notas de crédito/débito electrónicas (33, 34) |
| 3 | Corrige montos del NCF modificado | Notas de crédito/débito electrónicas (33, 34) |
| 4 | Reemplazo de NCF emitido en contingencia | Factura de crédito fiscal electrónica (31) |
| 5 | Referencia a factura de consumo electrónica | Factura de consumo electrónica (32) |

## Dónde se elige

- **Notas de crédito**: formulario *Nueva devolución* de la pestaña [Devoluciones](../facturas/notas.md).
- **Notas de débito (33)**: junto a *Factura a Modificar* en la factura nueva.

## Reglas

- El plugin **solo acepta** el código que corresponde al tipo de comprobante; cualquier otro valor se descarta al
  guardar.
- Para los tipos 31 y 32 los códigos 4 y 5 existen en el catálogo, pero hoy no hay un selector en la factura que
  permita elegirlos desde la pantalla.
- Si el tipo no es una nota (o el 31/32 con su código), el campo queda vacío.
- El código elegido se imprime en el PDF junto con el NCF modificado.
