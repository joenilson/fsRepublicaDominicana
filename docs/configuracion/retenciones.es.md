# Retenciones (ISR e ITBIS)

FacturaScripts maneja las retenciones con un único campo, que internamente se llama **IRPF** (el impuesto español
sobre la renta). En República Dominicana ese concepto no existe: la DGII habla de **retenciones** de ISR
(Impuesto Sobre la Renta) y de ITBIS. Por eso el plugin cambia los textos a la nomenclatura de la DGII y calcula
cada retención según su norma.

## Cómo se calcula cada tipo

| Tipo | El porcentaje se aplica sobre | Ejemplo (servicio de 10,000.00 con ITBIS 18 %) |
|------|-------------------------------|-----------------------------------------------|
| **ISR** | El **neto** de la línea | 10 % → 10,000.00 × 10 % = **1,000.00** |
| **ITBIS** | El **ITBIS facturado** de la línea | 30 % → 1,800.00 × 30 % = **540.00**; 100 % → **1,800.00** |

Internamente FacturaScripts solo sabe aplicar un porcentaje sobre el neto. Para las retenciones de tipo ITBIS el
plugin lo convierte con el ITBIS de la línea (30 % del ITBIS al 18 % es el 5.4 % del neto) y por eso el importe es
correcto también con líneas de ITBIS al 16 % o exentas.

### Retenciones de ITBIS (guía de la DGII)

| Porcentaje | Cuándo se retiene | Norma |
|------------|-------------------|-------|
| 30 % del ITBIS | Sociedades que pagan servicios profesionales liberales o alquiler de bienes muebles a otras sociedades | 02-05 |
| 100 % del ITBIS | Personas jurídicas que pagan servicios gravados a entidades no lucrativas | 01-11 |
| 100 % del ITBIS | Sociedades que pagan servicios de seguridad o vigilancia a otras sociedades | 07-09 |
| 100 % del ITBIS | Compras a personas físicas no registradas como contribuyentes (comprobante de compras) | 05-19 |
| 30 % o 100 % | Sector construcción: 30 % si el servicio lo presta una persona jurídica y 100 % si es persona física | 07-07 |

El catálogo trae las retenciones `RETEN30` y `RETEN100`, que el plugin marca como **ITBIS**.

### Retenciones de ISR

El plugin agrega estas retenciones de ISR, con el **código del formato 606** (*Tipo de Retención en ISR*):

| Código | Descripción | % |
|--------|-------------|---|
| `RETISR1` | Alquileres (código 1) | 10 |
| `RETISR2` | Honorarios por servicios (código 2) | 10 |
| `RETISR3` | Otras rentas (código 3) | 10 |
| `RETISR5` | Intereses a personas jurídicas (código 5) | 1 |
| `RETISR6` | Intereses a personas físicas (código 6) | 1 |
| `RETISR7` | Proveedores del Estado (código 7) | 5 |
| `RETISR8` | Juegos telefónicos (código 8) | 5 |
| `RETISR9` | Ganadería de carne bovina (código 9) | 1 |

Los porcentajes salen de la guía *Retenciones de ISR e ITBIS* de la DGII (julio 2025). Hay otras retenciones de ISR
(por ejemplo 2 % por servicios técnicos o 27 % de remesas al exterior) que puede crear usted mismo.

!!! note "Quién debe retener"
    Según la DGII, solo las personas jurídicas, los negocios de único dueño y las entidades públicas actúan como
    agentes de retención. Una persona física que no sea negocio de único dueño no retiene el ISR.

## Crear una retención

1. Abra **Contabilidad → Retenciones** (o edite una existente).
2. Indique **código**, **descripción** y **porcentaje**.
3. En **Impuesto retenido** elija **ISR** o **ITBIS**.
4. Si es ISR, elija el **Tipo de retención en ISR** del formato 606.

## Aplicarla en un documento

1. Asigne la retención como **retención predeterminada** del cliente o del proveedor.
2. Los documentos nuevos de ese cliente o proveedor toman la retención en cada línea.
3. El importe retenido se descuenta del total, aparece en el pie y en el **PDF** (fila *Retención*) y se contabiliza
   en la subcuenta de retenciones. Si falta la subcuenta o la retención no existe, FacturaScripts avisa al contabilizar.

Ejemplo, servicio de 10,000.00 con ITBIS 18 % y retención `RETEN30`:

```text
Neto              = 10,000.00
ITBIS 18 %        =  1,800.00
Retención (30 % del ITBIS) = 1,800.00 × 30 % = 540.00
Total             = 10,000.00 + 1,800.00 − 540.00 = 11,260.00
```

Para **no retener** en una línea (por ejemplo, en un producto), ponga su retención en 0 en el modal de la línea.

## Reportes 606 y 607

El plugin guarda en cada factura cuánto se retuvo de ITBIS y de ISR, y los reportes lo muestran:

| Reporte | Columna | Dato |
|---------|---------|------|
| 606 | ITBIS Retenido | ITBIS retenido al proveedor |
| 606 | Tipo de Retención en ISR | Código de la retención de ISR |
| 606 | Monto Retención Renta | ISR retenido |
| 607 | ITBIS Retenido por Terceros | ITBIS que le retuvo el cliente |
| 607 | Retención Renta por Terceros | ISR que le retuvo el cliente |
| 607 | Fecha de Retención | Fecha de la factura |

El **TXT para la DGII** incluye estos datos. Revíselo con la plantilla oficial antes de presentarlo.

## Límites a tener en cuenta

- **Una retención por documento.** FacturaScripts guarda un solo porcentaje por línea, así que no puede aplicar a
  la vez una retención de ISR y una de ITBIS en la misma factura (por ejemplo, una persona física que presta un
  servicio gravado). En ese caso registre la retención de mayor importe y el resto en la declaración.
- **La retención sale del cliente o proveedor.** En un cliente o proveedor con retención de ITBIS, el porcentaje de
  cada línea mayor que 0 se recalcula desde esa retención; solo la línea con 0 se respeta.
- **La fecha de retención del 607 es la fecha de la factura.** La DGII pide la fecha en que el tercero retuvo.
- **Facturas anteriores.** Las facturas guardadas antes de esta versión no tienen guardado el reparto de ISR e ITBIS,
  y las de ITBIS se calcularon sobre el neto. Revíselas con su contador.
- Confirme con su contador qué retención corresponde a cada operación.
