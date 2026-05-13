// ---------------------------------------------------------------
// Mobile/tablet navigation: hamburger ↔ sliding sidebar.
//
// Runs on every page that uses the sidebar layout (body has class
// `has-sidebar`). Wires up the navbar's hamburger button, injects a
// backdrop, and toggles `sidebar-open` on the body — CSS does the rest.
// ---------------------------------------------------------------

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        if (!body.classList.contains('has-sidebar')) return;

        // Inject the backdrop element (used to close the sidebar by tapping outside).
        const backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        backdrop.addEventListener('click', closeSidebar);
        body.appendChild(backdrop);

        // Toggle on hamburger click.
        const btn = document.querySelector('.navbar__hamburger');
        if (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                body.classList.toggle('sidebar-open');
            });
        }

        // Close after navigating from a sidebar link — otherwise it stays
        // open behind the new page on slow connections.
        document.querySelectorAll('.sidebar__nav a, .sidebar__footer a').forEach(function (a) {
            a.addEventListener('click', closeSidebar);
        });

        // Escape key also closes — keyboard accessibility nicety.
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });

        function closeSidebar() {
            body.classList.remove('sidebar-open');
        }
    });
})();
