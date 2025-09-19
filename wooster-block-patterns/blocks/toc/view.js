(function(){
	function slugify(text){
		return String(text || '')
			.toLowerCase()
			.normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
			.replace(/[^a-z0-9\s-]/g, '')
			.trim()
			.replace(/\s+/g, '-')
			.replace(/-+/g, '-')
			.slice(0, 80) || 'section';
	}

	function ensureIds(toc){
		if (!toc) return;
		var idsAttr = toc.getAttribute('data-ids');
		var ids = [];
		try { ids = JSON.parse(idsAttr || '[]'); } catch(e){}

		var selector = 'h2.wp-block-heading, h3.wp-block-heading, h4.wp-block-heading, h5.wp-block-heading, h6.wp-block-heading, h2, h3, h4, h5, h6';
		var heads = Array.prototype.slice.call(document.querySelectorAll(selector)).filter(function(h){
			var lvl = parseInt(h.tagName.substring(1), 10);
			return lvl >= 2 && lvl <= 6;
		});

		var i = 0;
		heads.forEach(function(h){
			if (!h.id) {
				var id = ids[i] || slugify(h.textContent);
				// If duplicate id exists, add suffix
				var base = id, k = 2;
				while (document.getElementById(id)) { id = base + '-' + k; k++; }
				h.id = id;
			}
			i++;
		});
	}

	function wireToggle(toc){
		if (!toc) return;
		var btn = toc.querySelector('.wbp-toc__toggle');
		var contentId = toc.getAttribute('data-content');
		var panel = contentId ? document.getElementById(contentId) : toc.querySelector('.wbp-toc__content');
		if (!btn || !panel) return;

		btn.addEventListener('click', function(){
			var expanded = btn.getAttribute('aria-expanded') === 'true';
			btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
			if (expanded) {
				panel.setAttribute('hidden', '');
				toc.classList.add('is-collapsed');
			} else {
				panel.removeAttribute('hidden');
				toc.classList.remove('is-collapsed');
			}
		});
	}

	function init(){
		document.querySelectorAll('.wbp-toc').forEach(function(toc){
			ensureIds(toc);
			wireToggle(toc);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();