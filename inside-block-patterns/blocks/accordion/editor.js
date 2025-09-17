( function( wp ) {
  const { InnerBlocks, useBlockProps, BlockControls } = wp.blockEditor;
  const { ToolbarGroup, ToolbarButton } = wp.components;
  const { useSelect, useDispatch } = wp.data;
  const { createElement: el } = wp.element;
  const { createBlock } = wp.blocks;

  const ALLOWED = [ 'ibp/accordion-item' ];
  const TEMPLATE = [
    [ 'ibp/accordion-item', { open: false, title: 'Question one' } ],
    [ 'ibp/accordion-item', { open: false, title: 'Question two' } ],
  ];

  wp.blocks.registerBlockType( 'ibp/accordion', {
    edit: function( props ) {
      const { clientId } = props;
      const blockProps = useBlockProps( { className: 'ibp-accordion' } );

      const { insertBlocks } = useDispatch( 'core/block-editor' );

      // Add a new section as the last child of this accordion
      const addSection = () => {
        insertBlocks(
          createBlock( 'ibp/accordion-item', { open: false, title: 'New question' } ),
          undefined,
          clientId
        );
      };

      // Whether we have any child sections (optional, for conditional UI)
      const hasChildren = useSelect( ( select ) => {
        const b = select( 'core/block-editor' ).getBlock( clientId );
        return !!( b && b.innerBlocks && b.innerBlocks.length );
      }, [ clientId ] );

      return el(
        'div',
        blockProps,
        // Toolbar button in the block toolbar
        el(
          BlockControls,
          {},
          el(
            ToolbarGroup,
            {},
            el( ToolbarButton, {
              icon: 'plus',
              label: 'Add question',
              onClick: addSection
            } )
          )
        ),
        // Child sections, plus a visible appender at the end
        el( InnerBlocks, {
          allowedBlocks: ALLOWED,
          template: TEMPLATE,
          templateLock: false,
          // Always show a clear "Add section" button at the end
          renderAppender: InnerBlocks.ButtonBlockAppender
        } )
      );
    },

    save: function() {
      const blockProps = wp.blockEditor.useBlockProps.save( { className: 'ibp-accordion' } );
      return el( 'div', blockProps, el( InnerBlocks.Content, null ) );
    },
  } );
} )( window.wp );