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
- **Credit notes by amount** (lines without a product) work if the amount is entered as a negative. The plugin rejects a
  credit note (04 or 34) with a positive amount, a debit note (03 or 33) with a negative amount, one that, added to the
  other active notes of the same invoice, exceeds its total, and one that modifies a cancelled invoice or one of
  another customer, supplier, company or currency. A note saved before this version that breaks the rule cannot be
  saved again until it is fixed. A note without an original invoice is only validated by its sign. See
  [credit and debit notes](../facturas/notas.md).
- A line **without a product** is reported as goods in the 606; it cannot be marked as a service.
- In the **606**, field 14 (**ITBIS taken to cost**) is always blank and all the ITBIS is left as ITBIS to advance
  (field 15), because the plugin has no data saying which ITBIS goes to cost. If your accountant takes part of it to
  cost, adjust the file before filing.
- The TXT does not yet fill the ISC, other taxes and legal tip columns using the official templates.
- **One withholding per document:** FacturaScripts stores a single withholding percentage per line, so ISR and ITBIS
  cannot be combined on the same invoice. See [Withholdings](../configuracion/retenciones.md).
- The 607 **withholding date** is the invoice date, not the date the third party withheld.
- The **ITBIS subject to proportionality, ITBIS to be credited and ITBIS/ISR collected in advance** columns of the
  606 and 607 tables are not filled from the invoice data.
- Invoices saved before version 1.36 do not have the ISR/ITBIS withholding split stored.
- Formats 609 (payments abroad) and 623 (sales to the State) are not implemented.

## Reporting a problem

Open an issue on [GitHub](https://github.com/joenilson/fsRepublicaDominicana/issues) with the plugin version, the
FacturaScripts version, the receipt type involved and, if possible, a screenshot without real data.
