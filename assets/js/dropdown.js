/* Themed dropdown — replaces <select> when we need full visual control.
   Native <select> only lets you style the trigger; the option panel that
   drops down is rendered by the OS and can't be CSS'd. This is a button
   + ARIA listbox implementation that we CAN style.

   Usage:
       <div class="dropdown" id="foo" data-value="">
         <button class="dropdown__trigger" aria-haspopup="listbox"
                 aria-expanded="false">
           <span class="dropdown__label">Placeholder</span>
           <svg class="dropdown__chevron">...</svg>
         </button>
         <ul class="dropdown__menu" role="listbox" hidden>
           <li class="dropdown__option is-selected" role="option"
               data-value="" aria-selected="true" tabindex="-1">Placeholder</li>
         </ul>
       </div>

       const dd = Dropdown(document.getElementById('foo'));
       dd.addEventListener('change', () => console.log(dd.value));
       dd.addOption('cs', 'Computing');

   API attached to the root element:
       .value         current selected value (string)
       .size          number of options currently in the list
       .addOption(v, text)
       fires CustomEvent('change') on the element when the user picks. */
function Dropdown(root) {
    const trigger = root.querySelector('.dropdown__trigger');
    const menu    = root.querySelector('.dropdown__menu');
    const label   = root.querySelector('.dropdown__label');

    let value     = root.dataset.value ?? '';
    let activeIdx = -1;

    const opts = () => [...menu.querySelectorAll('.dropdown__option')];

    function open() {
        if (!menu.hidden) return;
        menu.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        const list = opts();
        const i = list.findIndex(o => o.dataset.value === value);
        setActive(i < 0 ? 0 : i);
        // Capture phase so we beat anything else listening.
        document.addEventListener('click', onDocClick, true);
        document.addEventListener('keydown', onKey);
    }

    function close() {
        if (menu.hidden) return;
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        document.removeEventListener('click', onDocClick, true);
        document.removeEventListener('keydown', onKey);
    }

    function setActive(i) {
        const list = opts();
        list.forEach((o, idx) => o.classList.toggle('is-active', idx === i));
        if (list[i]) list[i].scrollIntoView({ block: 'nearest' });
        activeIdx = i;
    }

    function setValue(v, fire) {
        if (v === value) return;
        value = v;
        root.dataset.value = v;
        for (const o of opts()) {
            const sel = o.dataset.value === v;
            o.classList.toggle('is-selected', sel);
            o.setAttribute('aria-selected', sel);
            if (sel) label.textContent = o.textContent;
        }
        if (fire) root.dispatchEvent(new CustomEvent('change'));
    }

    function addOption(v, text) {
        const li = document.createElement('li');
        li.className = 'dropdown__option';
        li.setAttribute('role', 'option');
        li.setAttribute('tabindex', '-1');
        li.dataset.value = v;
        li.textContent = text;
        const sel = v === value;
        li.setAttribute('aria-selected', sel ? 'true' : 'false');
        if (sel) li.classList.add('is-selected');
        menu.appendChild(li);
    }

    function onDocClick(e) {
        if (!root.contains(e.target)) close();
    }

    function onKey(e) {
        const list = opts();
        switch (e.key) {
            case 'Escape':
                e.preventDefault(); close(); trigger.focus(); break;
            case 'ArrowDown':
                e.preventDefault();
                setActive(Math.min(list.length - 1, activeIdx + 1));
                break;
            case 'ArrowUp':
                e.preventDefault();
                setActive(Math.max(0, activeIdx - 1));
                break;
            case 'Home':
                e.preventDefault(); setActive(0); break;
            case 'End':
                e.preventDefault(); setActive(list.length - 1); break;
            case 'Enter':
            case ' ':
                e.preventDefault();
                if (list[activeIdx]) {
                    setValue(list[activeIdx].dataset.value, true);
                    close();
                    trigger.focus();
                }
                break;
        }
    }

    trigger.addEventListener('click', () => menu.hidden ? open() : close());
    trigger.addEventListener('keydown', (e) => {
        if (menu.hidden && (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            open();
        }
    });
    menu.addEventListener('click', (e) => {
        const opt = e.target.closest('.dropdown__option');
        if (!opt) return;
        setValue(opt.dataset.value, true);
        close();
        trigger.focus();
    });

    // Sync the label with whatever option is pre-selected in markup.
    const initial = opts().find(o => o.dataset.value === value);
    if (initial) label.textContent = initial.textContent;

    // Expose API on the DOM element so existing patterns like
    // `el.addEventListener('change', ...)` keep working unchanged.
    Object.defineProperty(root, 'value', { get: () => value, configurable: true });
    Object.defineProperty(root, 'size',  { get: () => opts().length, configurable: true });
    root.addOption = addOption;
    return root;
}
