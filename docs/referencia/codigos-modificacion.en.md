# Modification codes

They state **what a credit or debit note corrects**, or the context of certain receipts. They are stored in the
`rd_ncfmodificacion` table and in the `ncfcodigomodificacion` field of sales and purchase invoices.

| Code | Description | Applies to |
|:----:|-------------|------------|
| 1 | Cancels the modified NCF | Electronic credit/debit notes (33, 34) |
| 2 | Corrects the text of the modified fiscal receipt | Electronic credit/debit notes (33, 34) |
| 3 | Corrects the amounts of the modified NCF | Electronic credit/debit notes (33, 34) |
| 4 | Replacement of an NCF issued during contingency | Electronic fiscal credit invoice (31) |
| 5 | Reference to electronic consumer invoice | Electronic consumer invoice (32) |

## Where it is chosen

- **Credit notes**: the *New return* form on the [Returns](../facturas/notas.md) tab.
- **Debit notes (33)**: next to *Invoice to modify* on the new invoice.

## Rules

- The plugin **only accepts** the code that matches the receipt type; any other value is discarded on save.
- For types 31 and 32, codes 4 and 5 exist in the catalog, but today no selector on the invoice lets you pick them
  from the screen.
- If the type is not a note (or 31/32 with its code), the field stays empty.
- The chosen code is printed on the PDF together with the modified NCF.
