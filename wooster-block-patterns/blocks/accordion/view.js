
/* Front-end behavior for the accordion (wbp-only) */
(function () {
	if (typeof window === 'undefined' || typeof document === 'undefined') return;

	var ROOT_SEL  = '.wbp-accordion';
	var BTN_SEL   = '.wbp-accordion__button';
	var ITEM_SEL  = '.wbp-accordion__item';
	var PANEL_SEL = '.wbp-accordion__panel';

	function setExpanded(btn, expanded) {
		btn.setAttribute('aria-expanded', String(expanded));
		var panelId = btn.getAttribute('aria-controls');
		var panel = panelId && document.getElementById(panelId);
		if (panel) panel.hidden = !expanded;

		var item = btn.closest(ITEM_SEL);
		if (item) item.classList.toggle('is-open', !!expanded);
	}

	function onBtnClick(e) {
		var btn = e.currentTarget;
		var root = btn.closest(ROOT_SEL);
		if (!root) return;

		var wasOpen = btn.getAttribute('aria-expanded') === 'true';
		var allowMultiple = (root.dataset && root.dataset.allowMultiple === 'true');

		// If single-open, close siblings before opening this one
		if (!allowMultiple && !wasOpen) {
			root.querySelectorAll(BTN_SEL).forEach(function (b) {
				if (b !== btn) setExpanded(b, false);
			});
		}
		setExpanded(btn, !wasOpen);
	}

	function onKey(e) {
		var key = e.key;
		if (!key) return;

		var btn = e.currentTarget;
		var root = btn.closest(ROOT_SEL);
		if (!root) return;

		var buttons = Array.prototype.slice.call(root.querySelectorAll(BTN_SEL));
		var idx = buttons.indexOf(btn);
		if (idx < 0) return;

		switch (key) {
			case 'Enter':
			case ' ':
				e.preventDefault();
				btn.click();
				break;
			case 'ArrowDown':
				e.preventDefault();
				buttons[(idx + 1) % buttons.length].focus();
				break;
			case 'ArrowUp':
				e.preventDefault();
				buttons[(idx - 1 + buttons.length) % buttons.length].focus();
				break;
			case 'Home':
				e.preventDefault();
				buttons[0].focus();
				break;
			case 'End':
				e.preventDefault();
				buttons[buttons.length - 1].focus();
				break;
		}
	}

	function initRoot(root) {
		if (!root || root.__wbpAccInit) return;
		root.__wbpAccInit = true;

		root.querySelectorAll(BTN_SEL).forEach(function (btn) {
			btn.addEventListener('click', onBtnClick);
			btn.addEventListener('keydown', onKey);

			// Initialize state from aria-expanded
			var expanded = btn.getAttribute('aria-expanded') === 'true';
			setExpanded(btn, expanded);
		});

		// Deep-link support: open targeted panel by hash
		var hash = (location.hash || '').replace(/^#/, '');
		if (hash) {
			try {
				var targetPanel = document.getElementById(hash);
				var btn;
				if (targetPanel && root.contains(targetPanel)) {
					btn = root.querySelector(BTN_SEL + '[aria-controls="' + (window.CSS && CSS.escape ? CSS.escape(hash) : hash) + '"]');
				} else {
					var el = document.getElementById(hash);
					if (el && root.contains(el) && el.matches(BTN_SEL)) btn = el;
				}
				if (btn) {
					var allowMultiple = (root.dataset && root.dataset.allowMultiple === 'true');
					if (!allowMultiple) {
						root.querySelectorAll(BTN_SEL).forEach(function (b) { setExpanded(b, b === btn); });
					} else {
						setExpanded(btn, true);
					}
				}
			} catch (_) { /* ignore */ }
		}
	}

	function enhanceAll() {
		document.querySelectorAll(ROOT_SEL).forEach(initRoot);
	}

	// DOM ready
	if (document.readyState !== 'loading') {
		enhanceAll();
	} else {
		document.addEventListener('DOMContentLoaded', enhanceAll);
	}

	// Observe late-added accordions
	try {
		new MutationObserver(function (muts) {
			muts.forEach(function (m) {
				m.addedNodes && m.addedNodes.forEach(function (node) {
					if (node.nodeType !== 1) return;
					if (node.matches && node.matches(ROOT_SEL)) initRoot(node);
					else if (node.querySelectorAll) node.querySelectorAll(ROOT_SEL).forEach(initRoot);
				});
			});
		}).observe(document.documentElement, { childList: true, subtree: true });
	} catch (_) { /* old browser fallback already covered */ }
})();
