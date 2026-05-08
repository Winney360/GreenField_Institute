// Tiny AJAX helper used by every page. Wraps fetch() with JSON
// encoding, the X-Requested-With header (so PHP's is_ajax() flag
// flips on), and a single error-handling pathway.

window.api = {
    async get(url) {
        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        return parse(r);
    },
    async post(url, body) {
        const r = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body || {})
        });
        return parse(r);
    }
};

async function parse(response) {
    let data = {};
    try { data = await response.json(); } catch (e) { /* non-JSON */ }
    if (response.status === 401) {
        // Session has expired or never existed — bounce to login.
        window.location.href = '/index.html';
        return { ok: false, error: 'not_authenticated' };
    }
    return data;
}

// Reusable flash-message helper.
window.flash = function (containerId, message, kind = 'info') {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = `<div class="alert ${kind}">${escapeHtml(message)}</div>`;
    if (kind === 'success') setTimeout(() => (el.innerHTML = ''), 3500);
};

window.escapeHtml = function (s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
};
