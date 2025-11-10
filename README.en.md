
# My Web Project

## 1. Overview
Basic e-commerce web application with:
- User registration and login.
- Persistent cart linked to a database.
- Payment gateway (simulation) via PayPal Sandbox.
- Invoice (PDF) generation after checkout.
- Downloadable invoice history.
- Admin dashboard to monitor user activity.
All served with PHP + Apache over HTTPS (self-signed certificate) and MySQL.

## 2. Technologies and Rationale
- PHP 8.2: easy to integrate with Apache, suitable for rapid prototyping.
- Apache + SSL: classic environment, easy to launch with Docker; HTTPS required for secure cookies.
- MySQL: structured, supports relations, and simple initialization with SQL script.
- JavaScript (ES6): for dynamic AJAX (cart/invoice updates and PayPal).
- PayPal SDK: standard for integrating real/sandbox payments.
- jsPDF: client-side invoice generation without server dependencies.
- Docker: reproducible environment (frontend + DB) avoiding local manual setups.

## 3. Folder Structure (Summary)
```
/frontend/public        -> Public files (index.php, principal.php, admin.php)
/frontend/src           -> PHP logic (register_logic, principal_logic, admin_logic, AJAX)
/backend/src            -> init.sql to create schema and tables
/docker                 -> Dockerfiles and Apache SSL config
```

## 4. How It Works
1. User accesses `index.php` (register / login).
2. Once authenticated → `principal.php`.
3. Add products to the cart (AJAX → cart_ajax.php).
4. Cart and invoices refresh periodically without reload.
5. PayPal payment (sandbox): when approved:
   - An invoice is created (invoices table).
   - Cart contents are moved to purchase_history with invoice_id.
   - The cart is emptied.
   - PDF can be downloaded (invoice.js + invoice_ajax.php).
6. Admin user (`admin`) can access `admin.php` to view:
   - Users, total purchases, money spent.
   - Invoices per user.

## 5. Database (Main Tables)
- `users`: credentials (password hash), email, timestamps.
- `cart`: current cart state per user.
- `purchase_history`: lines of purchased products (with invoice_id when invoiced).
- `invoices`: invoice metadata (unique number, total).

Key relationship:
```
users (1) -- (N) cart
users (1) -- (N) purchase_history
users (1) -- (N) invoices
invoices (1) -- (N) purchase_history (via invoice_id)
```

## 6. Security Implemented
- Session cookies with flags: Secure, HttpOnly, SameSite=Lax.
- Regeneration of application token (`login_token`) after login/register.
- Custom CSRF token (meta tag) validated on AJAX POST requests.
- Basic validation and sanitization: trim, product name length limit, safe float conversion.
- Connection over HTTPS (self-signed certificate for development, accept browser warning on first access).
- Admin access restricted (`$_SESSION['usuari'] === 'admin'`).
- No password hashes are exposed (stored with `password_hash`, never returned to client).
- PayPal logout (opens `https://www.paypal.com/signout` in a new tab).

HTTP headers (in `docker/apache-ssl.conf`):
- `X-Frame-Options: SAMEORIGIN` → prevents clickjacking in third-party iframes.
- `Content-Security-Policy: frame-ancestors 'self'` → ensures only this page can embed content.
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` (HTTPS only) → enforces HTTPS for 1 year in browser.
- `X-Content-Type-Options: nosniff` → prevents browser from guessing content types.
- Compression disabled for dynamic content (`mod_deflate` disabled + `SetEnvIfNoCase ... no-gzip`) → reduces risk of BREACH attacks on sensitive responses.

Web server:
- `a2enmod ssl headers` to enable SSL and headers.
- `a2dismod deflate` to disable global compression.
- SSL certificate and key generated in Dockerfile with OpenSSL (self-signed for local use).

## 7. PayPal (Sandbox)
- SDK loaded dynamically when amount > 0.
- createOrder → amount = current total.
- onApprove → simulated capture, then AJAX `checkout`.
- Cart items removed and invoice generated in the same flow.
- Button re-renders automatically if cart changes (or disappears if empty).

## 8. PDF Invoicing
- Endpoint `invoice_ajax.php` returns JSON with: fake company, client, lines, totals.
- `invoice.js` builds PDF with jsPDF (header, table, total).
- File name: `Invoice_<number>.pdf`.

## 9. Admin Panel
- View: `admin.php`.
- User list with:
  - Username, Email, Registration date.
  - Number of purchases (purchase_history rows).
  - Money spent (SUM price * quantity).
  - Invoices (toggle + download buttons).
- Does not show passwords or hashes.
- Reuses general style (main page classes).

## 10. Dynamic Updates (No Reload)
- `cart.js` polls every 20s + refreshes on focus/visibility.
- Recalculates cart + invoices via `cart_ajax.php`.
- Keeps CSRF in sync if backend rotates it.
- PayPal re-render with small delay to ensure DOM integrity.

## 11. Docker
- `frontend.Dockerfile`: Apache + PHP + SSL + code.
- `db.Dockerfile`: MySQL + initial script (`init.sql`).
- `docker-compose.yml`: bridge network, persistent volume `db_data`.
- Port 443 exposed → access via https://localhost/

## 12. Running with Docker (Windows / PowerShell)
Prerequisites: Docker Desktop with Docker Compose v2.

1) Build and start (in background):
```powershell
docker compose up --build -d
```

2) Check container status:
```powershell
docker compose ps
```

3) Access the app: https://localhost/
   - The certificate is self-signed: accept the browser warning the first time.

4) Create a user (you can create `admin` to access the admin panel).

5) Add products, test payment (Sandbox), download the invoice.

6) Stop and remove containers and data volume (optional):
```powershell
docker compose down -v
```

## 13. PayPal Sandbox Configuration
1) Create an App at https://developer.paypal.com/ and get your CLIENT_ID (Sandbox).

2) Edit the file `frontend/src/paypal-sandbox.js` and literally replace `TU_CLIENT_ID` with your Client ID:
   ```js
   s.src = "https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=EUR&intent=capture";
   ```
   - You can change `currency=EUR` if needed.
   - In production, configure the Live Client ID and use a secure method (env variables / build) to inject it.

3) Use Sandbox (Buyer) accounts to test payments.

4) PayPal logout: the flow opens `https://www.paypal.com/signout` to clear the PayPal session.

## 14. Parameters and Ports
- HTTPS: 443 (web container).
- MySQL: host `db`, DB `ecommerce_db`.
- Certificate: self-signed (dev).

## 15. Troubleshooting
- PayPal button not showing: check there are products and valid client-id.
- CSRF error: meta token exists + sent in POST body.
- Cart not refreshing: check console (network errors), valid session.
- PDF not downloading: check jsPDF (CDN) is loaded.
- Secure cookies: must access via HTTPS (already configured).

## 16. Useful Docker Commands (PowerShell)
Service logs:
```powershell
docker logs -f web_app
docker logs -f mysql_db
```

Open MySQL shell inside the container:
```powershell
docker exec -it mysql_db mysql -uroot -prootpassword ecommerce_db
```

## 17. Final Notes
Demo project (educational). For production: real TLS, secrets in environment variables, PayPal live mode, and web security.

---
Author: v0id0100
