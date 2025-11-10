document.addEventListener('DOMContentLoaded', function () {
    function ensureJsPDF(cb) {
        if (window.jspdf && window.jspdf.jsPDF) return cb();
        const s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
        s.onload = cb;
        document.body.appendChild(s);
    }

    function generatePdf(data) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'pt', format: 'a4' });
        const margin = 40, lineH = 18;
        let y = margin;

        doc.setFontSize(16); doc.setFont('helvetica', 'bold');
        doc.text(data.company.name, margin, y); y += lineH;
        doc.setFontSize(10); doc.setFont('helvetica', 'normal');
        doc.text(`CIF/NIF: ${data.company.vat}`, margin, y); y += lineH;
        doc.text(data.company.address, margin, y); y += lineH;
        doc.text(data.company.email, margin, y); y += lineH * 2;

        doc.setFont('helvetica', 'bold'); doc.setFontSize(12);
        doc.text(`Factura: ${data.invoice.number}`, margin, y);
        doc.setFont('helvetica', 'normal');
        doc.text(`Data: ${new Date(data.invoice.created_at).toLocaleString()}`, margin + 250, y);
        y += lineH * 1.5;

        doc.setFont('helvetica', 'bold'); doc.text('Client', margin, y); y += lineH;
        doc.setFont('helvetica', 'normal');
        doc.text(`Nom: ${data.customer.name}`, margin, y); y += lineH;
        doc.text(`Email: ${data.customer.email}`, margin, y); y += lineH * 1.5;

        doc.setFont('helvetica', 'bold');
        doc.text('Producte', margin, y);
        doc.text('Preu', margin + 260, y);
        doc.text('Qty', margin + 320, y);
        doc.text('Subtotal', margin + 380, y);
        y += lineH; doc.setDrawColor(0); doc.line(margin, y, 555, y); y += lineH;

        doc.setFont('helvetica', 'normal');
        data.items.forEach(it => {
            doc.text(it.name, margin, y);
            doc.text((it.price).toFixed(2) + ' €', margin + 260, y);
            doc.text(String(it.qty), margin + 320, y);
            doc.text((it.subtotal).toFixed(2) + ' €', margin + 380, y);
            y += lineH;
            if (y > 770) { doc.addPage(); y = margin; }
        });

        y += lineH; doc.line(margin, y, 555, y); y += lineH;
        doc.setFont('helvetica', 'bold');
        doc.text('TOTAL:', margin + 300, y);
        doc.text((data.invoice.total).toFixed(2) + ' €', margin + 380, y);

        doc.save(`Factura_${data.invoice.number}.pdf`);
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.download-invoice-btn');
        if (!btn) return;
        e.preventDefault();

        const invoiceId = btn.getAttribute('data-invoice-id');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch('src/invoice_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `invoice_id=${encodeURIComponent(invoiceId)}&csrf_token=${encodeURIComponent(csrf)}`
        })
        .then(r => r.json())
        .then(resp => {
            if (!resp.success) return;
            ensureJsPDF(() => generatePdf(resp));
        })
        .catch(() => {});
    });
});
