// ---------------------------------------------------------------
// Auth page controller — runs the login ↔ signup transition,
// wires the password show/hide buttons, and submits each form.
// ---------------------------------------------------------------

(function () {
    const ANIM_MS = 3500;
    let isAnimating = false;

    // --- Mode switching with the choreographed animation -------
    function setInitialMode() {
        // URL hash decides which view loads first. Defaults to login.
        const target = window.location.hash === '#signup' ? 'signup' : 'login';
        document.body.classList.add('mode-' + target);
    }

    function switchMode(target) {
        if (isAnimating) return;
        const body = document.body;
        const current = body.classList.contains('mode-login') ? 'login' : 'signup';
        if (current === target) return;

        isAnimating = true;
        body.classList.add('is-going-to-' + target);

        // Midpoint of the animation (300 ms): the form panel is off-screen.
        // This is when we swap the mode class so the side-change is invisible.
        setTimeout(function () {
            body.classList.remove('mode-' + current);
            body.classList.add('mode-' + target);
        }, ANIM_MS / 2);

        // Animation finished: drop the transitional class and update the URL.
        setTimeout(function () {
            body.classList.remove('is-going-to-' + target);
            isAnimating = false;
            history.replaceState(null, '', '#' + target);
        }, ANIM_MS);
    }

    // --- Password show/hide ------------------------------------
    function wirePasswordToggles() {
        document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = btn.parentElement.querySelector('input');
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                btn.classList.toggle('is-visible', !showing);
                btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            });
        });
    }

    // --- Form submissions --------------------------------------
    function wireLoginForm() {
        const form = document.getElementById('loginForm');
        if (!form) return;
        const btn = form.querySelector('button[type="submit"]');
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const emailEl = document.getElementById('loginEmail');
            const passEl  = document.getElementById('loginPassword');
            if (!emailEl || !passEl) {
                flash('msg', 'Login form is missing a field. Please refresh and try again.', 'error');
                return;
            }
            btn.disabled = true; btn.textContent = 'Signing in…';
            const res = await api.post('api/login.php', {
                email:    (emailEl.value || '').trim(),
                password: passEl.value || ''
            });
            if (res.ok) {
                window.location.href = res.redirect;
            } else {
                flash('msg', res.error || 'Login failed.', 'error');
                btn.disabled = false; btn.textContent = 'Login';
            }
        });
    }

    function wireSignupForm() {
        const form = document.getElementById('signupForm');
        if (!form) return;
        const btn = form.querySelector('button[type="submit"]');
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            // Look up fields explicitly by id so the code doesn't depend on
            // form.{name} property access, which can break if the input's
            // name attribute is missing, renamed, or shadowed.
            const emailEl  = document.getElementById('signupEmail');
            const regnumEl = document.getElementById('signupRegNum');
            const passEl   = document.getElementById('signupPassword');
            if (!emailEl || !regnumEl || !passEl) {
                flash('msg', 'Sign-up form is missing a field. Please refresh and try again.', 'error');
                return;
            }
            btn.disabled = true; btn.textContent = 'Signing up…';
            const res = await api.post('api/register.php', {
                email:               (emailEl.value || '').trim(),
                registration_number: (regnumEl.value || '').trim(),
                password:            passEl.value || ''
            });
            if (res.ok) {
                window.location.href = res.redirect;
            } else {
                flash('msg', res.error || 'Sign up failed.', 'error');
                btn.disabled = false; btn.textContent = 'Sign up';
            }
        });
    }

    // --- Wire up the switch links ------------------------------
    function wireSwitchLinks() {
        document.querySelectorAll('[data-switch-to]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                switchMode(link.dataset.switchTo);
            });
        });
    }

    // --- Boot --------------------------------------------------
    setInitialMode(); // sets mode class before first paint
    document.addEventListener('DOMContentLoaded', function () {
        wirePasswordToggles();
        wireLoginForm();
        wireSignupForm();
        wireSwitchLinks();
    });
})();
