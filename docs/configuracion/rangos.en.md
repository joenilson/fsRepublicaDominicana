# NCF ranges

The **NCF master** (*Accounting → Dominican Republic → NCF master*) stores the sequences authorized to you by the
DGII. The plugin takes the next number of the active range every time an invoice is saved.

![NCF master](../assets/img/ncf-rangos.png)

## Range fields

| Field | Description |
|-------|-------------|
| **Receipt type** | NCF type the range belongs to (see [catalog](../referencia/tipos-comprobante.md)). |
| **Series** | `B` for paper receipts and `E` for electronic ones (e-CF). |
| **Request / Authorization** | Numbers issued by the DGII when authorizing the sequence. |
| **Start / End sequence** | Limits of the authorized sequence. |
| **Current sequence** | Next number to use. Incremented by the plugin. |
| **Expiry date** | Validity of the sequence. Copied to the invoice as the NCF expiry date. |
| **Status** | Only active ranges are used when invoicing. |

## Generated NCF format

The NCF is built as **series + type + zero-padded sequence**:

| Series | Sequence length | Example |
|--------|-----------------|---------|
| `B` (paper) | 8 digits | `B0100000001` |
| `E` (electronic) | 10 digits | `E310000000001` |

## How the range is chosen

When saving an invoice without NCF, the plugin looks, for the invoice's company and receipt type, for an **active**
range whose current sequence does not exceed the end sequence. If none is found it shows the error
*no NCF range for the type* and the invoice is not saved.

!!! tip "Do not run out of sequence"
    Load the next authorization before the current range is exhausted. With an exhausted range and no other active
    one, invoices of that type cannot be issued.

## Receipt types generated on purchases

On **supplier** invoices the NCF is typed by whoever records the invoice, except for receipts your own company issues
(**11**, **13**, **41** and **43**): for those the plugin takes the number from your ranges.
