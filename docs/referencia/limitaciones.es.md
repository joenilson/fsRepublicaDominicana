# Limitaciones y pendientes conocidos

## Alcance del plugin

- **No firma ni transmite e-CF.** Registra y reporta los datos; la emisión electrónica la hace otro sistema.
  Ver [comprobantes electrónicos](../facturas/ecf.md).
- **Compatibilidad con otros plugins.** El plugin modifica los formularios de factura de venta y de compra. No es
  completamente compatible con otros plugins que hagan lo mismo. Ante un conflicto, contacte primero al soporte del
  otro plugin y luego valide con este.
- **Consulta de RNC externa.** Requiere Internet y envía el número consultado a un servicio de terceros.
  Ver [búsqueda de RNC](../busqueda/rnc.md).

## Notas de crédito y débito

- Los selectores de *Factura a Modificar* y *Código de Modificación* en la factura aparecen solo con el tipo
  **33**. La nota de débito física (03) no los muestra.
- Los códigos de modificación **4** y **5** (facturas 31 y 32) existen en el catálogo, pero hoy no se pueden elegir
  desde una pantalla.
- Las notas de débito no se generan desde *Devoluciones*.

## Impuestos y reportes

- Las **fórmulas del ISC** específico están pendientes de validar contra la Ley 253-12 y la descripción técnica
  del e-CF. También está abierta la decisión de si la base del ITBIS debe incluir el ISC.
- En el **TXT** de los reportes 606 y 607 solo se invierte el signo de las notas de crédito físicas (tipo 04). Las
  notas de crédito electrónicas (34) se exportan con signo positivo. Verifique estos casos antes de presentar.
- El TXT todavía no llena las columnas de ISC, otros impuestos y propina legal con las plantillas oficiales.
- **Una retención por documento:** FacturaScripts guarda un solo porcentaje de retención por línea, por lo que no se
  pueden combinar ISR e ITBIS en la misma factura. Ver [Retenciones](../configuracion/retenciones.md).
- La **fecha de retención** del 607 es la fecha de la factura, no la fecha en que el tercero retuvo.
- Las columnas de **ITBIS sujeto a proporcionalidad, ITBIS por adelantar y ITBIS/ISR percibidos** de las tablas 606
  y 607 no se llenan con los datos de las facturas.
- Las facturas guardadas antes de la versión 1.36 no tienen guardado el reparto de la retención entre ISR e ITBIS.
- El **TXT del 606** escribe 24 campos por registro y el formato oficial tiene 23, y el ITBIS llevado al costo y la
  forma de pago pueden quedar una posición corridos. Compárelo con la plantilla de la DGII antes de presentarlo.
- Formatos 609 (pagos al exterior) y 623 (ventas al Estado) no están implementados.

## Cómo reportar un problema

Abra un *issue* en [GitHub](https://github.com/joenilson/fsRepublicaDominicana/issues) indicando la versión del
plugin, la de FacturaScripts, el tipo de comprobante involucrado y, si puede, una captura sin datos reales.
