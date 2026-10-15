# Búsqueda de RNC

En las fichas de **cliente** y **proveedor**, el campo del RNC o cédula incluye un botón de lupa que consulta
al contribuyente.

![Ficha de cliente](../assets/img/cliente.png)

## Cómo usarla

1. Abra o cree un cliente o proveedor.
2. Escriba el RNC o la cédula en el campo de identificación fiscal.
3. Pulse el botón de lupa. Si el contribuyente existe, el plugin muestra sus datos (nombre, nombre comercial y
   estado) para que los copie a la ficha.
4. Si no existe, aparece un aviso de que el RNC no fue encontrado.

## Servicio de consulta

!!! info "Servicio externo"
    La consulta **no usa una base local**. El plugin envía el RNC a un servicio externo de consulta de contribuyentes
    y muestra su respuesta. Necesita acceso a Internet desde el servidor y, al usarla, el número consultado sale de su
    instalación hacia ese servicio. Si su política de datos no lo permite, no use el botón: el resto del plugin
    funciona sin él.

!!! note "Cambio respecto a versiones anteriores"
    Las versiones antiguas descargaban el listado completo de RNC de la DGII en una tabla local desde
    *Lista de RNC*. Esa pantalla ya no existe; ahora la consulta es puntual desde la ficha.
