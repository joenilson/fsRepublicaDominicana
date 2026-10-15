# Comprobantes electrónicos (e-CF)

El plugin registra y reporta comprobantes fiscales electrónicos. Los tipos 31 al 47 vienen en el
[catálogo](../referencia/tipos-comprobante.md) y se emiten con series `E` desde un [rango](../configuracion/rangos.md).

!!! warning "Alcance del plugin"
    El plugin **no firma ni transmite** el e-CF a la DGII. Guarda y muestra los datos del e-CF (NCF, fecha de firma,
    código de seguridad, XML y PDF firmados, estado y *track id*) que le entrega el sistema que sí firma y transmite.
    Para el proceso oficial de certificación consulte los documentos de la DGII sobre
    [facturación electrónica](https://dgii.gov.do).

## Datos que guarda cada factura

| Dato | Campo |
|------|-------|
| NCF electrónico | **NCF** |
| Tipo de e-CF | **Tipo de comprobante** (31, 32, 33, 34, 41, 43...) |
| Fecha y hora de firma | **Fecha de Firma** |
| Código de seguridad | **Código de Seguridad** |
| Estado ante la DGII, *track id*, XML y PDF firmados | Campos internos para integraciones externas |

## Cargar el XML de un e-CF

En ventas, compras y en el formulario de devolución hay un selector **Archivo XML de e-Factura** y el botón
**Cargar XML**. Al elegir el archivo, el plugin lo lee en el navegador y completa:

- NCF (*eNCF*), tipo de e-CF y fecha de emisión;
- fecha y hora de firma y código de seguridad;
- fecha de vencimiento de la secuencia (`FechaVencimientoSecuencia`). Los tipos 32 y 34 no la traen: en ese caso
  se conserva la fecha que ya tenga el formulario.

Revise los valores antes de guardar.

## Representación impresa

El PDF de una factura electrónica incluye el tipo de comprobante, el NCF, el **código QR**, el código de seguridad
y la fecha de firma. En notas se imprimen además el NCF modificado y el código de modificación.

También hay un formato de ticket (`ncf-dominicana`) cuando está instalado el plugin **Tickets**.

## Esquemas y normas de la DGII

Los formatos de los XML, esquemas XSD y la descripción técnica están publicados por la DGII. El plugin usa los
campos de esos formatos que necesita para el registro y los reportes; la validación del XML contra el XSD
corresponde al sistema emisor.
