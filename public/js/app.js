// ============================================================
// AgriCoop ERP - Main JS
// ============================================================

// Sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('mainContent').classList.toggle('collapsed');
});

// AJAX helper
const Ajax = {
    post(url, data, cb) {
        const form = new FormData();
        if (data instanceof FormData) {
            fetch(url, { method: 'POST', body: data })
                .then(r => r.json()).then(cb).catch(e => console.error(e));
        } else {
            Object.entries(data).forEach(([k, v]) => form.append(k, v));
            fetch(url, { method: 'POST', body: form })
                .then(r => r.json()).then(cb).catch(e => console.error(e));
        }
    },
    get(url, cb) {
        fetch(url).then(r => r.json()).then(cb).catch(e => console.error(e));
    }
};

// Toast notification
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer') || (() => {
        const el = document.createElement('div');
        el.id = 'toastContainer';
        el.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;';
        document.body.appendChild(el);
        return el;
    })();

    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible shadow`;
    toast.style.cssText = 'min-width:280px;animation:slideIn 0.3s ease;';
    toast.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// Real-time stats polling (every 30s)
function pollStats() {
    Ajax.get(BASE_URL + '/dashboard/stats', data => {
        const badge = document.getElementById('pendingBadge');
        if (badge && data.pending_approvals > 0) {
            badge.textContent = data.pending_approvals + ' pending';
        }
    });
}
if (typeof BASE_URL !== 'undefined') {
    pollStats();
    setInterval(pollStats, 30000);
}

// Generic form submit via AJAX
document.querySelectorAll('form[data-ajax]').forEach(form => {
    form.addEventListener('submit', e => {
        e.preventDefault();
        const btn = form.querySelector('[type=submit]');
        const originalText = btn?.innerHTML;
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...'; }

        Ajax.post(form.action, new FormData(form), res => {
            if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
            showToast(res.message, res.success ? 'success' : 'danger');
            if (res.success) {
                const modal = form.closest('.modal');
                if (modal) bootstrap.Modal.getInstance(modal)?.hide();
                if (form.dataset.reload) setTimeout(() => location.reload(), 800);
            }
        });
    });
});

// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
});

// Dynamic item rows (for PO/SO forms)
window.addItemRow = function(tableId) {
    const tbody = document.getElementById(tableId);
    const row = tbody.querySelector('tr').cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    tbody.appendChild(row);
};

window.removeItemRow = function(btn) {
    const tbody = btn.closest('tbody');
    if (tbody.querySelectorAll('tr').length > 1) btn.closest('tr').remove();
};

window.calcRowTotal = function(input) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelector('.qty')?.value || 0);
    const price = parseFloat(row.querySelector('.price')?.value || 0);
    const total = row.querySelector('.total');
    if (total) total.value = (qty * price).toFixed(2);
};

// ── Print Page ────────────────────────────────────────────
window.printPage = function(title) {
    // Convert all canvas charts to images first
    const canvases = document.querySelectorAll('.page-content canvas');
    canvases.forEach(canvas => {
        if (canvas.id === '__replaced') return;
        const img = document.createElement('img');
        img.src = canvas.toDataURL('image/png');
        img.style.cssText = 'width:100%;max-height:280px;object-fit:contain;display:block';
        img.dataset.replacedCanvas = canvas.id;
        canvas.parentNode.insertBefore(img, canvas);
        canvas.style.display = 'none';
    });

    const content = document.querySelector('.page-content').innerHTML;
    const pageTitle = title || document.title;

    const win = window.open('', '_blank', 'width=1000,height=750');
    win.document.write(`<!DOCTYPE html><html><head>
        <meta charset="UTF-8"><title>${pageTitle}</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
        <style>
            body{font-family:'Segoe UI',sans-serif;padding:20px;background:#fff}
            .print-header{border-bottom:2px solid #4caf50;padding-bottom:10px;margin-bottom:18px}
            .no-print,.btn,.modal,.form-control,.form-select,.nav-tabs,.nav-item,
            [data-bs-toggle],[data-bs-target],.topbar,.sidebar{display:none!important}
            .stat-card{border:1px solid #ddd;border-radius:8px;padding:14px;margin-bottom:8px}
            .stat-value{font-size:1.4rem;font-weight:700}
            .stat-label{font-size:.75rem;text-transform:uppercase;color:#666}
            .table-card{border:1px solid #ddd;border-radius:6px;overflow:hidden;margin-bottom:14px}
            .card-header{background:#f8f9fa;padding:10px 14px;font-weight:600;border-bottom:1px solid #ddd}
            .table th{font-size:.75rem;text-transform:uppercase;color:#666}
            .badge{padding:2px 7px;border-radius:4px;font-size:.72rem}
            .badge-approved,.bg-success{background:#198754!important;color:#fff}
            .badge-pending,.bg-warning{background:#ffc107!important;color:#000}
            .badge-rejected,.bg-danger{background:#dc3545!important;color:#fff}
            .bg-info{background:#0dcaf0!important;color:#000}
            .bg-secondary{background:#6c757d!important;color:#fff}
            .text-success{color:#198754!important}.text-danger{color:#dc3545!important}
            .text-muted{color:#666!important}.fw-bold{font-weight:700}
            .d-none{display:none!important}
            @media print{body{padding:0}.no-print{display:none!important}}
        </style>
    </head><body>
        <div class="print-header d-flex justify-content-between align-items-start">
            <div><h4 style="color:#1a2e1a;margin:0">${pageTitle}</h4>
            <div style="color:#666;font-size:.8rem">Printed: ${new Date().toLocaleString('en-PH')}</div></div>
            <div style="font-size:.8rem;color:#666">AgriCoop ERP</div>
        </div>
        ${content}
    </body></html>`);
    win.document.close();

    // Restore canvases
    canvases.forEach(canvas => {
        canvas.style.display = '';
        const img = document.querySelector(`[data-replaced-canvas="${canvas.id}"]`);
        if (img) img.remove();
    });

    win.focus();
    setTimeout(() => win.print(), 700);
};
