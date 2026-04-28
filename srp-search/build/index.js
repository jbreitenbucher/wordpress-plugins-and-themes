/* global wp */
( function () {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps }     = wp.blockEditor;
	const ServerSideRender      = wp.serverSideRender;

	registerBlockType( 'srp/search', {
		edit: function ( props ) {
			const blockProps = useBlockProps();
			return wp.element.createElement(
				'div',
				blockProps,
				wp.element.createElement( ServerSideRender, {
					block: 'srp/search',
					attributes: props.attributes,
				} )
			);
		},

		save: function () {
			// Server-side rendered — save returns null.
			return null;
		},
	} );
} )();
