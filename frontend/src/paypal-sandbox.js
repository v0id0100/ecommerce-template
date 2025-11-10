document.addEventListener('DOMContentLoaded', function () {
    if (window.paypalRenderIfNeeded) {
        window.paypalRenderIfNeeded();
    } else {
        window.paypalRenderIfNeeded = makePaypalRenderer();
        window.paypalRenderIfNeeded();
    }
});

function makePaypalRenderer() {
    let loading = false;

    function ensureSdkLoaded(cb) {
        if (window.paypal) return cb();
        if (loading) {
            const iv = setInterval(() => {
                if (window.paypal) { clearInterval(iv); cb(); }
            }, 100);
            return;
        }
        loading = true;
        const s = document.createElement('script');
        s.src = "https://www.paypal.com/sdk/js?client-id=TU_CLIENT_ID&currency=EUR&intent=capture";
        s.onload = () => { loading = false; cb(); };
        document.body.appendChild(s);
    }

    function render() {
        const container = document.getElementById('paypal-button-container');
        const totalInput = document.getElementById('paypal-total');
        if (!container) return;
        container.innerHTML = "";
        const total = totalInput ? parseFloat(totalInput.value) : 0;
        if (!total || total <= 0) return;

        ensureSdkLoaded(() => {
            if (!window.paypal) return;
            window.paypal.Buttons({
                style: { layout: 'horizontal', color: 'gold', shape: 'rect', label: 'paypal' },
                createOrder: (data, actions) => actions.order.create({
                    purchase_units: [{ amount: { value: total.toFixed(2) } }]
                }),
                onApprove: (data, actions) => actions.order.capture().then(details => {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    fetch('src/cart_ajax.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `checkout=1&csrf_token=${encodeURIComponent(csrfToken)}`
                    })
                    .then(r => r.json())
                    .then(resp => {
                        if (!resp.success) return;
                        const cartContent = document.getElementById('cart-content');
                        if (cartContent) cartContent.innerHTML = resp.cart_html;
                        const historyContent = document.getElementById('history-content');
                        if (historyContent && resp.history_html) historyContent.innerHTML = resp.history_html;
                        if (resp.csrf_token) {
                            const meta = document.querySelector('meta[name="csrf-token"]');
                            if (meta) meta.setAttribute('content', resp.csrf_token);
                        }
                        window.paypalRenderIfNeeded();
                        alert('Pagament completat i factura generada. Pots descarregar-la a Historial de factures.');
                    })
                    .catch(() => {});
                })
            }).render('#paypal-button-container');
        });
    }

    return render;
}
