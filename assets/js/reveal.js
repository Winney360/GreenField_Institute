/* Reveal-on-scroll helper.
   Elements with class `reveal` start hidden (CSS), and gain class
   `is-visible` when they cross into the viewport — at which point a
   CSS transition fades + slides them into place.

   Page scripts that inject `.reveal` elements after page load should
   call `reveal.scan()` once the new DOM is in place so the new nodes
   get observed too. Otherwise no manual wiring is needed.

   Respects prefers-reduced-motion: when the user has reduced-motion
   enabled, every .reveal element is shown immediately with no
   animation. */
(function () {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduced || typeof IntersectionObserver === 'undefined') {
        // No animation — make everything visible right away.
        const showAll = () => {
            document.querySelectorAll('.reveal:not(.is-visible)')
                .forEach(el => el.classList.add('is-visible'));
        };
        window.reveal = { scan: showAll };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', showAll);
        } else {
            showAll();
        }
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        }
    }, {
        threshold: 0.08,
        // Trigger slightly before the element fully enters — feels snappier.
        rootMargin: '0px 0px -40px 0px'
    });

    function scan() {
        document.querySelectorAll('.reveal:not(.is-visible)')
            .forEach(el => observer.observe(el));
    }

    window.reveal = { scan };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }
})();
