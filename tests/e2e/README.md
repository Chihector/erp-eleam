# QA visual (scripts heredados)

Estos scripts `.mjs` se usaron durante el desarrollo para recorrer el panel con
un navegador automatizado. No son pruebas PHPUnit ni se ejecutan en CI.

Dependen de un arnés externo que entrega `page` y `ui` (por ejemplo, la
herramienta de QA visual usada en el proyecto); por sí solos no importan
Puppeteer ni otra librería. Los selectores y flujos pueden haber quedado
desactualizados respecto de la interfaz actual.

Se conservan como referencia y para repetir una QA manual asistida. Si se
quieren convertir en pruebas de regresión estables, moverlos a un framework
E2E con dependencias declaradas en `package.json` y datos de prueba aislados.
