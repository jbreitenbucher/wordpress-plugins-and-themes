(function (wp) {
  const { InnerBlocks, RichText, InspectorControls, useBlockProps } = wp.blockEditor || wp.editor;
  const { PanelBody, ToggleControl } = wp.components;
  const { createElement: el, useEffect } = wp.element;

  const INNER_TEMPLATE = [[ 'core/paragraph', { placeholder: 'Add answer text…' } ]];

  wp.blocks.registerBlockType('wbp/accordion-item', {
    attributes: {
      title: { type: 'string', source: 'text', selector: 'button.wbp-accordion__button .wbp-accordion__label' },
      open:  { type: 'boolean', default: false },
      uid:   { type: 'string' },
    },

    edit: function (props) {
      const { attributes = {}, setAttributes, clientId } = props;
      const { title = '', open = false, uid } = attributes;

      // Ensure a stable uid once, without setting attributes in render.
      useEffect(() => {
        if (!uid) {
          setAttributes({ uid: clientId.replace(/-/g, '') });
        }
      }, [uid, clientId]);

      const stableUid = uid || clientId.replace(/-/g, '');
      const controlId = 'wbp-acc-control-' + stableUid;
      const panelId   = 'wbp-acc-panel-' + stableUid;

      const toggle = () => setAttributes({ open: !open });
      const onKeyDown = (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggle();
        }
      };

      const blockProps = useBlockProps({
        className: 'wbp-accordion__item' + (open ? ' is-open' : '')
      });

      const inspector = el(
        InspectorControls,
        {},
        el(
          PanelBody,
          { title: 'Accordion Section', initialOpen: true },
          el(ToggleControl, {
            label: 'Open by default',
            checked: !!open,
            onChange: (val) => setAttributes({ open: !!val })
          })
        )
      );

      const header = el(
        'h3',
        { className: 'wbp-accordion__heading' },
        el(
          'button',
          {
            type: 'button',
            className: 'wbp-accordion__button',
            'aria-expanded': open ? 'true' : 'false',
            'aria-controls': panelId,
            id: controlId,
            onClick: toggle,
            onKeyDown,
          },
          el('span', { className: 'wbp-accordion__icon', 'aria-hidden': 'true' }),
          el(RichText, {
            tagName: 'span',
            className: 'wbp-accordion__label',
            placeholder: 'Section title…',
            value: title,
            onChange: (val) => setAttributes({ title: val }),
            allowedFormats: [],
          })
        )
      );

      const panel = el(
        'div',
        {
          id: panelId,
          className: 'wbp-accordion__panel',
          role: 'region',
          'aria-labelledby': controlId,
          hidden: !open,            // editor & front-end friendly
          'data-editor': '1',       // keep your existing editor-only hooks
          'data-open': open ? 'true' : 'false',
        },
        el(InnerBlocks, { template: INNER_TEMPLATE })
      );

      return el('div', blockProps, inspector, header, panel);
    },

    save: function (props) {
      const { attributes = {} } = props;
      const { title = 'Section title', open = false, uid } = attributes;

      const stableUid = uid || Math.random().toString(36).slice(2);
      const controlId = 'wbp-acc-control-' + stableUid;
      const panelId   = 'wbp-acc-panel-' + stableUid;

      const blockProps = (wp.blockEditor || wp.editor).useBlockProps.save({
        className: 'wbp-accordion__item' + (open ? ' is-open' : '')
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
              'aria-expanded': open ? 'true' : 'false',
              'aria-controls': panelId,
              id: controlId,
            },
            el('span', { className: 'wbp-accordion__icon', 'aria-hidden': 'true' }),
            el((wp.blockEditor || wp.editor).RichText.Content, {
              tagName: 'span',
              className: 'wbp-accordion__label',
              value: title,
            })
          )
        ),
        el(
          'div',
          { id: panelId, className: 'wbp-accordion__panel', role: 'region', 'aria-labelledby': controlId },
          el((wp.blockEditor || wp.editor).InnerBlocks.Content, null)
        )
      );
    },
  });
})(window.wp);