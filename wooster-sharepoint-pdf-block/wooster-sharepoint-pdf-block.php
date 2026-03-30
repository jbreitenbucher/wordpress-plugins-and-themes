<?php
/**
 * Plugin Name:       Wooster SharePoint PDF Block
 * Description:       Gutenberg block to embed SharePoint PDFs via “Anyone” share links using a self-hosted
 *                    PDF.js viewer (no Media Library uploads, no raw-PDF iframe).
 * Version:           1.0.1
 * Author:            The College of Wooster
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * Text Domain:       wooster-sharepoint-pdf-block
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Wooster_SharePoint_PDF_Block {
    const NS          = 'wspdf/v1';
    const TENANT_HOST = 'livewooster.sharepoint.com';

    public static function init() : void {
        add_action( 'init', [ __CLASS__, 'register_block' ] );
        add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );

        // Ensure the Wooster Blocks category exists (slug: wbp-content).
        // block_categories_all is the modern filter (WP 5.8+). We also register the legacy filter
        // for older sites, without creating duplicates.
        add_filter( 'block_categories_all', [ __CLASS__, 'filter_block_categories' ], 10, 2 );
        add_filter( 'block_categories', [ __CLASS__, 'filter_block_categories_legacy' ], 10, 2 );
    }

    /**
     * Add the Wooster Blocks category (wbp-content) if missing.
     *
     * @param array $categories Existing categories.
     * @param mixed $post       Current post.
     * @return array
     */
    public static function filter_block_categories( array $categories, $post ) : array {
        $slug = 'wbp-content';

        foreach ( $categories as $cat ) {
            if ( isset( $cat['slug'] ) && $cat['slug'] === $slug ) {
                return $categories;
            }
        }

        $categories[] = [
            'slug'  => $slug,
            'title' => __( 'Wooster Blocks', 'wooster-sharepoint-pdf-block' ),
            'icon'  => null,
        ];

        return $categories;
    }

    /**
     * Legacy filter signature for pre-5.8 sites.
     *
     * @param array $categories Existing categories.
     * @param mixed $post       Current post.
     * @return array
     */
    public static function filter_block_categories_legacy( array $categories, $post ) : array {
        // The legacy filter provides arrays of ['slug' => ..., 'title' => ...].
        return self::filter_block_categories( $categories, $post );
    }

    public static function register_block() : void {
        $handle = 'wspdf-block-editor';

        wp_register_script(
            $handle,
            plugins_url( 'assets/js/block.js', __FILE__ ),
            [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n' ],
            filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/block.js' ),
            true
        );

        wp_localize_script(
            $handle,
            'wspdfBlock',
            [
                'restBase' => esc_url_raw( rest_url( self::NS ) ),
            ]
        );

        // Prefer block.json metadata (build/block.json) so editor + PHP stay in sync.
        // We still pass the render callback here because this is a dynamic block.
        register_block_type(
            __DIR__ . '/build',
            [
                'render_callback' => [ __CLASS__, 'render_block' ],
            ]
        );
    }

    /**
     * Server-side render callback.
     *
     * Important: use get_block_wrapper_attributes() so core can apply alignment
     * classes (alignwide/alignfull) and any theme styles that depend on them.
     *
     * @param array         $attributes Block attributes.
     * @param string        $content    Block content.
     * @param WP_Block|null $block      Block instance.
     * @return string
     */
    
	/**
	 * Append one or more CSS classes to a wrapper-attribute string produced by get_block_wrapper_attributes().
	 *
	 * We intentionally call get_block_wrapper_attributes() *without* overriding the "class" attribute so
	 * WordPress can inject alignment classes (alignwide/alignfull) and any other block-support classes.
	 *
	 * @param string $wrapper_attributes Attribute string, e.g. 'class="..." data-...'
	 * @param string $classes_to_add     Space-separated classes to append.
	 * @return string Updated attribute string.
	 */
	private static function append_classes_to_wrapper_attributes( $wrapper_attributes, $classes_to_add ) {
		$classes_to_add = trim( (string) $classes_to_add );
		if ( '' === $classes_to_add ) {
			return (string) $wrapper_attributes;
		}

		$wrapper_attributes = (string) $wrapper_attributes;

		if ( preg_match( '/\bclass="([^"]*)"/', $wrapper_attributes, $m ) ) {
			$existing = trim( $m[1] );
			$combined = trim( $existing . ' ' . $classes_to_add );
			return preg_replace( '/\bclass="[^"]*"/', 'class="' . esc_attr( $combined ) . '"', $wrapper_attributes, 1 );
		}

		// No class attribute present yet; just append one.
		return trim( $wrapper_attributes ) . ' class="' . esc_attr( $classes_to_add ) . '"';
	}

public static function render_block( array $attributes, string $content = '', $block = null ) : string {
        $share_url = isset( $attributes['shareUrl'] ) ? trim( (string) $attributes['shareUrl'] ) : '';
        if ( $share_url === '' ) {
            return '';
        }

        $filename = isset( $attributes['filename'] ) ? sanitize_file_name( (string) $attributes['filename'] ) : '';
        $height   = isset( $attributes['height'] ) ? max( 200, (int) $attributes['height'] ) : 900;

        $args = [
                'u' => self::b64url_encode( $share_url ),
            ];
            if ( $filename !== '' ) {
                $args['fn'] = $filename;
            }

            $viewer_url = add_query_arg(
                $args,
                rest_url( self::NS . '/viewer' )
            );

        $wrapper_attrs = function_exists( 'get_block_wrapper_attributes' )
            ? get_block_wrapper_attributes( [ 'class' => 'wspdf-embed' ] )
            : 'class="wspdf-embed"';

        // Build a descriptive iframe title for screen readers (WCAG 4.1.2, 2.4.1).
        $iframe_title = $filename !== ''
            ? sprintf(
                /* translators: %s: PDF filename */
                __( '%s — PDF viewer', 'wooster-sharepoint-pdf-block' ),
                $filename
            )
            : __( 'PDF document viewer', 'wooster-sharepoint-pdf-block' );

        return sprintf(
            '<div %s><iframe class="wspdf-viewer" src="%s" title="%s" style="display:block;width:100%%;height:%dpx;border:0;" loading="lazy" referrerpolicy="no-referrer"></iframe></div>',
            $wrapper_attrs,
            esc_url( $viewer_url ),
            esc_attr( $iframe_title ),
            (int) $height
        );
    }

    public static function register_rest_routes() : void {
        register_rest_route(
            self::NS,
            '/pdf',
            [
                'methods'             => 'GET',
                'permission_callback' => '__return_true',
                'callback'            => [ __CLASS__, 'rest_pdf_proxy' ],
                'args'                => [
                    'u'  => [ 'required' => true ],
                    'fn' => [ 'required' => false ],
                    'dl' => [ 'required' => false ],
                ],
            ]
        );

        // Echo HTML and exit (NOT JSON) to avoid escaped newline junk.
        register_rest_route(
            self::NS,
            '/viewer',
            [
                'methods'             => 'GET',
                'permission_callback' => '__return_true',
                'callback'            => [ __CLASS__, 'rest_viewer_html' ],
                'args'                => [
                    'u'  => [ 'required' => true ],
                    'fn' => [ 'required' => false ],
                    'dl' => [ 'required' => false ],
                ],
            ]
        );

        register_rest_route(
            self::NS,
            '/asset',
            [
                'methods'             => 'GET',
                'permission_callback' => '__return_true',
                'callback'            => [ __CLASS__, 'rest_asset' ],
                'args'                => [
                    'f' => [ 'required' => true ],
                ],
            ]
        );
    }

    /**
     * /wp-json/wspdf/v1/asset?f=pdf|worker
     */
    public static function rest_asset( WP_REST_Request $request ) {
        $f = (string) $request->get_param( 'f' );

        $map = [
            'pdf'    => plugin_dir_path( __FILE__ ) . 'assets/pdfjs/pdf.min.js',
            'worker' => plugin_dir_path( __FILE__ ) . 'assets/pdfjs/pdf.worker.min.js',
        ];

        if ( ! isset( $map[ $f ] ) ) {
            return new WP_Error( 'wspdf_bad_asset', 'Invalid asset.', [ 'status' => 400 ] );
        }

        $path = $map[ $f ];
        if ( ! file_exists( $path ) ) {
            return new WP_Error( 'wspdf_missing_asset', 'Asset not found.', [ 'status' => 404 ] );
        }

        nocache_headers();
        header( 'Content-Type: application/javascript; charset=utf-8' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Cache-Control: public, max-age=86400' );

        // Streaming is intentional here; these files are shipped with the plugin.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        readfile( $path );
        exit;
    }

    /**
     * STREAMED PROXY (prevents “loads pages but renders blank” caused by mangled binary output)
     * /wp-json/wspdf/v1/pdf?u=<b64url(share_url)>&fn=<filename>
     */
    public static function rest_pdf_proxy( WP_REST_Request $request ) {
        $u  = (string) $request->get_param( 'u' );
        $fn = (string) $request->get_param( 'fn' );

        $share_url = self::b64url_decode( $u );
        if ( ! $share_url ) {
            return new WP_Error( 'wspdf_bad_u', 'Invalid URL encoding.', [ 'status' => 400 ] );
        }

        $share_url = trim( $share_url );
        if ( ! self::is_allowed_sharepoint_url( $share_url ) ) {
            return new WP_Error( 'wspdf_bad_domain', 'URL must be a SharePoint link for the configured tenant.', [ 'status' => 400 ] );
        }

        $download_url = self::share_to_download_url( $share_url );
        if ( is_wp_error( $download_url ) ) {
            return $download_url;
        }

        $tmp = wp_tempnam( 'wspdf-' );
        if ( ! $tmp ) {
            return new WP_Error( 'wspdf_tmp_failed', 'Could not create temp file for streaming.', [ 'status' => 500 ] );
        }

        $response = wp_remote_get(
            $download_url,
            [
                'timeout'     => 60,
                'redirection' => 5,
                'stream'      => true,
                'filename'    => $tmp,
                'headers'     => [
                    'Accept'          => 'application/pdf',
                    // Avoid compressed transfer edge cases when proxying binary.
                    'Accept-Encoding' => 'identity',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_delete_file( $tmp );
            return new WP_Error( 'wspdf_fetch_failed', $response->get_error_message(), [ 'status' => 502 ] );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            wp_delete_file( $tmp );
            return new WP_Error( 'wspdf_bad_response', 'Failed to retrieve PDF.', [ 'status' => 502, 'code' => $code ] );
        }

        if ( ! file_exists( $tmp ) || filesize( $tmp ) < 5 ) {
            wp_delete_file( $tmp );
            return new WP_Error( 'wspdf_empty_pdf', 'Received empty PDF stream.', [ 'status' => 502 ] );
        }

        // Sanity check: should start with "%PDF-"
        // Read a tiny header to confirm this is a PDF (defensive against HTML error pages).
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $fh   = @fopen( $tmp, 'rb' );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
        $head = $fh ? fread( $fh, 5 ) : '';
        if ( $fh ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            fclose( $fh );
        }
        if ( $head !== '%PDF-' ) {
            wp_delete_file( $tmp );
            return new WP_Error( 'wspdf_not_pdf', 'Upstream did not return a PDF.', [ 'status' => 502 ] );
        }

        $filename = $fn !== '' ? sanitize_file_name( $fn ) : 'document.pdf';
        if ( ! preg_match( '/\.pdf$/i', $filename ) ) {
            $filename .= '.pdf';
        }

        // Ensure nothing corrupts binary output.
        while ( ob_get_level() ) {
            ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: application/pdf' );
        header( 'X-Content-Type-Options: nosniff' );
        // dl=1 forces download (used by the viewer Download button)
        $dl = (string) $request->get_param( 'dl' );
        $disposition = ( $dl === '1' ) ? 'attachment' : 'inline';
        header( 'Content-Disposition: ' . $disposition . '; filename="' . $filename . '"' );
        header( 'Content-Length: ' . (string) filesize( $tmp ) );

        // Stream the file to the browser. Avoid loading large PDFs into memory.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        readfile( $tmp );
        wp_delete_file( $tmp );
        exit;
    }


    /**
     * /wp-json/wspdf/v1/viewer?u=<b64url(share_url)>&fn=<filename>
     * PDF.js renderer with:
     * - continuous scroll
     * - fit-to-width (uses full iframe width)
     * - text layer for screen reader access (WCAG 1.1.1, 1.3.1)
     * - full WCAG 2.2 AA toolbar and focus management
     */
    public static function rest_viewer_html( WP_REST_Request $request ) {
        $u  = (string) $request->get_param( 'u' );
        $fn = (string) $request->get_param( 'fn' );

        $share_url = self::b64url_decode( $u );
        if ( ! $share_url || ! self::is_allowed_sharepoint_url( $share_url ) ) {
            status_header( 400 );
            header( 'Content-Type: text/html; charset=utf-8' );
            echo '<!doctype html><html lang="en"><meta charset="utf-8"><p>Invalid SharePoint URL.</p></html>';
            exit;
        }

        $pdf_args = [
            'u' => self::b64url_encode( trim( $share_url ) ),
        ];
        $safe_fn = sanitize_file_name( (string) $fn );
        if ( $safe_fn !== '' ) {
            $pdf_args['fn'] = $safe_fn;
        }

        $pdf_url = add_query_arg(
            $pdf_args,
            rest_url( self::NS . '/pdf' )
        );

        $pdfjs_url  = add_query_arg( [ 'f' => 'pdf' ], rest_url( self::NS . '/asset' ) );
        $worker_url = add_query_arg( [ 'f' => 'worker' ], rest_url( self::NS . '/asset' ) );

        // Human-readable document label used in aria-label and <title>.
        $doc_label = $safe_fn !== '' ? $safe_fn : 'PDF document';

        // Download button aria-label includes filename so screen reader users
        // know exactly what they are downloading (WCAG 2.4.6).
        $download_label = $safe_fn !== ''
            ? sprintf( 'Download %s (PDF)', $safe_fn )
            : 'Download PDF';

        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );
        ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $doc_label ); ?></title>
    <style>
        html, body { height: 100%; margin: 0; }
        body { background: #f3f4f6; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
        #app { height: 100%; display: flex; flex-direction: column; }

        /* ── Toolbar ── */
        #toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: #ffffff;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            box-sizing: border-box;
            flex-shrink: 0;
        }
        #toolbar .spacer { flex: 1; }
        #toolbar button {
            appearance: none;
            border: 1px solid rgba(0,0,0,0.15);
            background: #ffffff;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            line-height: 1.2;
        }
        #toolbar button:hover { background: #f3f4f6; }
        /* WCAG 2.4.7 — visible focus indicator with sufficient contrast */
        #toolbar button:focus-visible {
            outline: 3px solid #005fcc;
            outline-offset: 2px;
        }
        #status { font-size: 13px; color: #111827; min-width: 8ch; }

        /* ── Page scroller ── */
        #scroller { flex: 1; overflow: auto; padding: 12px; box-sizing: border-box; }

        /* Each page wrapper is position:relative so the text layer can overlay the canvas */
        .page {
            position: relative;
            display: flex;
            justify-content: center;
            margin: 0 auto 14px auto;
            width: 100%;
        }
        canvas {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            border-radius: 6px;
            display: block;
        }

        /* ── PDF.js text layer (WCAG 1.1.1 / 1.3.1) ──
         * Positioned absolutely over the canvas so screen readers can traverse
         * the actual PDF text content. Visually transparent; pointer-events none
         * so mouse interaction falls through to the canvas beneath.
         * Do NOT use display:none or visibility:hidden — that removes it from
         * the accessibility tree entirely.
         */
        .textLayer {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            overflow: hidden;
            pointer-events: none;
            color: transparent;
            line-height: 1;
        }
        .textLayer span,
        .textLayer br {
            color: transparent;
            position: absolute;
            white-space: pre;
            transform-origin: 0% 0%;
            pointer-events: none;
        }

        /* Visually-hidden utility — keeps content in the accessibility tree
         * without taking up visual space. Do NOT use display:none. */
        .sr-only {
            position: absolute;
            width: 1px; height: 1px;
            padding: 0; margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>
<body>
    <div id="app">
        <!--
            role="toolbar" groups the controls for AT users.
            aria-label names the toolbar so it is distinguishable from any
            other landmarks on the page (WCAG 1.3.6, 2.4.6).
        -->
        <div id="toolbar" role="toolbar" aria-label="PDF viewer controls">

            <!-- aria-label spells out exactly what will be downloaded (WCAG 2.4.6) -->
            <button id="btnDownload" type="button"
                    aria-label="<?php echo esc_attr( $download_label ); ?>">
                Download
            </button>

            <button id="btnPrint" type="button" aria-label="Print this PDF">
                Print
            </button>

            <div class="spacer" aria-hidden="true"></div>

            <!--
                role="status" + aria-live="polite" announces load progress and
                errors to screen readers without interrupting ongoing speech
                (WCAG 4.1.3). aria-atomic="true" ensures the full message is
                read, not just the changed portion.
            -->
            <div id="status" role="status" aria-live="polite" aria-atomic="true">
                Loading PDF&#x2026;
            </div>
        </div>

        <!--
            role="region" + aria-labelledby creates a named landmark so keyboard
            users can jump directly here via their screen reader landmark
            navigation (WCAG 1.3.6, 2.4.1).
            tabindex="0" makes the region itself focusable so keyboard users
            can scroll it with arrow keys after tabbing into it.
        -->
        <div id="scroller" role="region" aria-labelledby="scrollerHeading" tabindex="0">
            <!-- Visually hidden heading labels the region for AT (WCAG 2.4.6) -->
            <h1 id="scrollerHeading" class="sr-only"><?php echo esc_html( $doc_label ); ?></h1>
        </div>
    </div>

    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
    <script src="<?php echo esc_url( $pdfjs_url ); ?>"></script>
    <script>
    (function () {
        'use strict';

        const pdfUrl    = <?php echo wp_json_encode( $pdf_url ); ?>;
        const workerUrl = <?php echo wp_json_encode( $worker_url ); ?>;
        const docLabel  = <?php echo wp_json_encode( $doc_label ); ?>;

        const statusEl    = document.getElementById('status');
        const scroller    = document.getElementById('scroller');
        const btnDownload = document.getElementById('btnDownload');
        const btnPrint    = document.getElementById('btnPrint');

        /* ── Helpers ── */

        // Tracks the last meaningful status string so transient messages
        // (e.g. "Preparing download…") can restore it afterwards.
        let currentStatus = 'Loading PDF\u2026';

        function setStatus(msg, transient) {
            statusEl.textContent = msg;
            if (!transient) currentStatus = msg;
        }

        function withDl(url) {
            try {
                const u = new URL(url, window.location.href);
                u.searchParams.set('dl', '1');
                return u.toString();
            } catch (e) {
                return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'dl=1';
            }
        }

        function triggerDownload(url) {
            const a = document.createElement('a');
            a.href = url;
            a.target = '_blank';
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            a.remove();
        }

        /* ── Toolbar button handlers ── */

        if (btnDownload) {
            btnDownload.addEventListener('click', () => {
                // Announce action to AT (WCAG 4.1.3).
                setStatus('Preparing download\u2026', true);
                triggerDownload(withDl(pdfUrl));
                setTimeout(() => setStatus(currentStatus), 1500);
            });
        }

        if (btnPrint) {
            btnPrint.addEventListener('click', () => {
                // Announce to AT that something is happening (WCAG 4.1.3).
                setStatus('Opening print dialog\u2026', true);

                const frame = document.createElement('iframe');
                frame.setAttribute('title', 'PDF print frame');
                frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
                frame.src = pdfUrl;
                document.body.appendChild(frame);

                frame.onload = () => {
                    try {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    } catch (e) {
                        // Cross-origin fallback: open in new tab so the user
                        // can use the browser's own print UI.
                        window.open(pdfUrl, '_blank', 'noopener');
                    } finally {
                        setTimeout(() => {
                            frame.remove();
                            setStatus(currentStatus);
                        }, 2000);
                    }
                };
            });
        }

        /* ── PDF.js setup ── */

        if (!window.pdfjsLib) {
            setStatus('Error: PDF viewer failed to load.');
            return;
        }
        pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

        let pdfDoc     = null;
        let pageCount  = 0;
        let pageAspect = 1.294; // height/width fallback (US Letter ~1.294)
        // pageNumber -> { wrap, canvas, textDiv, rendering, renderedOnce }
        const rendered = new Map();

        /* ── Layout helpers ── */

        function innerScrollerWidth() {
            const cs   = getComputedStyle(scroller);
            const padL = parseFloat(cs.paddingLeft)  || 0;
            const padR = parseFloat(cs.paddingRight) || 0;
            const raw  = (scroller.clientWidth || document.documentElement.clientWidth || window.innerWidth || 980) - padL - padR;
            return Math.max(320, raw);
        }

        function makeViewport(page, scale, rotation) {
            // Support both legacy getViewport(scale, rotation) and modern
            // getViewport({ scale, rotation }) signatures across PDF.js versions.
            let vp = null;
            try { vp = page.getViewport({ scale, rotation: rotation || 0 }); } catch (e) {}
            if (!vp || !Number.isFinite(vp.width) || vp.width <= 0 || !Number.isFinite(vp.height) || vp.height <= 0) {
                try { vp = (rotation !== undefined) ? page.getViewport(scale, rotation) : page.getViewport(scale); } catch (e) {}
            }
            if (!vp || !Number.isFinite(vp.width) || vp.width <= 0 || !Number.isFinite(vp.height) || vp.height <= 0) {
                try { vp = page.getViewport(1); } catch (e) {}
            }
            return vp;
        }

        async function computeAspectFromFirstPage() {
            const p1 = await pdfDoc.getPage(1);
            const v1 = makeViewport(p1, 1);
            if (v1 && v1.width > 0 && v1.height > 0) {
                pageAspect = v1.height / v1.width;
            }
        }

        /* ── DOM construction ── */

        function buildPlaceholders(numPages) {
            // Preserve the sr-only heading that labels the region.
            const heading = document.getElementById('scrollerHeading');
            scroller.innerHTML = '';
            if (heading) scroller.appendChild(heading);

            const frag = document.createDocumentFragment();
            const w = innerScrollerWidth();
            const h = Math.round(w * pageAspect);

            for (let i = 1; i <= numPages; i++) {
                const wrap = document.createElement('div');
                wrap.className = 'page';
                wrap.dataset.pageNumber = String(i);
                wrap.style.minHeight = h + 'px';

                const canvas = document.createElement('canvas');
                // role="img" + aria-label gives AT users page orientation.
                // The text layer below provides the actual readable content.
                canvas.setAttribute('role', 'img');
                canvas.setAttribute('aria-label', 'Page ' + i + ' of ' + numPages);
                canvas.style.width  = w + 'px';
                canvas.style.height = h + 'px';

                // Text layer container — overlaid on the canvas, sized to match.
                const textDiv = document.createElement('div');
                textDiv.className = 'textLayer';
                textDiv.style.width  = w + 'px';
                textDiv.style.height = h + 'px';

                wrap.appendChild(canvas);
                wrap.appendChild(textDiv);
                rendered.set(i, { wrap, canvas, textDiv, rendering: false, renderedOnce: false });
                frag.appendChild(wrap);
            }

            scroller.appendChild(frag);
        }

        function clearCanvasWhite(ctx, canvas) {
            ctx.save();
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.restore();
        }

        /* ── Manual text layer for PDF.js 2.x (e.g. 2.5.5) ──
         * In the 2.x UMD build, renderTextLayer is not reliably exposed on
         * pdfjsLib. We reconstruct it from textContent.items, which are plain
         * objects with { str, transform, width, height } fields.
         * Each item's transform is a 6-element CSS matrix [a,b,c,d,e,f] in
         * PDF coordinate space (origin bottom-left). The viewport's transform
         * maps that to canvas/CSS space (origin top-left).
         */
        function renderTextLayerManual(container, items, viewport) {
            const vt = viewport.transform; // [scaleX, skewY, skewX, scaleY, dx, dy]

            for (const item of items) {
                if (!item.str || item.str.trim() === '') continue;

                const tx = item.transform; // PDF glyph matrix
                if (!tx || tx.length < 6) continue;

                // Combine the item's glyph transform with the viewport transform.
                // Result gives us the CSS position of the glyph origin in px.
                const x = vt[0] * tx[4] + vt[2] * tx[5] + vt[4];
                const y = vt[1] * tx[4] + vt[3] * tx[5] + vt[5];

                // Font size: scale the glyph height (tx[3]) by the viewport scale.
                const fontHeight = Math.sqrt(tx[2] * tx[2] + tx[3] * tx[3]);
                const fontSize   = Math.abs(fontHeight * viewport.scale);
                if (fontSize < 1) continue;

                const span = document.createElement('span');
                span.textContent = item.str;
                span.style.cssText = [
                    'font-size:' + fontSize + 'px',
                    'left:'      + x + 'px',
                    'top:'       + (viewport.height - y) + 'px',
                    // Stretch the span to match the item's rendered width if available.
                    item.width ? 'width:' + (item.width * viewport.scale) + 'px' : '',
                ].filter(Boolean).join(';');

                container.appendChild(span);
            }
        }

        /* ── Render pipeline ── */

        async function renderPage(pageNumber) {
            if (!pdfDoc) return;
            const rec = rendered.get(pageNumber);
            if (!rec || rec.rendering) return;

            rec.rendering = true;

            try {
                const page = await pdfDoc.getPage(pageNumber);

                const w        = innerScrollerWidth();
                const vp1      = makeViewport(page, 1);
                const baseW    = (vp1 && Number.isFinite(vp1.width) && vp1.width > 0) ? vp1.width : w;
                const scale    = Math.max(0.1, w / baseW);
                const viewport = makeViewport(page, scale);

                if (!viewport || !Number.isFinite(viewport.width) || !Number.isFinite(viewport.height) || viewport.width <= 0 || viewport.height <= 0) {
                    throw new Error('Bad viewport');
                }

                const dpr = window.devicePixelRatio || 1;

                // ── Canvas render ──
                rec.canvas.style.width  = Math.round(viewport.width)  + 'px';
                rec.canvas.style.height = Math.round(viewport.height) + 'px';
                rec.canvas.width        = Math.max(1, Math.floor(viewport.width  * dpr));
                rec.canvas.height       = Math.max(1, Math.floor(viewport.height * dpr));

                const ctx = rec.canvas.getContext('2d', { alpha: true });
                clearCanvasWhite(ctx, rec.canvas);

                await page.render({
                    canvasContext: ctx,
                    viewport,
                    transform: [dpr, 0, 0, dpr, 0, 0],
                    intent: 'display'
                }).promise;

                // Canvas render succeeded — mark done before attempting text layer
                // so a text layer failure never takes down the visible render.
                rec.renderedOnce = true;
                rec.rendering    = false;

                // ── Text layer (WCAG 1.1.1, 1.3.1) ──
                // Isolated in its own try/catch: if it fails for any reason the
                // canvas render is already committed above and sighted users are
                // unaffected. AT users fall back to the canvas aria-label.
                try {
                    rec.textDiv.style.width  = Math.round(viewport.width)  + 'px';
                    rec.textDiv.style.height = Math.round(viewport.height) + 'px';
                    rec.textDiv.innerHTML    = ''; // clear any prior render on resize

                    const textContent = await page.getTextContent();
                    if (!textContent || !textContent.items || textContent.items.length === 0) {
                        // Scanned / image-only page — nothing to render.
                        return;
                    }

                    if (typeof pdfjsLib.renderTextLayer === 'function') {
                        // PDF.js 3.x+ — renderTextLayer is on pdfjsLib directly.
                        pdfjsLib.renderTextLayer({
                            textContentSource: textContent,
                            container: rec.textDiv,
                            viewport,
                            textDivs: []
                        });
                    } else {
                        // PDF.js 2.x (including 2.5.5) — renderTextLayer is NOT
                        // reliably exposed on pdfjsLib in the UMD build. Build the
                        // text layer manually from textContent.items instead.
                        renderTextLayerManual(rec.textDiv, textContent.items, viewport);
                    }
                } catch (textErr) {
                    // Silently swallow — canvas is already rendered and committed.
                }

            } catch (e) {
                rec.rendering = false;
                // Only surface canvas render errors for page 1 to avoid flooding
                // the status region on large documents.
                if (pageNumber === 1) {
                    setStatus('Error: could not render page 1. The PDF may be damaged or unsupported.');
                }
            }
        }

        /* ── Visibility / lazy rendering ── */

        function renderVisiblePages() {
            const top    = scroller.scrollTop;
            const bottom = top + scroller.clientHeight;
            for (const el of scroller.querySelectorAll('.page')) {
                const pn         = parseInt(el.dataset.pageNumber, 10);
                const rectTop    = el.offsetTop;
                const rectBottom = rectTop + el.offsetHeight;
                if (rectBottom >= top - 1200 && rectTop <= bottom + 1200) {
                    renderPage(pn);
                }
            }
        }

        function startObservers() {
            try {
                if (!('IntersectionObserver' in window)) throw new Error('no IO');
                const io = new IntersectionObserver((entries) => {
                    for (const entry of entries) {
                        if (!entry.isIntersecting) continue;
                        renderPage(parseInt(entry.target.dataset.pageNumber, 10));
                    }
                }, { root: scroller, rootMargin: '900px 0px', threshold: 0.01 });

                scroller.querySelectorAll('.page').forEach(el => io.observe(el));
            } catch (e) {
                scroller.addEventListener('scroll', () => requestAnimationFrame(renderVisiblePages), { passive: true });
                renderVisiblePages();
            }
        }

        /* ── Resize handling ── */

        let resizeTimer = null;
        function handleResize() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (!pdfDoc) return;
                const w = innerScrollerWidth();
                const h = Math.round(w * pageAspect);
                for (const rec of rendered.values()) {
                    if (!rec.renderedOnce) {
                        rec.canvas.style.width   = w + 'px';
                        rec.canvas.style.height  = h + 'px';
                        rec.textDiv.style.width  = w + 'px';
                        rec.textDiv.style.height = h + 'px';
                    }
                }
                for (const [pn, rec] of rendered.entries()) {
                    if (rec.renderedOnce) {
                        rec.rendering = false;
                        renderPage(pn);
                    }
                }
            }, 160);
        }
        window.addEventListener('resize', handleResize);

        /* ── Failure diagnostics ── */

        async function getFailureHint() {
            try {
                const r  = await fetch(pdfUrl, { method: 'GET', cache: 'no-store' });
                const ct = (r.headers.get('content-type') || '').toLowerCase();
                if (!r.ok) return 'HTTP ' + r.status;
                if (!ct.includes('application/pdf')) return 'Unexpected content-type';
                return 'Unknown';
            } catch (e) {
                return 'Network error';
            }
        }

        /* ── Init ── */

        (async function init() {
            try {
                setStatus('Loading PDF\u2026');

                const loadingTask = pdfjsLib.getDocument({
                    url: pdfUrl,
                    withCredentials: false,
                    // WordPress REST proxy does not reliably support HTTP Range/streaming.
                    disableRange:     true,
                    disableStream:    true,
                    disableAutoFetch: false
                });

                pdfDoc    = await loadingTask.promise;
                pageCount = pdfDoc.numPages;

                await computeAspectFromFirstPage();

                const pageWord = pageCount === 1 ? 'page' : 'pages';
                setStatus(docLabel + ', ' + pageCount + ' ' + pageWord + '. Use Tab to access toolbar controls.');

                buildPlaceholders(pageCount);
                startObservers();

                requestAnimationFrame(() => renderPage(1));
                requestAnimationFrame(() => renderPage(2));

            } catch (e) {
                const hint   = await getFailureHint();
                const errMsg = 'Error: failed to load PDF (' + hint + ').';
                setStatus(errMsg);

                // Move focus to the status element so keyboard and AT users are
                // immediately informed of the failure (WCAG 2.4.3).
                statusEl.setAttribute('tabindex', '-1');
                statusEl.focus();
            }
        })();

    })();
    </script>
</body>
</html>
        <?php
        exit;
    }


    /* ------------------------- Helpers ------------------------- */

    private static function is_allowed_sharepoint_url( string $url ) : bool {
        $parts = wp_parse_url( $url );
        if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
            return false;
        }
        if ( strtolower( $parts['scheme'] ) !== 'https' ) {
            return false;
        }
        return strtolower( $parts['host'] ) === self::TENANT_HOST;
    }

    private static function share_to_download_url( string $share_url ) {
        $parts = wp_parse_url( $share_url );
        if ( ! is_array( $parts ) ) {
            return new WP_Error( 'wspdf_bad_url', 'Invalid URL.', [ 'status' => 400 ] );
        }

        $path  = isset( $parts['path'] ) ? $parts['path'] : '';
        $query = [];
        if ( ! empty( $parts['query'] ) ) {
            parse_str( $parts['query'], $query );
        }

        if ( strpos( $path, '/_layouts/15/download.aspx' ) !== false && ! empty( $query['share'] ) ) {
            return $share_url;
        }

        $site = self::extract_site_from_path( (string) $path );
        if ( ! $site ) {
            return new WP_Error( 'wspdf_no_site', 'Could not determine SharePoint site from URL path.', [ 'status' => 400 ] );
        }

        $token = self::extract_share_token( $parts, $query );
        if ( ! $token ) {
            return new WP_Error( 'wspdf_no_token', 'Could not extract share token from URL.', [ 'status' => 400 ] );
        }

        return sprintf(
            'https://%s/sites/%s/_layouts/15/download.aspx?share=%s',
            self::TENANT_HOST,
            rawurlencode( $site ),
            rawurlencode( $token )
        );
    }

    /**
     * Handles:
     * - /sites/<SITE>/...
     * - /teams/<TEAM>/...
     * - /:b:/s/<SITE>/<TOKEN>...
     * - /:u:/s/<SITE>/<TOKEN>...
     * - /r/sites/<SITE>/...
     */
    private static function extract_site_from_path( string $path ) : string {
        $path = rawurldecode( trim( $path, '/' ) );
        if ( $path === '' ) {
            return '';
        }

        $segs = explode( '/', $path );

        $idx = array_search( 'sites', $segs, true );
        if ( $idx !== false && isset( $segs[ $idx + 1 ] ) ) {
            return $segs[ $idx + 1 ];
        }

        $idx = array_search( 'teams', $segs, true );
        if ( $idx !== false && isset( $segs[ $idx + 1 ] ) ) {
            return $segs[ $idx + 1 ];
        }

        $idx = array_search( 's', $segs, true );
        if ( $idx !== false && isset( $segs[ $idx + 1 ] ) ) {
            return $segs[ $idx + 1 ];
        }

        $idx = array_search( 'r', $segs, true );
        if ( $idx !== false && isset( $segs[ $idx + 2 ] ) && isset( $segs[ $idx + 1 ] ) && $segs[ $idx + 1 ] === 'sites' ) {
            return $segs[ $idx + 2 ];
        }

        return '';
    }

    private static function extract_share_token( array $parts, array $query ) : string {
        if ( ! empty( $query['share'] ) && is_string( $query['share'] ) ) {
            return $query['share'];
        }
        if ( ! empty( $query['surl'] ) && is_string( $query['surl'] ) ) {
            return $query['surl'];
        }

        $path = isset( $parts['path'] ) ? trim( (string) $parts['path'], '/' ) : '';
        if ( $path !== '' ) {
            $segs = explode( '/', $path );
            $last = end( $segs );
            if ( is_string( $last ) ) {
                $last = rawurldecode( $last );
                if ( preg_match( '/^[A-Za-z0-9\-_]{10,}$/', $last ) ) {
                    return $last;
                }
            }
        }

        return '';
    }

    private static function b64url_encode( string $data ) : string {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }

    private static function b64url_decode( string $data ) : string {
        $data = strtr( $data, '-_', '+/' );
        $pad  = strlen( $data ) % 4;
        if ( $pad ) {
            $data .= str_repeat( '=', 4 - $pad );
        }
        $decoded = base64_decode( $data, true );
        return $decoded === false ? '' : $decoded;
    }
}

Wooster_SharePoint_PDF_Block::init();