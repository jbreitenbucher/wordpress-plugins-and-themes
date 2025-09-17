/*(function () {
  function enhance(root) {
    if (!root || root.dataset.ibpAccordionEnhanced === '1') return;
    root.dataset.ibpAccordionEnhanced = '1';

    const items = root.querySelectorAll(':scope .ibp-accordion__item');
    let uid = 0;
    items.forEach((item) => {
      const heading = item.querySelector(':scope > h1, :scope > h2, :scope > h3, :scope > h4, :scope > h5, :scope > h6');
      const panel = item.querySelector(':scope .ibp-accordion__panel');
      if (!heading || !panel) return;

      if (heading.querySelector('button.ibp-accordion__button')) return;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ibp-accordion__button';
      btn.innerHTML = heading.innerHTML || 'Toggle';
      heading.innerHTML = '';
      heading.appendChild(btn);

      const id = panel.id || `ibp-acc-panel-${Date.now()}-${uid++}`;
      panel.id = id;
      btn.setAttribute('aria-controls', id);
      const isOpen = item.classList.contains('is-open');
      btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      if (!isOpen) panel.hidden = true;

      btn.addEventListener('click', () => {
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!expanded));
        panel.hidden = expanded;
        item.classList.toggle('is-open', !expanded);
      });
    });
  }

  const ready = (fn) => document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn);
  ready(() => document.querySelectorAll('.ibp-accordion').forEach(enhance));
})();*/