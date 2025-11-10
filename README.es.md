# My Web Project

## 1. Visión general
Aplicación web de comercio básica con:
- Registro e inicio de sesión de usuarios.
- Carrito persistente ligado a base de datos.
- Pasarela de pago (simulación) vía PayPal Sandbox.
- Generación de facturas (PDF) tras el checkout.
- Historial de facturas descargables.
- Panel de administración para monitorizar actividad de usuarios.
Servido con PHP + Apache sobre HTTPS (certificado autofirmado) y MySQL.

## 2. Tecnologías y motivo de la elección
- PHP 8.2: integración sencilla con Apache, ideal para prototipos rápidos.
- Apache + SSL: entorno clásico, fácil de levantar con Docker; HTTPS necesario para cookies seguras.
- MySQL: relacional, con soporte de relaciones e inicialización simple con script SQL.
- JavaScript (ES6): para AJAX dinámico (actualización de carrito/facturas y PayPal).
- PayPal SDK: estándar para integrar pagos reales/sandbox.
- jsPDF: generación de facturas en el cliente sin dependencias del servidor.
- Docker: entorno reproducible (frontend + DB) evitando configuraciones locales manuales.

## 3. Estructura de carpetas (resumen)
```
/frontend/public        -> Archivos públicos (index.php, principal.php, admin.php)
/frontend/src           -> Lógica PHP (register_logic, principal_logic, admin_logic, AJAX)
/backend/src            -> init.sql para crear esquema y tablas
/docker                 -> Dockerfiles y configuración SSL de Apache
```

## 4. Flujo de funcionamiento
1. Usuario accede a `index.php` (registro / login).
2. Una vez autenticado → `principal.php`.
3. Añadir productos al carrito (AJAX → cart_ajax.php).
4. Carrito y facturas se refrescan periódicamente sin recarga.
5. Pago PayPal (sandbox): cuando se aprueba:
   - Se crea una factura (tabla invoices).
   - Se mueve el contenido del carrito a purchase_history con invoice_id.
   - Se vacía el carrito.
   - Se permite descargar PDF (invoice.js + invoice_ajax.php).
6. Usuario administrador (`admin`) puede entrar a `admin.php` para ver:
   - Usuarios, compras totales, dinero gastado.
   - Facturas por usuario.

## 5. Base de datos (tablas principales)
- `users`: credenciales (hash de contraseña), email, timestamps.
- `cart`: estado actual del carrito por usuario.
- `purchase_history`: líneas de productos adquiridos (con invoice_id cuando hay factura).
- `invoices`: metadatos de la factura (número único, total).

Relación clave:
```
users (1) -- (N) cart
users (1) -- (N) purchase_history
users (1) -- (N) invoices
invoices (1) -- (N) purchase_history (via invoice_id)
```

## 6. Seguridad implementada
- Cookies de sesión con flags: Secure, HttpOnly, SameSite=Lax.
- Regeneración de token de aplicación (`login_token`) tras login/registro.
- Token CSRF propio (meta tag) validado en peticiones POST de AJAX.
- Validación básica y saneamiento: trim, límite de longitud de producto, conversión a float segura.
- Conexión sobre HTTPS (certificado autofirmado para desarrollo; acepta el aviso del navegador la primera vez).
- Acceso restringido a admin (`$_SESSION['usuari'] === 'admin'`).
- No se expone ningún hash de contraseña (se almacena con `password_hash`, no se devuelve al cliente).
- Cierre de sesión de PayPal (se abre `https://www.paypal.com/signout` en una pestaña nueva).

Cabeceras HTTP (en el archivo `docker/apache-ssl.conf`):
- `X-Frame-Options: SAMEORIGIN` → evita clickjacking en iframes de dominios de terceros.
- `Content-Security-Policy: frame-ancestors 'self'` → refuerza que solo la propia página puede embeber contenido.
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` (solo con HTTPS) → fuerza el uso de HTTPS en el navegador durante 1 año.
- `X-Content-Type-Options: nosniff` → evita que el navegador “adivine” tipos de contenido.
- Descompresión desactivada para contenidos dinámicos (`mod_deflate` desactivado + `SetEnvIfNoCase ... no-gzip`) → minimiza el riesgo de ataques como BREACH sobre respuestas sensibles.

Servidor web:
- `a2enmod ssl headers` para habilitar SSL y cabeceras.
- `a2dismod deflate` para deshabilitar compresión a nivel global.
- Certificado y clave SSL generados en el `Dockerfile` con OpenSSL (autofirmado para uso local).

## 7. PayPal (Sandbox)
- SDK cargado dinámicamente cuando hay importe > 0.
- createOrder → amount = total actual.
- onApprove → captura simulada, después AJAX `checkout`.
- Eliminación de items del carrito y generación de factura en el mismo flujo.
- El botón se re-renderiza automáticamente si el carrito cambia (o desaparece si está vacío).

## 8. Facturación PDF
- Endpoint `invoice_ajax.php` devuelve JSON con datos: empresa simulada, cliente, líneas, totales.
- `invoice.js` construye el PDF con jsPDF (cabecera, tabla, total).
- Nombre de archivo: `Factura_<número>.pdf`.

## 9. Panel de administración
- Vista: `admin.php`.
- Lista de usuarios con:
  - Username, Email, Fecha de alta.
  - Número de compras (filas en purchase_history).
  - Dinero gastado (SUM precio * cantidad).
  - Facturas (toggle + botones de descarga).
- No muestra contraseñas ni hashes.
- Reutiliza el estilo general (clases de la página principal).

## 10. Actualización dinámica (sin recarga)
- `cart.js` hace polling cada 20s + refresco en focus/visibility.
- Recalcula carrito + facturas vía `cart_ajax.php`.
- Mantiene sincronizado CSRF si el backend lo rota.
- PayPal re-render con pequeño retraso para asegurar integridad del DOM.

## 11. Docker
- `frontend.Dockerfile`: Apache + PHP + SSL + código.
- `db.Dockerfile`: MySQL + script inicial (`init.sql`).
- `docker-compose.yml`: red bridge, volumen persistente `db_data`.
- Puerto expuesto 443 → acceso vía https://localhost/

## 12. Ejecución con Docker (Windows / PowerShell)
Requisitos: Docker Desktop con Docker Compose v2.

1) Construir y arrancar (en segundo plano):
```powershell
docker compose up --build -d
```

2) Ver estado de contenedores:
```powershell
docker compose ps
```

3) Acceder a la aplicación: https://localhost/  
   - El certificado es autofirmado: acepta el aviso del navegador la primera vez.

4) Crear un usuario (puedes crear `admin` si quieres acceder al panel de administración).

5) Añadir productos, probar el pago (Sandbox) y descargar la factura.

6) Parar y borrar contenedores y volumen de datos (opcional):
```powershell
docker compose down -v
```

## 13. Configuración PayPal Sandbox
1) Crea una App en https://developer.paypal.com/ y obtén tu CLIENT_ID (Sandbox).

2) Edita el archivo `frontend/src/paypal-sandbox.js` y sustituye literalmente `TU_CLIENT_ID` por tu Client ID:
   ```js
   s.src = "https://www.paypal.com/sdk/js?client-id=TU_CLIENT_ID&currency=EUR&intent=capture";
   ```
   - Puedes cambiar `currency=EUR` si lo necesitas.
   - En entorno de producción, configura el Client ID de Live y utilízalo de forma segura (variables de entorno / build) para inyectarlo.

3) Usa cuentas Sandbox (Buyer) para probar los pagos.

4) Cierre de sesión de PayPal: el flujo abre `https://www.paypal.com/signout` para limpiar la sesión de PayPal.

## 14. Parámetros y puertos
- HTTPS: 443 (contenedor web).
- MySQL: host `db`, BD `ecommerce_db`.
- Certificado: autofirmado (dev).

## 15. Solución de problemas
- El botón de PayPal no aparece: comprueba que hay productos y Client ID válido.
- Error CSRF: meta token existente + enviado en el body POST.
- El carrito no refresca: revisa la consola (errores de red), sesión válida.
- El PDF no se descarga: comprueba la carga de jsPDF (CDN).
- Cookies seguras: es necesario acceder por HTTPS (ya configurado).

## 16. Comandos Docker útiles (PowerShell)
Logs de los servicios:
```powershell
docker logs -f web_app
docker logs -f mysql_db
```

Abrir shell MySQL dentro del contenedor:
```powershell
docker exec -it mysql_db mysql -uroot -prootpassword ecommerce_db
```

## 17. Notas finales
Proyecto demostrativo (educativo). Para producción: TLS real, secretos en variables de entorno, modo Live de PayPal y seguridad web.

---
Autor: v0id0100
