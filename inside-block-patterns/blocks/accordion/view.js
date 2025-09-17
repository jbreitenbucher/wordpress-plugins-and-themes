
/* Front-end behavior for the accordion */
(function(){
	if (typeof window === 'undefined') return;
	function enhance(root){
		if (!root || root.dataset.ibpAccordionEnhanced === '1') return;
		root.dataset.ibpAccordionEnhanced = '1';
		const buttons = root.querySelectorAll('.ibp-accordion__button');
		buttons.forEach(btn => {
			btn.addEventListener('click', () => {
				const expanded = btn.getAttribute('aria-expanded') === 'true';
				btn.setAttribute('aria-expanded', String(!expanded));
				const panelId = btn.getAttribute('aria-controls');
				const panel = panelId && document.getElementById(panelId);
				if (panel) panel.hidden = expanded;
				const item = btn.closest('.ibp-accordion__item');
				if (item) item.classList.toggle('is-open', !expanded);
			});
			// Initialize hidden state
			const expanded = btn.getAttribute('aria-expanded') === 'true';
			const panelId = btn.getAttribute('aria-controls');
			const panel = panelId && document.getElementById(panelId);
			if (panel) panel.hidden = !expanded;
		});
	}
	function ready(fn){document.readyState!=='loading'?fn():document.addEventListener('DOMContentLoaded',fn);}
	ready(function(){
		document.querySelectorAll('.ibp-accordion').forEach(enhance);
	});
})();
