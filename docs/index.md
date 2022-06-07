layout: page
title: "Inicio"
permalink: /

Plugin base para generar los comprobantes fiscales NCF para República Dominicana

- Requisitos de Instalación
  Para un correcto funcionamiento la instalación de FacturaScripts debe hacerse eligiendo el idioma Español de República Dominicana y el País República Dominicana

- Configuración Inicial
  Para una correcta configuración primero se debe agregar en Contabilidad > República Dominicana > Maestro de NCF, la lista de tipos de comprobante autorizados por DGII para su emisión.

Luego de esto debe ir a Contabilidad > República Dominicana > Lista de RNC de DGII y darle al botón de Actualizar

*** Este proceso toma mas de 10 minutos, mientras se ejecuta por favor no actualice ni cierre la pantalla ***
Esta opción lo que hace es descarga el archivo ZIP de RNC de la DGII, descomprime el mismo y luego guarda la información en la base de datos de FacturaScripts, esto se usa para verificar los números de RNC de clientes y proveedores, si no requiere esta funcionalidad no es necesario que haga esta actualización de datos.

Los comprobantes fiscales soportados en este momento son todos los comprobantes físicos desde el 01 hasta el 17.

- Impresión de Comprobantes de Venta
  La impresión de facturas tambien permite imprimir el tipo de comprobante fiscal, el numero de NCF y la fecha de vencimiento si esta aplica.

- Verificación de NCF de Proveedores
  En el modulo de registro de facturas de proveedores puede verificar si el numero de NCF de un proveedor ya ha sido registrado con anterioridad antes de darle a guardar.

- Limitantes del plugin
  El plugin no es completamente compatible con otros plugins que modifiquen las facturas de ventas o compras, cuando encuentre un problema de este tipo debe contactar primero al soporte del plugin no compatible y luego validar con el plugin de República Dominicana.

- Secciones que añade el plugin a FacturaScripts
  El plugin agrega los siguientes submenus:

En Contabilidad agrega el submenu República Dominicana con las siguientes secciones:
- Lista de RNC de la DGII : Descarga el archivo ZIP de RNC de la DGII para usarlo internamente para validar los RNC de clientes o proveedores.
- Maestro de NCF: Aquí se agregan las autorizaciones de emisión de NCF de la DGII.
- Tipo de Anulaciones: Aquí se puede dar mantenimiento a los tipos de anulaciones de DGII.
- Tipo de Movimiento: Aquí se puede dar mantenimiento a los tipos de movimientos de DGII.
- Tipo de NCF: Aquí se puede dar mantenimiento a las descripciones de los tipos de NCF.
- Tipo de Pago: Aquí se puede dar mantenimiento a los tipos de pago de DGII.

En Informes se agrega el submenu República Dominicana con las siguientes opciones:

- Informes Fiscales: Aquí uno puede generar los informes 606, 607 y 608 y un informe general con las compras y ventas.
  El plugin agrega los siguientes campos en la parte final de la pantalla de Ventas > Clientes

- Tipo de comprobante: Para indicar el tipo de comprobante que se le debe generar a este cliente
- Tipos de Pago: Para indicar cual es la forma de pago que se debe reportar de este cliente.
  El plugin agrega los siguientes campos en la parte final de la pantalla Compras > Proveedores

- Tipos de Pago: Este campo es para indicar cual es el tipo de pago que se tiene pactado con este cliente según la codificación de DGII.