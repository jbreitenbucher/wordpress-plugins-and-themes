( function( wp ) {
  const { InnerBlocks, useBlockProps, BlockControls, InspectorControls } = wp.blockEditor || wp.editor;
  const { ToolbarGroup, ToolbarButton, PanelBody, ToggleControl } = wp.components;
  const { useSelect, useDispatch } = wp.data;
  const { createElement: el, Fragment } = wp.element;
  const { createBlock, registerBlockType } = wp.blocks;

  const ALLOWED = [ 'wbp/accordion-item' ];
  const TEMPLATE = [
    [ 'wbp/accordion-item', { open: false, title: 'Question one' } ],
    [ 'wbp/accordion-item', { open: false, title: 'Question two' } ],
  ];

  registerBlockType( 'wbp/accordion', {
    edit: function( props ) {
      const { clientId, attributes = {}, setAttributes } = props;
      const { insertBlocks, updateBlockAttributes } = useDispatch( 'core/block-editor' );

      // Read optional allowMultiple attr if your block.json defines it; otherwise undefined and ignored.
      const allowMultiple = attributes.hasOwnProperty( 'allowMultiple' ) ? !!attributes.allowMultiple : undefined;

      // Child block clientIds (for Expand/Collapse All)
      const childIds = useSelect( ( select ) => {
        const b = select( 'core/block-editor' ).getBlock( clientId );
        return b && b.innerBlocks ? b.innerBlocks.map( ( ib ) => ib.clientId ) : [];
      }, [ clientId ] );

      const hasChildren = childIds.length > 0;

      // Actions
      const addSection = () => {
        insertBlocks(
          createBlock( 'wbp/accordion-item', { open: false, title: 'New question' } ),
          undefined,
          clientId
        );
      };

      const setAll = ( isOpen ) => {
        childIds.forEach( ( id ) => {
          updateBlockAttributes && updateBlockAttributes( id, { open: !!isOpen }, true );
        } );
      };

      const blockProps = useBlockProps( {
        className: 'wbp-accordion',
        ...( allowMultiple !== undefined ? { 'data-allow-multiple': String( !!allowMultiple ) } : {} ),
      } );

      return el(
        Fragment,
        {},
        // Optional inspector setting if you *kept* the allowMultiple attribute in block.json
        el(
          InspectorControls,
          null,
          attributes.hasOwnProperty( 'allowMultiple' ) &&
            el(
              PanelBody,
              { title: 'Accordion Settings', initialOpen: true },
              el( ToggleControl, {
                label: 'Allow multiple sections open',
                checked: !!allowMultiple,
                onChange: ( v ) => setAttributes( { allowMultiple: !!v } ),
              } )
            )
        ),

        // Block toolbar
        el(
          BlockControls,
          {},
          el(
            ToolbarGroup,
            {},
            el( ToolbarButton, {
              icon: 'plus',
              label: 'Add question',
              onClick: addSection,
            } ),
            hasChildren &&
              el( ToolbarButton, {
                icon: 'arrow-down-alt2',
                label: 'Expand all',
                onClick: () => setAll( true ),
              } ),
            hasChildren &&
              el( ToolbarButton, {
                icon: 'dismiss',
                label: 'Collapse all',
                onClick: () => setAll( false ),
              } )
          )
        ),

        // Wrapper + children
        el(
          'div',
          blockProps,
          el( InnerBlocks, {
            allowedBlocks: ALLOWED,
            template: TEMPLATE,
            templateLock: false,
            renderAppender: InnerBlocks.ButtonBlockAppender,
          } )
        )
      );
    },

    save: function() {
      const blockProps = ( wp.blockEditor || wp.editor ).useBlockProps.save( { className: 'wbp-accordion' } );
      return el( 'div', blockProps, el( InnerBlocks.Content, null ) );
    },
  } );
} )( window.wp );