(function (wp) {
  const { InnerBlocks, RichText, InspectorControls, useBlockProps } = wp.blockEditor;
  const { PanelBody, ToggleControl } = wp.components;
  const { createElement: el } = wp.element;

  const INNER_TEMPLATE = [[ 'core/paragraph', { placeholder: 'Add answer text…' } ]];

  wp.blocks.registerBlockType('wbp/accordion-item', {
    attributes: {
      title: { type: 'string', source: 'text', selector: 'button.wbp-accordion__button .wbp-accordion__label' },
      open:  { type: 'boolean', default: false },
      uid:   { type: 'string' },
    },

    edit: function (props) {
      const { attributes, setAttributes, clientId } = props;

      // Ensure a stable uid for ARIA wiring while editing.
      if (!attributes.uid) {
        setAttributes({ uid: clientId.replace(/-/g, '') });
      }
      const uid       = attributes.uid || clientId.replace(/-/g, '');
      const controlId = 'wbp-acc-control-' + uid;
      const panelId   = 'wbp-acc-panel-' + uid;

      // Toggle handler for button and keyboard
      const toggle = () => setAttributes({ open: !attributes.open });
      const onKeyDown = (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggle();
        }
      };

      const blockProps = useBlockProps({
        className: 'wbp-accordion__item' + (attributes.open ? ' is-open' : '')
      });

      const inspector = el(
        InspectorControls,
        {},
        el(
          PanelBody,
          { title: 'Accordion Section', initialOpen: true },
          el(ToggleControl, {
            label: 'Open by default',
            checked: !!attributes.open,
            onChange: (val) => setAttributes({ open: !!val })
          })
        )
      );

      // H3 + button, mirrors front-end save markup closely
      const header = el(
        'h3',
        { className: 'wbp-accordion__heading' },
        el(
          'button',
          {
            type: 'button',
            className: 'wbp-accordion__button',
            'aria-expanded': attributes.open ? 'true' : 'false',
            'aria-controls': panelId,
            id: controlId,
            onClick: toggle,
            onKeyDown: onKeyDown,
          },
          el('span', { className: 'wbp-accordion__icon', 'aria-hidden': 'true' }),
          el(RichText, {
            tagName: 'span',
            className: 'wbp-accordion__label',
            placeholder: 'Section title…',
            value: attributes.title,
            onChange: (val) => setAttributes({ title: val }),
            allowedFormats: [], // keep the title clean
          })
        )
      );

      // Panel: hide in editor when closed (data flags make this editor-only)
      const panel = el(
        'div',
        {
          id: panelId,
          className: 'wbp-accordion__panel',
          role: 'region',
          'aria-labelledby': controlId,
          'data-editor': '1',
          'data-open': attributes.open ? 'true' : 'false',
        },
        el(InnerBlocks, { template: INNER_TEMPLATE })
      );

      return el('div', blockProps, inspector, header, panel);
    },

    // save() unchanged from your working version
    save: function (props) {
      const { attributes } = props;
      const uid = attributes.uid || Math.random().toString(36).slice(2);
      const controlId = 'wbp-acc-control-' + uid;
      const panelId   = 'wbp-acc-panel-' + uid;

      const blockProps = wp.blockEditor.useBlockProps.save({
        className: 'wbp-accordion__item' + (attributes.open ? ' is-open' : '')
      });

      return el(
        'div',
        blockProps,
        el(
          'h3',
          { className: 'wbp-accordion__heading' },
          el(
            'button',
            {
              type: 'button',
              className: 'wbp-accordion__button',
              'aria-expanded': attributes.open ? 'true' : 'false',
              'aria-controls': panelId,
              id: controlId,
            },
            el('span', { className: 'wbp-accordion__icon', 'aria-hidden': 'true' }),
            el(wp.blockEditor.RichText.Content, {
              tagName: 'span',
              className: 'wbp-accordion__label',
              value: attributes.title || 'Section title',
            })
          )
        ),
        el(
          'div',
          { id: panelId, className: 'wbp-accordion__panel', role: 'region', 'aria-labelledby': controlId },
          el(wp.blockEditor.InnerBlocks.Content, null)
        )
      );
    },
  });
})(window.wp);