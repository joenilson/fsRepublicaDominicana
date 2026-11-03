# Electronic receipts (e-CF)

The plugin records and reports electronic fiscal receipts. Types 31 to 47 are in the
[catalog](../referencia/tipos-comprobante.md) and are issued with series `E` from a [range](../configuracion/rangos.md).

!!! warning "Plugin scope"
    The plugin **does not sign or transmit** the e-CF to the DGII. It stores and shows the e-CF data (NCF, signing
    date, security code, signed XML and PDF, status and *track id*) provided by the system that does sign and
    transmit. For the official certification process see the DGII documents on
    [electronic invoicing](https://dgii.gov.do/cicloContribuyente/facturacion/comprobantesFiscalesElectronicosE-CF).

## Data stored on each invoice

| Data | Field |
|------|-------|
| Electronic NCF | **NCF** |
| e-CF type | **Receipt type** (31, 32, 33, 34, 41, 43...) |
| Signing date and time | **Signing date** |
| Security code | **Security code** |
| DGII status, *track id*, signed XML and PDF | Internal fields for external integrations |

## Loading an e-CF XML

Sales, purchases and the return form have an **e-Invoice XML file** selector and a **Load XML** button. After
choosing the file, the plugin reads it in the browser and fills:

- NCF (*eNCF*), e-CF type and issue date;
- signing date and time and security code;
- sequence expiry date (`FechaVencimientoSecuencia`). Types 32 and 34 do not carry it: in that case the form's
  existing date is kept.

Review the values before saving.

## Printed representation

The PDF of an electronic invoice includes the receipt type, NCF, **QR code**, security code and signing date.
Notes also print the modified NCF and the modification code.

### QR code

The QR opens the DGII stamp lookup (`ConsultaTimbre`) with the issuer's RNC, the NCF, the issue date, the total
amount, the signing date and the security code. It is printed only on sales invoices with an electronic NCF (series `E`),
and the issuer is the invoice's company.

- **Environment:** by default the QR points to production (`ecf`). Another plugin can return `testecf` or `certecf`
  through the `ecfTimbreEnvironment` hook.
- **Buyer without RNC:** for types 43, 46 and 47 (for example, foreigners) the `RncComprador` parameter is left out of
  the address, because with the parameter empty the DGII answers that the receipt was not found.
- **Requirement:** the plugin draws the QR with the `chillerlan/php-qrcode` library, which ships in `vendor/` and needs PHP 8.2 or later.

A ticket format (`ncf-dominicana`) is also available when the **Tickets** plugin is installed.

## DGII schemas and rules

XML formats, XSD schemas and the technical description are published by the DGII. The plugin uses the fields from
those formats that it needs for recording and reports; validating the XML against the XSD is the issuing system's job.
