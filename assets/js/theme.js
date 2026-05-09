// Theme handler — runs as early as possible to avoid a flash of light mode
// when the user has dark saved. Reads from localStorage first, falls back to
// the OS preference. Writes the choice to <html data-theme="..."> so CSS swaps.
(function () {
    const KEY = 'gf-theme';
    const stored = localStorage.getItem(KEY);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initial = stored || (prefersDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', initial);

    // Wire up the toggle button once the DOM is ready.
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.querySelector('.theme-toggle');
        if (!btn) return;
        btn.addEventListener('click', function () {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem(KEY, next);
        });
    });
})();
