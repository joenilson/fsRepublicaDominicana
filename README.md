# fsRepublicaDominicana

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/joenilson/fsRepublicaDominicana/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/joenilson/fsRepublicaDominicana/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/joenilson/fsRepublicaDominicana/badges/build.png?b=master)](https://scrutinizer-ci.com/g/joenilson/fsRepublicaDominicana/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/joenilson/fsRepublicaDominicana/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Plugin de **localización fiscal de República Dominicana** para [FacturaScripts](https://facturascripts.com).
Agrega el manejo de comprobantes fiscales (NCF), comprobantes fiscales electrónicos (e-CF), impuestos adicionales,
retenciones y los reportes 606, 607 y 608 de la DGII.

Versión actual: **1.37**. Requiere FacturaScripts 2026 o superior y PHP 8.2 o superior.

## Qué hace

| Área | Qué hace el plugin |
|------|--------------------|
| **NCF** | Maestro de rangos autorizados por la DGII, generación automática del NCF al guardar la factura, control de correlativos y vencimiento. |
| **Comprobantes** | Tipos físicos (01 al 17) y electrónicos (31 al 47). Tipo de comprobante por cliente. |
| **Notas de crédito y débito** | Notas de crédito desde *Devoluciones* (04 y 34) y notas de débito con factura a modificar y código de modificación. Validan el signo, el tope contra la factura y que sea del mismo cliente o proveedor, empresa y divisa. |
| **e-CF** | Carga del XML del e-CF, fecha de firma, código de seguridad y código QR de la DGII en el PDF. El plugin **no firma ni transmite** el e-CF: guarda y muestra los datos que entrega el sistema emisor. |
| **Impuestos adicionales** | ISC específico y *ad valorem* (por porcentaje, monto por unidad o litro de alcohol absoluto), CDT, propina legal y primera placa, por línea. En compras, el monto del ISC se puede digitar. |
| **Retenciones** | ISR e ITBIS con los códigos de la DGII, reflejadas en el PDF y en los reportes. |
| **Reportes** | Informes 606, 607, 608 y consolidado, con exportación a TXT para la DGII. |
| **RNC** | Consulta de contribuyentes desde la ficha de cliente y proveedor (usa un servicio externo). |

## Instalación

1. Descargue el ZIP desde [facturascripts.com](https://facturascripts.com/plugins/fsrepublicadominicana) o desde
   las etiquetas de este repositorio.
2. En FacturaScripts abra **Administrador → Plugins**, súbalo con **Añadir** y active el plugin.
3. Al actualizar, pulse **Reconstruir** para crear las tablas, columnas y traducciones nuevas.

Para que las opciones de impuestos, plan contable y moneda queden como las espera el plugin, instale
FacturaScripts con el país **República Dominicana** y el idioma **Español (República Dominicana)**.

## Novedades

El detalle de cada versión está en [`CHANGELOG-1.37.md`](CHANGELOG-1.37.md).

## Documentación

- Sitio: <https://joenilson.github.io/fsRepublicaDominicana/> (español e inglés).
- Fuentes en [`docs/`](docs/); cómo editarla y regenerar las capturas:
  [docs/desarrollo/documentacion.es.md](docs/desarrollo/documentacion.es.md).
- La documentación no viaja dentro del ZIP del plugin (ver `.zipignore`).

## Desarrollo

- **Calidad:** el análisis de Scrutinizer usa `.scrutinizer.yml`, que clona el núcleo de FacturaScripts para resolver
  sus clases y excluye `vendor/`. Hay reglas de estilo en `phpcs.xml` y `eslint.config.mjs`.
- **Pruebas unitarias:** `Test/main` (PHPUnit; requiere el entorno de pruebas de FacturaScripts, que pide PHP 8.4).
- **Pruebas de lanzamiento:** `Test/release/run.sh` ejecuta 37 casos integrales (compras, ventas, notas, retenciones,
  reportes DGII) y deja capturas, PDF e informe. Escribe datos reales: úsela solo en una instalación de pruebas.
  Detalles en [docs/desarrollo/pruebas-lanzamiento.es.md](docs/desarrollo/pruebas-lanzamiento.es.md).
- **Paquete:** `fsmaker zip` genera el ZIP del plugin y respeta `.zipignore`.

## Licencia

LGPL-3.0-or-later. Ver [LICENSE](LICENSE).
