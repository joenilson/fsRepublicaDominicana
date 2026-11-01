# Known limitations and pending items

## Plugin scope

- **It does not sign or transmit e-CF.** It records and reports the data; electronic issuing is done by another
  system. See [electronic receipts](../facturas/ecf.md).
- **Compatibility with other plugins.** The plugin modifies the sales and purchase invoice forms. It is not fully
  compatible with other plugins that do the same. On a conflict, contact the other plugin's support first and then
  validate with this one.
- **External RNC lookup.** It needs Internet and sends the queried number to a third-party service.
  See [RNC lookup](../busqueda/rnc.md).

## Credit and debit notes

- The *Invoice to modify* and *Modification code* selectors on the invoice appear only with type **33**. The paper
  debit note (03) does not show them.
- Modification codes **4** and **5** (invoices 31 and 32) exist in the catalog, but today they cannot be picked
  from a screen.
- Debit notes are not generated from *Returns*.

## Taxes and reports

- The specific **ISC formulas** are pending validation against Law 253-12 and the DGII e-CF technical description.
  Whether the ITBIS base should include ISC is also still open.
- In the 606 and 607 **TXT** only the sign of paper credit notes (type 04) is inverted. Electronic credit notes (34)
  are exported with a positive sign. Verify these cases before filing.
- The TXT does not yet fill the ISC, other taxes and legal tip columns using the official templates.
- **One withholding per document:** FacturaScripts stores a single withholding percentage per line, so ISR and ITBIS
  cannot be combined on the same invoice. See [Withholdings](../configuracion/retenciones.md).
- The 607 **withholding date** is the invoice date, not the date the third party withheld.
- The **ITBIS subject to proportionality, ITBIS to be credited and ITBIS/ISR collected in advance** columns of the
  606 and 607 tables are not filled from the invoice data.
- Invoices saved before version 1.36 do not have the ISR/ITBIS withholding split stored.
- The **606 TXT** writes 24 fields per record and the official format has 23, and the ITBIS taken to cost and the
  payment method may be shifted by one position. Compare it with the DGII template before filing.
- Formats 609 (payments abroad) and 623 (sales to the State) are not implemented.

## Reporting a problem

Open an issue on [GitHub](https://github.com/joenilson/fsRepublicaDominicana/issues) with the plugin version, the
FacturaScripts version, the receipt type involved and, if possible, a screenshot without real data.
