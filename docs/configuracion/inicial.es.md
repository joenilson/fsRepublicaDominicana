# Configuración inicial

## Menús que agrega el plugin

=== "Contabilidad → República Dominicana"

    | Opción | Para qué sirve |
    |--------|----------------|
    | **Maestro de NCF** | Rangos de comprobantes autorizados por la DGII. |
    | **Tipos de NCF** | Catálogo de tipos de comprobante (01–17 y 31–47). |
    | **Tipos de Anulaciones** | Motivos de anulación de la DGII (01–08). |
    | **Tipos de Movimiento** | Clasificación de ingresos (ventas) y gastos (compras). |
    | **Tipos de Pago** | Formas de pago de la DGII para ventas y compras. |
    | **Impuestos** | Impuestos adicionales: ISC, CDT, propina legal, primera placa. |

=== "Informes → República Dominicana"

    | Opción | Para qué sirve |
    |--------|----------------|
    | **Informes Fiscales** | Reportes 606, 607, 608 y consolidado, con exportación TXT. |

## Orden recomendado

1. Verifique que la **empresa** tenga su RNC en *Administrador → Empresas* (se usa en los reportes).
2. Revise el catálogo de [tipos de comprobante](../referencia/tipos-comprobante.md) y desactive los que no use.
3. Cargue sus [rangos de NCF](rangos.md).
4. Asigne a cada **cliente** su tipo de comprobante habitual y su tipo de pago.
5. Marque en cada **producto** si es servicio, y su grado de alcohol y contenido si lleva
   [ISC](impuestos.md).

## Campos que se agregan a clientes y proveedores

=== "Cliente"

    - **Tipo de comprobante**: el que se propone al facturarle (por ejemplo 01 crédito fiscal o 02 consumo).
    - **Tipo de pago**: la forma de pago que se reporta en el 607.

    ![Ficha de cliente](../assets/img/cliente.png)

=== "Proveedor"

    - **Tipo de pago**: la forma de pago pactada, según la codificación de la DGII.

    ![Ficha de proveedor](../assets/img/proveedor.png)

## Catálogos de la DGII

Los catálogos (tipos de NCF, anulación, movimiento y pago) vienen precargados. Cada lista tiene un botón
**Restaurar datos originales** para volver a los valores de fábrica.

!!! warning "Restaurar borra los cambios"
    Restaurar elimina los registros del catálogo y los vuelve a crear. Úselo solo si necesita volver al estado inicial.
