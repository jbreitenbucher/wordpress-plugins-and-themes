(function (wp) {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps } = wp.blockEditor || wp.editor;
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

	registerBlockType('ibp/toc', {
		apiVersion: 3,
		edit(props) {
			const { attributes, setAttributes, clientId } = props;
			const {
				title, includeH3, includeH4, includeH5, includeH6,
				collapsed, maxDepth
			} = attributes;

			// Local UI state for editor-only collapsing, seeded from attribute
			const [uiCollapsed, setUiCollapsed] = useState(!!collapsed);

			// Safe blocks fetch; returns [] during pattern hydration
			const blocks = useSelect((select) => {
				const sel = select('core/block-editor');
				return sel && typeof sel.getBlocks === 'function' ? sel.getBlocks() : [];
			}, []);

			const headings = collectHeadings(blocks)
				.filter((h) => h && h.level >= 2 && h.level <= 6)
				.filter((h) => h.level <= (maxDepth || 6))
				.filter((h) => {
					if (h.level === 2) return true;
					if (h.level === 3) return !!includeH3;
					if (h.level === 4) return !!includeH4;
					if (h.level === 5) return !!includeH5;
					if (h.level === 6) return !!includeH6;
					return false;
				});

			const base = 2;
			const blockProps = useBlockProps({
				className: 'ibp-toc' + (uiCollapsed ? ' is-collapsed' : '')
			});
			const contentId = 'ibp-toc-editor-' + clientId;

			// Keep the original nested preview logic that was working before.
			function renderList(items) {
				let currentLevel = base;
				const root = { type: 'ul', props: { className: 'ibp-toc__list level-' + base }, children: [] };
				const stack = [root];
				let currentUl = root;

				items.forEach((h, idx) => {
					const level = Math.min(Math.max(h.level, 2), 6);
					// open deeper lists
					while (currentLevel < level) {
						const newUl = { type: 'ul', props: { className: 'ibp-toc__list level-' + (currentLevel + 1) }, children: [] };
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
					// close lists
					while (currentLevel > level) {
						stack.pop();
						currentUl = stack[stack.length - 1];
						currentLevel--;
					}

					const id = slugify(h.text) || ('section-' + idx);
					currentUl.children.push({
						type: 'li',
						props: { className: 'ibp-toc__item level-' + level },
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
				// Inspector controls (authoritative attributes for frontend/default)
				el(InspectorControls, {},
					el(PanelBody, { title: 'Table of contents', initialOpen: true },
						el(TextControl, {
							label: 'Title',
							value: title,
							onChange: (v) => setAttributes({ title: v || '' })
						}),
						el(ToggleControl, {
							label: 'Collapse TOC by default (frontend)',
							checked: !!collapsed,
							onChange: (v) => setAttributes({ collapsed: !!v })
						}),
						el(RangeControl, {
							label: 'Max depth',
							min: 2, max: 6,
							value: maxDepth,
							onChange: (v) => setAttributes({ maxDepth: v || 6 }),
							help: 'H2 is always included. Headings deeper than this are hidden.'
						}),
						el(Notice, { status: 'info', isDismissible: false },
							'Use the button in the block to collapse in the editor. Sidebar toggle sets the default for visitors.'
						),
						el(ToggleControl, { label: 'Include H3', checked: !!includeH3, onChange: (v) => setAttributes({ includeH3: !!v }) }),
						el(ToggleControl, { label: 'Include H4', checked: !!includeH4, onChange: (v) => setAttributes({ includeH4: !!v }) }),
						el(ToggleControl, { label: 'Include H5', checked: !!includeH5, onChange: (v) => setAttributes({ includeH5: !!v }) }),
						el(ToggleControl, { label: 'Include H6', checked: !!includeH6, onChange: (v) => setAttributes({ includeH6: !!v }) })
					)
				),
				// Editor preview
				el('nav', { ...blockProps, 'aria-label': 'Table of contents' },
					el('button', {
						type: 'button',
						className: 'ibp-toc__toggle',
						'aria-controls': contentId,
						'aria-expanded': String(!uiCollapsed),
						onClick: () => setUiCollapsed(!uiCollapsed)
					}, title || 'On this page', el('span', { className: 'ibp-toc__caret', 'aria-hidden': true })),
					el('div', { id: contentId, className: 'ibp-toc__content', hidden: !!uiCollapsed },
						headings.length ? renderList(headings) : el('p', { className: 'ibp-toc__empty' }, 'No headings yet.')
					)
				)
			);
		},
		save() { return null; }
	});
})(window.wp);