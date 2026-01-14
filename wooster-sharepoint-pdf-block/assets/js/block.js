/**
 * assets/js/block.js
 */
/* global wp, wspdfBlock */
(function (wp) {
  const { __ } = wp.i18n;
  const { registerBlockType } = wp.blocks;
  const { InspectorControls, useBlockProps } = wp.blockEditor;
  const { PanelBody, TextControl, RangeControl, Notice } = wp.components;
  const { useMemo } = wp.element;

  function base64UrlEncode(str) {
    // Unicode-safe base64url.
    const bytes = new TextEncoder().encode(str);
    let binary = "";
    for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
    const b64 = btoa(binary);
    return b64.replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/g, "");
  }

  function buildViewerSrc(shareUrl, filename) {
    const base = (wspdfBlock && wspdfBlock.restBase) ? wspdfBlock.restBase : "/wp-json/wspdf/v1";
    const u = base64UrlEncode(shareUrl || "");
    const params = new URLSearchParams();
    params.set("u", u);
    if (filename) params.set("fn", filename);
    return base.replace(/\/$/, "") + "/viewer?" + params.toString();
  }

  registerBlockType("wspdf/wooster-sharepoint-pdf", {
    title: __("Wooster SharePoint PDF", "wooster-sharepoint-pdf-block"),
    description: __("Embed a SharePoint PDF using an “Anyone” share link, rendered via a consistent PDF.js viewer.", "wooster-sharepoint-pdf-block"),
    icon: "media-document",
    category: "wbp-content", // Wooster Blocks category slug (created in PHP if missing).
    attributes: {
      shareUrl: { type: "string", default: "" },
      filename: { type: "string", default: "" },
      height: { type: "number", default: 900 }
    },
    supports: {
      align: ["wide", "full"]
    },

    edit: function (props) {
      const { attributes, setAttributes } = props;
      const { shareUrl, filename, height } = attributes;

      const blockProps = useBlockProps();

      const src = useMemo(() => {
        if (!shareUrl) return "";
        return buildViewerSrc(shareUrl, filename);
      }, [shareUrl, filename]);

      return (
        wp.element.createElement(
          wp.element.Fragment,
          null,
          wp.element.createElement(
            InspectorControls,
            null,
            wp.element.createElement(
              PanelBody,
              { title: __("SharePoint PDF Settings", "wooster-sharepoint-pdf-block"), initialOpen: true },
              wp.element.createElement(TextControl, {
                label: __("SharePoint “Anyone” URL", "wooster-sharepoint-pdf-block"),
                help: __("Paste the SharePoint “Anyone” share link (tenant: livewooster.sharepoint.com).", "wooster-sharepoint-pdf-block"),
                value: shareUrl,
                onChange: (v) => setAttributes({ shareUrl: v })
              }),
              wp.element.createElement(TextControl, {
                label: __("Filename (optional)", "wooster-sharepoint-pdf-block"),
                help: __("Used for a friendly filename in the proxy response.", "wooster-sharepoint-pdf-block"),
                value: filename,
                onChange: (v) => setAttributes({ filename: v })
              }),
              wp.element.createElement(RangeControl, {
                label: __("Viewer height (px)", "wooster-sharepoint-pdf-block"),
                min: 200,
                max: 1600,
                step: 10,
                value: height,
                onChange: (v) => setAttributes({ height: v || 900 })
              })
            )
          ),

          wp.element.createElement(
            "div",
            blockProps,
            !shareUrl
              ? wp.element.createElement(Notice, { status: "info", isDismissible: false }, __("Add a SharePoint “Anyone” URL to preview the PDF.", "wooster-sharepoint-pdf-block"))
              : wp.element.createElement("iframe", {
                title: __("PDF Preview", "wooster-sharepoint-pdf-block"),
                src: src,
                style: { width: "100%", height: (height || 900) + "px", border: "1px solid rgba(0,0,0,0.1)", borderRadius: "6px", background: "#fff" }
              })
          )
        )
      );
    },

    save: function () {
      // Server-rendered (PHP render_callback outputs the iframe to /viewer).
      return null;
    }
  });
})(window.wp);