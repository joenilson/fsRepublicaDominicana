# Additional taxes (ISC)

Besides ITBIS, the plugin handles the DGII additional taxes in
**Accounting → Dominican Republic → Taxes**.

![Additional taxes list](../assets/img/impuestos-adicionales.png)

## Included catalog

| Codes | Tax | Calculation |
|-------|-----|-------------|
| 001 | Legal tip | Percentage (10 %) |
| 002 | CDT, Telecommunications Development Contribution | Percentage (2 %) |
| 003, 004 | ISC on insurance and telecommunications | Percentage |
| 005 | First vehicle license plate | Percentage (17 %) |
| 006 – 022 | Specific ISC on alcohol and cigarettes | Fixed amount per unit or per liter of absolute alcohol |
| 023 – 039 | *Ad valorem* ISC on alcohol and cigarettes | Percentage |

## How it is calculated

Each tax defines its **rate type**:

=== "Percentage"

    ```text
    amount = price before discount × rate / 100
    ```

=== "Amount"

    ```text
    specific     = factor × rate
    ad valorem   = base × ad valorem rate / 100
    ```

    The **factor** depends on the tax's *calculation base*:

    | Calculation base | Factor |
    |------------------|--------|
    | `unidad` (unit) | Line quantity |
    | `litros_alcohol_absoluto` | Quantity × content in liters × alcohol degrees / 100 |

    The *ad valorem* **base** is the price before discount, plus the specific amount when the tax has
    *ad valorem on specific* enabled.

!!! warning "Formulas pending validation"
    The code flags the specific ISC formulas as pending validation against Law 253-12 and the DGII e-CF technical
    description. **Confirm them with your accountant** before using them in production. It is also still open whether
    the ITBIS base should include ISC (today it is computed on the price before discount only).

## Applying it to a line

On the invoice line open the **···** (more options) button. The modal adds the **ISC**, **CDT**,
**Legal tip** and **1st plate** selectors.

![Line modal with tax selectors](../assets/img/linea-impuestos.png)

- The ISC selector is searchable and shows **[Amount]** or **[Percentage]** in brackets.
- The calculated amount is stored on the line and later appears in the 606 and 607 reports.
- On **purchases** you can also type the **ISC amount manually** when the supplier invoice differs from the
  calculated one.
- The additional tax total is shown on the invoice as **Other taxes** and **Total + tax**.
