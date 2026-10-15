# Products

The plugin adds the **Dominican Republic Fiscal Information** section to the product form.

![Product form with fiscal information](../assets/img/producto.png)

| Field | Use |
|-------|-----|
| **Is service?** | Marks the product as a service. In the 606 it separates services from goods. |
| **Alcohol degrees (%)** | Alcohol content, used by the ISC per liter of absolute alcohol. |
| **Content per unit (liters)** | Liters in one unit, used together with the degrees. |

## Specific ISC example

A 0.7 L bottle of rum at 40 % alcohol, sold in quantity 10, with tax *014 – Rum and other cane spirits*, has a
factor of:

```text
10 × 0.7 × 40 / 100 = 2.8 liters of absolute alcohol
```

That factor is multiplied by the tax rate. See [Additional taxes](impuestos.md) for details and the warning about
formula validation.
