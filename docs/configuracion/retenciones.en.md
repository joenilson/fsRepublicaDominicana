# Withholdings (ISR and ITBIS)

FacturaScripts handles withholdings with a single field that is internally called **IRPF** (the Spanish personal
income tax). That concept does not exist in the Dominican Republic: the DGII talks about **withholdings**
(*retenciones*) of ISR (income tax) and ITBIS. That is why the plugin changes the texts to the DGII terminology and
calculates each withholding according to its rule.

## How each type is calculated

| Type | The percentage applies to | Example (service of 10,000.00 with 18 % ITBIS) |
|------|---------------------------|-----------------------------------------------|
| **ISR** | The line **net amount** | 10 % → 10,000.00 × 10 % = **1,000.00** |
| **ITBIS** | The line **ITBIS invoiced** | 30 % → 1,800.00 × 30 % = **540.00**; 100 % → **1,800.00** |

Internally FacturaScripts can only apply a percentage to the net amount. For ITBIS-type withholdings the plugin
converts it using the line ITBIS (30 % of 18 % ITBIS is 5.4 % of the net amount), so the amount is also correct on
lines with 16 % or exempt ITBIS.

### ITBIS withholdings (DGII guide)

| Percentage | When it is withheld | Rule |
|------------|---------------------|------|
| 30 % of ITBIS | Companies paying liberal professional services or movable-goods rental to other companies | 02-05 |
| 100 % of ITBIS | Legal entities paying taxed services to non-profit entities | 01-11 |
| 100 % of ITBIS | Companies paying security or surveillance services to other companies | 07-09 |
| 100 % of ITBIS | Purchases from individuals not registered as taxpayers (purchase voucher) | 05-19 |
| 30 % or 100 % | Construction sector: 30 % if a legal entity provides the service, 100 % if an individual | 07-07 |

The catalog includes the `RETEN30` and `RETEN100` withholdings, which the plugin marks as **ITBIS**.

### ISR withholdings

The plugin adds these ISR withholdings with the **606 format code** (*Tipo de Retención en ISR*):

| Code | Description | % |
|------|-------------|---|
| `RETISR1` | Rents (code 1) | 10 |
| `RETISR2` | Fees for services (code 2) | 10 |
| `RETISR3` | Other income (code 3) | 10 |
| `RETISR5` | Interest to legal entities (code 5) | 1 |
| `RETISR6` | Interest to individuals (code 6) | 1 |
| `RETISR7` | State suppliers (code 7) | 5 |
| `RETISR8` | Telephone games (code 8) | 5 |
| `RETISR9` | Beef cattle subsector (code 9) | 1 |

The percentages come from the DGII guide *Retenciones de ISR e ITBIS* (July 2025). There are other ISR withholdings
(for example 2 % on technical services or 27 % on remittances abroad) that you can create yourself.

!!! note "Who must withhold"
    According to the DGII, only legal entities, sole-proprietor businesses and public entities act as withholding
    agents. An individual who is not a sole-proprietor business does not withhold ISR.

## Creating a withholding

1. Open **Accounting → Withholdings** (or edit an existing one).
2. Enter the **code**, **description** and **percentage**.
3. In **Tax withheld** choose **ISR** or **ITBIS**.
4. For ISR, choose the 606 format **ISR withholding type**.

## Applying it on a document

1. Assign the withholding as the customer's or supplier's **default withholding**.
2. New documents for that customer or supplier take the withholding on every line.
3. The withheld amount is deducted from the total, shown in the footer and on the **PDF** (*Withholding* row) and
   posted to the withholding subaccount. If the subaccount is missing or the withholding does not exist,
   FacturaScripts warns you when posting.

Example, a service of 10,000.00 with 18 % ITBIS and a `RETEN30` withholding:

```text
Net                          = 10,000.00
ITBIS 18 %                   =  1,800.00
Withholding (30 % of ITBIS)  = 1,800.00 × 30 % = 540.00
Total                        = 10,000.00 + 1,800.00 − 540.00 = 11,260.00
```

To **not withhold** on a line (for example on a product), set its withholding to 0 in the line modal.

## Reports 606 and 607

The plugin stores on each invoice how much ITBIS and ISR was withheld, and the reports show it:

| Report | Column | Data |
|--------|--------|------|
| 606 | ITBIS Withheld | ITBIS withheld from the supplier |
| 606 | ISR Withholding Type | Code of the ISR withholding |
| 606 | Income Tax Withheld Amount | ISR withheld |
| 607 | ITBIS Withheld by Third Parties | ITBIS the customer withheld from you |
| 607 | Income Tax Withheld by Third Parties | ISR the customer withheld from you |
| 607 | Withholding Date | Invoice date |

The **DGII TXT file** includes this data. Review it against the official template before filing.

## Limits to keep in mind

- **One withholding per document.** FacturaScripts stores a single percentage per line, so you cannot apply an ISR
  and an ITBIS withholding on the same invoice (for example, an individual providing a taxed service). In that case
  record the larger withholding and handle the rest in your tax return.
- **The withholding comes from the customer or supplier.** For a customer or supplier with an ITBIS withholding, a
  line percentage greater than 0 is recalculated from that withholding; only a line set to 0 is respected.
- **The 607 withholding date is the invoice date.** The DGII asks for the date the third party withheld.
- **Earlier invoices.** Invoices saved before this version do not have the ISR/ITBIS split stored, and the ITBIS
  ones were calculated on the net amount. Review them with your accountant.
- Confirm with your accountant which withholding applies to each operation.
