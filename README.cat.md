# My Web Project

## 1. Visió General
Aplicació web de comerç bàsic amb:
- Registre i inici de sessió d’usuaris.
- Carretó persistent lligat a base de dades.
- Passarel·la de pagament (simulació) via PayPal Sandbox.
- Generació de factures (PDF) després del checkout.
- Historial de factures descarregables.
- Panell d’administració per monitoritzar activitat d’usuaris.
Tot servit amb PHP + Apache sobre HTTPS (certificat autofirmat) i MySQL.

## 2. Tecnologies i Motiu de l’Elecció
- PHP 8.2: senzill d’integrar amb Apache i adequat per prototip ràpid.
- Apache + SSL: entorn clàssic, fàcil d’aixecar amb Docker; HTTPS necessari per cookies segures.
- MySQL: estructurat, suport per relacions i inicialització simple amb script SQL.
- JavaScript (ES6): per AJAX dinàmic (actualització carretó/factures i PayPal).
- PayPal SDK: estàndard per integrar pagaments reals/sandbox.
- jsPDF: generació de factures client-side sense dependències al servidor.
- Docker: entorn reproduïble (frontend + DB) evitant configuracions manuals locals.

## 3. Estructura de Carpetes (Resum)
```
/frontend/public        -> Fitxers accessibles (index.php, principal.php, admin.php)
/frontend/src           -> Lògica PHP (register_logic, principal_logic, admin_logic, AJAX)
/backend/src            -> init.sql per crear esquema i taules
/docker                 -> Dockerfiles i conf SSL Apache
```

## 4. Flux de Funcionament
1. Usuari accedeix a `index.php` (registre / login).
2. Un cop autenticat → `principal.php`.
3. Afegir productes al carretó (AJAX → cart_ajax.php).
4. Carretó i factures es refresquen periòdicament sense recàrrega.
5. Pagament PayPal (sandbox): quan s’aprova:
   - Es crea una factura (taula invoices).
   - Es mou el contingut del carretó a purchase_history amb invoice_id.
   - Es buida el carretó.
   - Es permet descarregar PDF (invoice.js + invoice_ajax.php).
6. Usuari administrador (`admin`) pot entrar a `admin.php` per veure:
   - Usuaris, compres totals, diners gastats.
   - Factures per usuari.

## 5. Base de Dades (Taules Principals)
- `users`: credencials (hash de contrasenya), email, timestamps.
- `cart`: estat actual del carretó per usuari.
- `purchase_history`: línies de productes adquirits (amb invoice_id quan hi ha factura).
- `invoices`: metadades de la factura (número únic, total).

Relació clau:
```
users (1) -- (N) cart
users (1) -- (N) purchase_history
users (1) -- (N) invoices
invoices (1) -- (N) purchase_history (via invoice_id)
```

## 6. Seguretat implementada
- Cookies de sessió amb flags: Secure, HttpOnly, SameSite=Lax.
- Regeneració de token d’aplicació (`login_token`) després de login/registre.
- Token CSRF propi (meta tag) validat a peticions POST d’AJAX.
- Validació bàsica i sanejament: trim, límit de longitud del producte, conversió a float segura.
- Connexió sobre HTTPS (certificat autofirmat per desenvolupament, cal acceptar l’avís del navegador la primera vegada).
- Accés restringit a admin (`$_SESSION['usuari'] === 'admin'`).
- No s’exposa cap hash de contrasenya (s’emmagatzema amb `password_hash`, no es retorna mai al client).
- Tancament de sessió de PayPal (s’obre `https://www.paypal.com/signout` en una pestanya nova).

Capçaleres HTTP (al fitxer `docker/apache-ssl.conf`):
- `X-Frame-Options: SAMEORIGIN` → evita clickjacking en iframes de dominis tercers.
- `Content-Security-Policy: frame-ancestors 'self'` → reforça que només la pròpia pàgina pot embedir contingut.
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` (només amb HTTPS) → força l’ús d’HTTPS al navegador durant 1 any.
- `X-Content-Type-Options: nosniff` → evita que el navegador “endevini” tipus de contingut.
- Descompressió desactivada per a continguts dinàmics (`mod_deflate` desactivat + `SetEnvIfNoCase ... no-gzip`) → minimitza el risc d’atacs com BREACH sobre respostes sensibles.

Servidor web:
- `a2enmod ssl headers` per habilitar SSL i capçaleres.
- `a2dismod deflate` per deshabilitar compressió a nivell global.
- Certificat i clau SSL generats a `Dockerfile` amb OpenSSL (autofirmat per ús local).

## 7. PayPal (Sandbox)
- SDK carregat dinàmicament quan hi ha import > 0.
- createOrder → amount = total actual.
- onApprove → captura simulada, després AJAX `checkout`.
- Eliminació d’items del carretó i generació de factura en el mateix flux.
- El botó es re-renderitza automàticament si el carretó canvia (o desapareix si buit).

## 8. Facturació PDF
- Endpoint `invoice_ajax.php` retorna JSON amb dades: empresa simulada, client, línies, totals.
- `invoice.js` construeix PDF amb jsPDF (capçalera, taula, total).
- Nom arxiu: `Factura_<número>.pdf`.

## 9. Panell d’Administració
- Vista: `admin.php`.
- Llista d’usuaris amb:
  - Username, Email, Data d’alta.
  - Nombre de compres (files purchase_history).
  - Diners gastats (SUM preu * quantitat).
  - Factures (toggle + botons de descàrrega).
- No mostra contrasenyes ni hashes.
- Reutilitza estil general (classes de la pàgina principal).

## 10. Actualització Dinàmica (Sense Reload)
- `cart.js` fa polling cada 20s + refresc on focus/visibility.
- Recalcula carretó + factures via `cart_ajax.php`.
- Manté sincronitzat CSRF si el backend el rota.
- PayPal re-render amb retard petit per assegurar integritat DOM.

## 11. Docker
- `frontend.Dockerfile`: Apache + PHP + SSL + codi.
- `db.Dockerfile`: MySQL + script inicial (`init.sql`).
- `docker-compose.yml`: xarxa bridge, volum persistència `db_data`.
- Port exposat 443 → accés via https://localhost/

## 12. Execució amb Docker (Windows / PowerShell)
Prerequisits: Docker Desktop amb Docker Compose v2.

1) Construir i arrencar (en segon pla):
```powershell
docker compose up --build -d
```

2) Veure l’estat dels contenidors:
```powershell
docker compose ps
```

3) Accedir a l’aplicació: https://localhost/  
   - El certificat és autofirmat: accepta l’avís del navegador la primera vegada.

4) Crear un usuari (pots crear `admin` si vols accedir al panell d’administració).

5) Afegir productes, provar el pagament (Sandbox) i descarregar la factura.

6) Aturar i esborrar contenidors i volum de dades (opcional):
```powershell
docker compose down -v
```

## 13. Configuració PayPal Sandbox
1) Crea una App a https://developer.paypal.com/ i obtén el teu CLIENT_ID (Sandbox).

2) Edita el fitxer `frontend/src/paypal-sandbox.js` i substitueix literalment `TU_CLIENT_ID` pel teu Client ID:
   ```js
   s.src = "https://www.paypal.com/sdk/js?client-id=EL_TEU_CLIENT_ID&currency=EUR&intent=capture";
   ```
   - Pots canviar `currency=EUR` si ho necessites.
   - En entorn de producció, configura el Client ID de Live i utilitza un mètode segur (variables d’entorn / build) per injectar-lo.

3) Usa comptes Sandbox (Buyer) per provar els pagaments.

4) Tancar sessió de PayPal: el flux obre `https://www.paypal.com/signout` per netejar la sessió de PayPal.

## 14. Paràmetres i Ports
- HTTPS: 443 (contenidor web).
- MySQL: host `db`, BD `ecommerce_db`.
- Certificat: autofirmat (dev).

## 15. Troubleshooting
- Botó PayPal no apareix: comprova que hi ha productes i client-id vàlid.
- Error CSRF: meta token existent + enviat al body POST.
- Carretó no refresca: revisa consola (errors xarxa), sessió vàlida.
- PDF no es baixa: comprova càrrega de jsPDF (CDN).
- Cookies segures: cal accedir per HTTPS (ja configurat).

## 16. Comandes Docker útils (PowerShell)
Logs dels serveis:
```powershell
docker logs -f web_app
docker logs -f mysql_db
```

Obrir shell MySQL dins el contenidor:
```powershell
docker exec -it mysql_db mysql -uroot -prootpassword ecommerce_db
```

## 17. Notes Finals
Projecte demostració (educatiu). Per producció: TLS real, secrets en variables d’entorn, mode live PayPal i seguretat web.

---
Autor: v0id0100
