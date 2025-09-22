( function( wp ) {
  const { InnerBlocks, useBlockProps, BlockControls, InspectorControls } = wp.blockEditor || wp.editor;
  const { ToolbarGroup, ToolbarButton, PanelBody, ToggleControl } = wp.components;
  const { useSelect, useDispatch, select: dataSelect } = wp.data;
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

      // Optional: only used if your block.json defines it.
      const allowMultiple = attributes.hasOwnProperty( 'allowMultiple' ) ? !!attributes.allowMultiple : undefined;

      // Track children for toolbar enablement; actual updates fetch fresh IDs at click time.
      const childIds = useSelect( ( select ) => {
        const b = select( 'core/block-editor' ).getBlock( clientId );
        return b && b.innerBlocks ? b.innerBlocks.map( ( ib ) => ib.clientId ) : [];
      }, [ clientId ] );
      const hasChildren = childIds.length > 0;

      const addSection = () => {
        insertBlocks(
          createBlock( 'wbp/accordion-item', { open: false, title: 'New question' } ),
          undefined,
          clientId
        );
      };

      // Fresh lookup to avoid stale closures; update each child's `open`.
      const setAll = ( isOpen ) => {
        const sel = dataSelect( 'core/block-editor' );
        const parent = sel.getBlock( clientId );
        const ids = parent && parent.innerBlocks ? parent.innerBlocks.map( ( ib ) => ib.clientId ) : [];
        ids.forEach( ( id ) => updateBlockAttributes( id, { open: !!isOpen } ) );
      };

      const blockProps = useBlockProps( {
        className: 'wbp-accordion',
        ...( allowMultiple !== undefined ? { 'data-allow-multiple': String( !!allowMultiple ) } : {} ),
      } );

      return el(
        Fragment,
        null,

        // Only shows if `allowMultiple` exists in block.json
        attributes.hasOwnProperty( 'allowMultiple' ) &&
          el(
            InspectorControls,
            null,
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

        el(
          BlockControls,
          null,
          el(
            ToolbarGroup,
            null,
            el( ToolbarButton, {
              icon: 'plus',
              label: 'Add question',
              onClick: addSection,
            } ),
            hasChildren && el( ToolbarButton, {
              icon: 'arrow-down-alt2',
              label: 'Expand all',
              onClick: () => setAll( true ),
            } ),
            hasChildren && el( ToolbarButton, {
              icon: 'dismiss',
              label: 'Collapse all',
              onClick: () => setAll( false ),
            } )
          )
        ),

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