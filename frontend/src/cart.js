document.addEventListener('DOMContentLoaded', function () {
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function reRenderPaypalDeferred() {
        if (window.paypalRenderIfNeeded) {
            setTimeout(() => window.paypalRenderIfNeeded(), 10);
        }
    }

    function refreshCartHtmlAndPaypal(html, newToken, historyHtml) {
        const cartContent = document.getElementById('cart-content');
        if (!cartContent) return;
        cartContent.innerHTML = html;
        if (historyHtml) {
            const historyContent = document.getElementById('history-content');
            if (historyContent) historyContent.innerHTML = historyHtml;
        }
        if (newToken) {
            csrfToken = newToken;
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) meta.setAttribute('content', newToken);
        }
        reRenderPaypalDeferred();
    }

    function safeCsrf() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) csrfToken = meta.getAttribute('content') || csrfToken;
        return csrfToken;
    }

    document.querySelectorAll('.add-to-cart-btn').forEach(function(btn) {
        btn.onclick = function () {
            const producte = btn.getAttribute('data-producte');
            const preu = btn.getAttribute('data-preu');
            btn.disabled = true;
            fetch('src/cart_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `producte=${encodeURIComponent(producte)}&preu=${encodeURIComponent(preu)}&afegir=1&csrf_token=${encodeURIComponent(safeCsrf())}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    refreshCartHtmlAndPaypal(data.cart_html, data.csrf_token, data.history_html);
                }
            })
            .catch(() => {})
            .finally(() => { btn.disabled = false; });
        };
    });

    const cartContent = document.getElementById('cart-content');
    if (cartContent) {
        cartContent.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-from-cart-btn');
            if (!btn) return;
            e.preventDefault();
            const producte = btn.getAttribute('data-producte');
            fetch('src/cart_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `producte=${encodeURIComponent(producte)}&treure=1&csrf_token=${encodeURIComponent(safeCsrf())}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    refreshCartHtmlAndPaypal(data.cart_html, data.csrf_token, data.history_html);
                }
            })
            .catch(() => {});
        });
    }

    const logoutBtn = document.querySelector('.logout-form button[name="logout"]');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            try { window.open('https://www.paypal.com/signout', '_blank', 'noopener'); } catch (e) {}
        });
    }

    window.forceCartRefresh = fetchCartAndHistory;

    if (window.paypalRenderIfNeeded) window.paypalRenderIfNeeded();
    window.forcePaypalRender = () => reRenderPaypalDeferred();
});
