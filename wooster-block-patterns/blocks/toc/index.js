/* global window */
(function (wp) {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps, RichText } = wp.blockEditor || wp.editor;
	const { PanelBody, TextControl, ToggleControl, RangeControl, Notice } = wp.components;
	const { useSelect } = wp.data;
	const { createElement: el, Fragment, useState } = wp.element;

	function collectHeadings(blocks) {
		const result = [];
		function walk(items) {
			(items || []).forEach((b) => {
				if (!b) return;
				if (b.name === 'core/heading') {
					const level = (b.attributes && b.attributes.level) || 2;
					let html = (b.attributes && b.attributes.content) || '';
					const tmp = document.createElement('div');
					tmp.innerHTML = html;
					const text = tmp.textContent || tmp.innerText || '';
					result.push({ level, text });
				}
				if (b.innerBlocks && b.innerBlocks.length) {
					walk(b.innerBlocks);
				}
			});
		}
		walk(blocks || []);
		return result;
	}

	function slugify(text) {
		return String(text || '')
			.toLowerCase()
			.normalize('NFKD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[^a-z0-9\s-]/g, '')
			.trim()
			.replace(/\s+/g, '-')
			.replace(/-+/g, '-')
			.slice(0, 80);
	}

	registerBlockType('wbp/toc', {
		apiVersion: 3,
		edit(props) {
			const { attributes, setAttributes, clientId } = props;
			const { title, collapsed, maxDepth } = attributes;

			// Editor-only collapse state so authors can fold it while editing
			const [uiCollapsed, setUiCollapsed] = useState(!!collapsed);

			// Grab all blocks to preview headings live
			const blocks = useSelect((select) => {
				const sel = select('core/block-editor');
				return sel && typeof sel.getBlocks === 'function' ? sel.getBlocks() : [];
			}, []);

			// H2 is base; include up to maxDepth (2..6)
			const depthMax = Math.min(Math.max(maxDepth || 3, 2), 6);
			const headings = collectHeadings(blocks)
				.filter((h) => h && h.level >= 2 && h.level <= depthMax);

			// Build nested preview structure
			const base = 2;
			const blockProps = useBlockProps({
				className: 'wbp-toc' + (uiCollapsed ? ' is-collapsed' : '')
				// Color + align + spacing classes/styles are added by useBlockProps automatically
			});
			const contentId = 'wbp-toc-editor-' + clientId;

			function renderList(items) {
				let currentLevel = base;
				const root = { type: 'ul', props: { className: 'wbp-toc__list level-' + base }, children: [] };
				const stack = [root];
				let currentUl = root;

				items.forEach((h, idx) => {
					const level = Math.min(Math.max(h.level, 2), 6);
					// Open deeper lists
					while (currentLevel < level) {
						const newUl = { type: 'ul', props: { className: 'wbp-toc__list level-' + (currentLevel + 1) }, children: [] };
						const lastLi = currentUl.children[currentUl.children.length - 1];
						if (lastLi) {
							lastLi.children = lastLi.children || [];
							lastLi.children.push(newUl);
						} else {
							currentUl.children.push(newUl);
						}
						stack.push(newUl);
						currentUl = newUl;
						currentLevel++;
					}
					// Close lists
					while (currentLevel > level) {
						stack.pop();
						currentUl = stack[stack.length - 1];
						currentLevel--;
					}

					const id = slugify(h.text) || ('section-' + idx);
					currentUl.children.push({
						type: 'li',
						props: { className: 'wbp-toc__item level-' + level },
						children: [
							{ type: 'a', props: { href: '#' + id }, children: [ h.text || '(untitled)' ] }
						]
					});
				});

				function toEl(node, key) {
					if (typeof node === 'string') return node;
					const kids = (node.children || []).map((c, i) => toEl(c, i));
					return el(node.type, { key, ...(node.props || {}) }, ...kids);
				}
				return toEl(root, 'root');
			}

			return el(Fragment, {},
				// Inspector: title, default collapse, and single depth control
				el(InspectorControls, {},
					el(PanelBody, { title: 'TOC Settings', initialOpen: true },
						el(TextControl, {
							label: 'Title',
							value: title,
							onChange: (v) => setAttributes({ title: v || '' })
						}),
						el(RangeControl, {
							label: 'Max heading level (starts at H2)',
							min: 2, max: 6,
							value: depthMax,
							onChange: (v) => setAttributes({ maxDepth: v || 3 })
						}),
						el(ToggleControl, {
							label: 'Collapse TOC by default (frontend)',
							checked: !!collapsed,
							onChange: (v) => setAttributes({ collapsed: !!v })
						}),
						el(Notice, { status: 'info', isDismissible: false },
							'Colors, spacing, and alignment are in the block toolbar/panels. This preview updates as you add headings.'
						)
					)
				),

				// Editor preview (foldable)
				el('nav', { ...blockProps, 'aria-label': 'Table of contents' },
					el('div', { className: 'wbp-toc__header' },
						el(RichText, {
							tagName: 'h4',
							className: 'wbp-toc__title',
							value: title || 'On this page',
							allowedFormats: [],
							onChange: (v) => setAttributes({ title: v })
						}),
						el('button', {
							type: 'button',
							className: 'wbp-toc__toggle',
							'aria-controls': contentId,
							'aria-expanded': String(!uiCollapsed),
							onClick: () => setUiCollapsed(!uiCollapsed)
						}, el('span', { className: 'wbp-toc__caret', 'aria-hidden': true }))
					),
					el('div', { id: contentId, className: 'wbp-toc__content', hidden: !!uiCollapsed },
						headings.length ? renderList(headings) : el('p', { className: 'wbp-toc__empty' }, 'No headings yet.')
					)
				)
			);
		},
		save() { return null; } // dynamic render in PHP
	});
})(window.wp);