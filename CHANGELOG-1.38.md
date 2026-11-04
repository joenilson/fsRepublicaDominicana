# fsRepublicaDominicana 1.38

Localización contable y fiscal para República Dominicana. Esta versión agrega la elección del tipo de comprobante al crear un cliente desde la factura y cambia el tipo predeterminado de los clientes nuevos a la factura de consumo electrónica (32). Todo lo anterior está en el [changelog de la 1.37](CHANGELOG-1.37.md).

**Requisitos:** FacturaScripts 2026 o superior y PHP 8.2 o superior.

> **Importante al actualizar:** pulse *Reconstruir* en Administrador → Plugins. El valor predeterminado de la columna `tipocomprobante` de los clientes cambia con esta versión.

---

## Novedades

### Tipo de comprobante al crear un cliente desde la factura
- En **Ventas → Facturas**, el modal de clientes tiene el botón verde **Nuevo**. Su formulario (nombre, cédula/RNC, teléfono, correo, dirección, código postal, ciudad, provincia y país) ahora pide también el **Tipo de comprobante** que se le generará al cliente.
- El selector propone el **32 (factura de consumo electrónica)**.
- El tipo elegido se guarda en la ficha del cliente y la factura lo toma de inmediato.
- Lista los mismos tipos de venta que la ficha del cliente, y solo se aceptan tipos de venta válidos.
- Aparece también en presupuestos, pedidos y albaranes de venta, porque el modal es el mismo.

---

## Cambios

- **Tipo de comprobante predeterminado de los clientes: del 02 al 32.** Antes un cliente nuevo nacía con el 02 (factura de consumo) fijo en la tabla; ahora nace con el 32. Los clientes existentes no cambian.
  - Esto afecta a todo cliente nuevo, también al creado desde Ventas → Clientes. Si su empresa aún no emite comprobantes electrónicos, necesitará un rango para el tipo 32 o deberá cambiar el tipo del cliente.
- Los valores de respaldo que decían `02` en la factura de venta pasan a usar el mismo valor predeterminado.

---

## Cambios internos
- Constante `NCFTipo::TIPO_VENTA_PREDETERMINADO` como única fuente del tipo predeterminado de venta.
- Nuevo mod de encabezado de ventas (`Mod/SalesHeaderHTMLMod.php`) y nuevo `Assets/JS/fsrd-customer.js`, porque el núcleo no ofrece un punto de extensión para el formulario de cliente nuevo.
- **Pruebas de lanzamiento en Docker o Podman:** `Test/release/run-docker.sh` levanta una instalación limpia de FacturaScripts con la imagen oficial (`facturascripts/facturascripts`) y MySQL, instala sin asistente, carga el país República Dominicana, activa el plugin, corre los 37 casos y genera capturas, PDF e informe. Al terminar borra el entorno. También permite probar contra la etiqueta `beta`.
- **Paquete más liviano:** el ZIP del plugin ya no incluye la documentación ni las herramientas de desarrollo (`.zipignore`). Pasó de unos 9.8 MB a menos de 1 MB.
- README reescrito como resumen del plugin, con la versión actual y el enlace al changelog.

## Documentación
- Nueva sección «Crear un cliente desde la factura» en facturas de venta y nota sobre el tipo predeterminado en la configuración inicial, en español e inglés.
- La instalación indica ahora FacturaScripts 2026 o superior.
- Nueva sección de pruebas de lanzamiento con Docker o Podman.
- Sitio: <https://joenilson.github.io/fsRepublicaDominicana/>

## Obsoleto
- Las acciones AJAX `busca_tipo`, `busca_pago`, `busca_movimiento` y `busca_tipoanulacion` siguen obsoletas y todavía no se eliminan.

---

## Pendiente de validar con su contador
Sin cambios respecto a la 1.37: las fórmulas del ISC específico, el campo de ITBIS llevado al costo del 606 y los códigos ISR 1 a 9 del 606.
