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
- Las columnas *id-item* y *date-retention* de las tablas de 606/607 se muestran sin traducir.
- Formatos 609 (pagos al exterior) y 623 (ventas al Estado) no están implementados.

## Cómo reportar un problema

Abra un *issue* en [GitHub](https://github.com/joenilson/fsRepublicaDominicana/issues) indicando la versión del
plugin, la de FacturaScripts, el tipo de comprobante involucrado y, si puede, una captura sin datos reales.
