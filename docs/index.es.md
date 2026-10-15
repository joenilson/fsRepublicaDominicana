# fsRepublicaDominicana

Plugin de **localización fiscal de República Dominicana** para [FacturaScripts](https://facturascripts.com).
Agrega a FacturaScripts el manejo de comprobantes fiscales (NCF), comprobantes fiscales electrónicos (e-CF),
impuestos adicionales (ISC, CDT, propina legal) y los reportes 606, 607 y 608 de la DGII.

![Factura de venta con los campos fiscales del plugin](assets/img/factura-venta-ecf31.png)

## ¿Qué incluye?

| Área | Qué hace el plugin |
|------|--------------------|
| **NCF** | Maestro de rangos autorizados por la DGII, generación automática del NCF al guardar la factura, control de correlativos y vencimiento. |
| **Comprobantes** | Tipos físicos (01 al 17) y electrónicos (31 al 47). Tipo de comprobante por cliente. |
| **Notas** | Notas de crédito desde *Devoluciones* y notas de débito con factura a modificar y código de modificación. |
| **e-CF** | Carga del XML del e-CF, fecha de firma, código de seguridad y representación impresa. |
| **Impuestos** | ISC específico y *ad valorem*, CDT, propina legal y otros impuestos por línea. |
| **Reportes** | Informes 606, 607, 608 y consolidado, con exportación a TXT para la DGII. |
| **RNC** | Consulta de contribuyentes desde la ficha de cliente y proveedor. |

## Por dónde empezar

1. [Instalación](instalacion.md): requisitos y activación del plugin.
2. [Configuración inicial](configuracion/inicial.md): menús del plugin y orden recomendado.
3. [Rangos de NCF](configuracion/rangos.md): cargar las autorizaciones de la DGII.
4. [Facturas de venta](facturas/ventas.md) y [de compra](facturas/compras.md).

!!! info "Versión documentada"
    Esta documentación corresponde al plugin **1.36** sobre FacturaScripts **2026.65**.
    Las capturas se generan automáticamente contra una instalación limpia con datos sintéticos
    (ver [Cómo se genera esta documentación](desarrollo/documentacion.md)).

## Soporte y contribuciones

El plugin es de código abierto (LGPL-3.0). Reporte problemas o proponga mejoras en
[GitHub](https://github.com/joenilson/fsRepublicaDominicana/issues).
