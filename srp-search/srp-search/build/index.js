( function ( blocks, element, blockEditor ) {
	const el         = element.createElement;
	const useBlockProps = blockEditor.useBlockProps;

	blocks.registerBlockType( 'srp/search', {
		title:       'SRP Search',
		description: 'Senior Research Project search form.',
		category:    'widgets',
		icon:        'search',
		supports:    { html: false },

		edit: function () {
			const blockProps = useBlockProps( { className: 'srp-editor-preview' } );
			return el(
				'div',
				blockProps,
				el( 'div', { className: 'srp-editor-label' },
					el( 'span', { className: 'dashicons dashicons-search' } ),
					' SRP Search Block — displays the student research project search form on the front end.'
				)
			);
		},

		save: function () {
			// Server-side rendered; save returns null.
			return null;
		},
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor
);
