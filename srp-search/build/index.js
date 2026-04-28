/* global wp */
( function () {
	const { registerBlockType }                          = wp.blocks;
	const { useBlockProps, InspectorControls }           = wp.blockEditor;
	const { PanelBody, SelectControl, ToggleControl,
	        TextControl, RangeControl }                  = wp.components;
	const ServerSideRender                               = wp.serverSideRender;

	registerBlockType( 'srp/search', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const {
				perPage,
				noResultsMessage,
				showMajor2,
				showAdvisor,
				orderBy,
			} = attributes;

			const blockProps = useBlockProps();

			return wp.element.createElement(
				wp.element.Fragment,
				null,

				// ── Inspector Panel ──────────────────────────────────────
				wp.element.createElement(
					InspectorControls,
					null,

					wp.element.createElement(
						PanelBody,
						{ title: 'Search Settings', initialOpen: true },

						wp.element.createElement( SelectControl, {
							label:    'Results per page',
							value:    perPage,
							options:  [
								{ label: '25', value: 25 },
								{ label: '50', value: 50 },
								{ label: '100', value: 100 },
							],
							onChange: ( v ) => setAttributes( { perPage: parseInt( v, 10 ) } ),
						} ),

						wp.element.createElement( SelectControl, {
							label:    'Result ordering',
							value:    orderBy,
							options:  [
								{ label: 'Year (oldest first), then Last Name', value: 'year_asc_name_asc'  },
								{ label: 'Last Name, then Year (oldest first)', value: 'name_asc_year_asc'  },
								{ label: 'Year (newest first), then Last Name', value: 'year_desc_name_asc' },
								{ label: 'Last Name only',                      value: 'name_asc'           },
							],
							onChange: ( v ) => setAttributes( { orderBy: v } ),
						} ),

						wp.element.createElement( TextControl, {
							label:    'No results message',
							value:    noResultsMessage,
							onChange: ( v ) => setAttributes( { noResultsMessage: v } ),
						} )
					),

					wp.element.createElement(
						PanelBody,
						{ title: 'Column Visibility', initialOpen: true },

						wp.element.createElement( ToggleControl, {
							label:    'Show Major 2',
							checked:  showMajor2,
							onChange: ( v ) => setAttributes( { showMajor2: !! v } ),
						} ),

						wp.element.createElement( ToggleControl, {
							label:    'Show Advisor',
							checked:  showAdvisor,
							onChange: ( v ) => setAttributes( { showAdvisor: !! v } ),
						} )
					)
				),

				// ── Editor canvas: live SSR preview ──────────────────────
				wp.element.createElement(
					'div',
					blockProps,
					wp.element.createElement( ServerSideRender, {
						block:      'srp/search',
						attributes: attributes,
					} )
				)
			);
		},

		save: function () {
			return null;
		},
	} );
} )();
