<?php
/**
 * Plugin Name:       Wooster SharePoint PDF Block
 * Description:       Gutenberg block to embed SharePoint PDFs via “Anyone” share links using a self-hosted PDF.js viewer (no Media Library uploads, no raw-PDF iframe).
 * Version:           1.0.0
 * Author:            Wooster
 * License:           GPL-2.0-or-later
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

        register_block_type(
            'wspdf/wooster-sharepoint-pdf',
            [
                'editor_script'   => $handle,
                'render_callback' => [ __CLASS__, 'render_block' ],
                'attributes'      => [
                    'shareUrl' => [ 'type' => 'string', 'default' => '' ],
                    'filename' => [ 'type' => 'string', 'default' => '' ],
                    'height'   => [ 'type' => 'number', 'default' => 900 ],
                ],
                'supports'        => [
                    'align' => [ 'wide', 'full' ],
                ],
            ]
        );
    }

    public static function render_block( array $attributes ) : string {
        $share_url = isset( $attributes['shareUrl'] ) ? trim( (string) $attributes['shareUrl'] ) : '';
        if ( $share_url === '' ) {
            return '';
        }

        $filename = isset( $attributes['filename'] ) ? sanitize_file_name( (string) $attributes['filename'] ) : '';
        $height   = isset( $attributes['height'] ) ? max( 200, (int) $attributes['height'] ) : 900;

        $viewer_url = add_query_arg(
            [
                'u'  => self::b64url_encode( $share_url ),
                'fn' => $filename,
            ],
            rest_url( self::NS . '/viewer' )
        );

        return sprintf(
            '<div class="wspdf-embed"><iframe class="wspdf-viewer" src="%s" style="width:100%%;height:%dpx;border:0;" loading="lazy" referrerpolicy="no-referrer"></iframe></div>',
            esc_url( $viewer_url ),
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
            @unlink( $tmp );
            return new WP_Error( 'wspdf_fetch_failed', $response->get_error_message(), [ 'status' => 502 ] );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            @unlink( $tmp );
            return new WP_Error( 'wspdf_bad_response', 'Failed to retrieve PDF.', [ 'status' => 502, 'code' => $code ] );
        }

        if ( ! file_exists( $tmp ) || filesize( $tmp ) < 5 ) {
            @unlink( $tmp );
            return new WP_Error( 'wspdf_empty_pdf', 'Received empty PDF stream.', [ 'status' => 502 ] );
        }

        // Sanity check: should start with "%PDF-"
        $fh   = @fopen( $tmp, 'rb' );
        $head = $fh ? fread( $fh, 5 ) : '';
        if ( $fh ) {
            fclose( $fh );
        }
        if ( $head !== '%PDF-' ) {
            @unlink( $tmp );
            return new WP_Error( 'wspdf_not_pdf', 'Upstream did not return a PDF.', [ 'status' => 502 ] );
        }

        $filename = $fn !== '' ? sanitize_file_name( $fn ) : 'document.pdf';
        if ( ! preg_match( '/\.pdf$/i', $filename ) ) {
            $filename .= '.pdf';
        }

        // Ensure nothing corrupts binary output.
        @ini_set( 'zlib.output_compression', '0' );
        while ( ob_get_level() ) {
            ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: application/pdf' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Content-Disposition: inline; filename="' . $filename . '"' );
        header( 'Content-Length: ' . (string) filesize( $tmp ) );

        readfile( $tmp );
        @unlink( $tmp );
        exit;
    }

    /**
     * /wp-json/wspdf/v1/viewer?u=<b64url(share_url)>&fn=<filename>
     * Minimal canvas-only renderer:
     * - continuous scroll
     * - fit-to-width (uses full iframe width)
     * - no text layer / no annotation UI
     */
    public static function rest_viewer_html( WP_REST_Request $request ) {
        $u  = (string) $request->get_param( 'u' );
        $fn = (string) $request->get_param( 'fn' );

        $share_url = self::b64url_decode( $u );
        if ( ! $share_url || ! self::is_allowed_sharepoint_url( $share_url ) ) {
            status_header( 400 );
            header( 'Content-Type: text/html; charset=utf-8' );
            echo '<!doctype html><meta charset="utf-8"><p>Invalid SharePoint URL.</p>';
            exit;
        }

        $pdf_url = add_query_arg(
            [
                'u'  => self::b64url_encode( trim( $share_url ) ),
                'fn' => sanitize_file_name( (string) $fn ),
            ],
            rest_url( self::NS . '/pdf' )
        );

        $pdfjs_url   = add_query_arg( [ 'f' => 'pdf' ], rest_url( self::NS . '/asset' ) );
        $worker_url  = add_query_arg( [ 'f' => 'worker' ], rest_url( self::NS . '/asset' ) );
        $title_label = $fn ? esc_html( (string) $fn ) : 'PDF';

        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );
        ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title_label; ?></title>
    <style>
        html, body { height: 100%; margin: 0; }
        body { background: #f3f4f6; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
        #app { height: 100%; display: flex; flex-direction: column; }
        #status { padding: 10px 14px; font-size: 13px; color: #111827; background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.08); }
        #scroller { flex: 1; overflow: auto; padding: 12px; box-sizing: border-box; }
        .page { display: flex; justify-content: center; margin: 0 auto 14px auto; width: 100%; }
        canvas { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.12); border-radius: 6px; display: block; }
    </style>
</head>
<body>
    <div id="app">
        <div id="status">Loading PDF…</div>
        <div id="scroller" role="document" aria-label="PDF document"></div>
    </div>

    <script src="<?php echo esc_url( $pdfjs_url ); ?>"></script>
    <script>
    (function () {
        const pdfUrl = <?php echo wp_json_encode( $pdf_url ); ?>;
        const workerUrl = <?php echo wp_json_encode( $worker_url ); ?>;

        const statusEl = document.getElementById('status');
        const scroller = document.getElementById('scroller');

        function setStatus(msg) { statusEl.textContent = msg; }

        if (!window.pdfjsLib) {
            setStatus('PDF.js failed to load.');
            return;
        }
        pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

        let pdfDoc = null;
        let pageCount = 0;
        let pageAspect = 1.294; // fallback (height/width)
        const rendered = new Map(); // pageNumber -> { canvas, rendering, renderedOnce }

        function innerScrollerWidth() {
            const cs = getComputedStyle(scroller);
            const padL = parseFloat(cs.paddingLeft) || 0;
            const padR = parseFloat(cs.paddingRight) || 0;
            const raw = (scroller.clientWidth || document.documentElement.clientWidth || window.innerWidth || 980) - padL - padR;
            return Math.max(320, raw);
        }

        async function computeAspectFromFirstPage() {
            const p1 = await pdfDoc.getPage(1);
            const v1 = p1.getViewport({ scale: 1 });
            if (v1 && v1.width > 0 && v1.height > 0) {
                pageAspect = v1.height / v1.width;
            }
        }

        function buildPlaceholders(numPages) {
            const frag = document.createDocumentFragment();
            const w = innerScrollerWidth();
            const h = Math.round(w * pageAspect);

            for (let i = 1; i <= numPages; i++) {
                const wrap = document.createElement('div');
                wrap.className = 'page';
                wrap.dataset.pageNumber = String(i);
                wrap.style.minHeight = h + 'px';

                const canvas = document.createElement('canvas');
                canvas.setAttribute('aria-label', 'Page ' + i);
                canvas.style.width = w + 'px';
                canvas.style.height = h + 'px';

                wrap.appendChild(canvas);
                rendered.set(i, { canvas, rendering: false, renderedOnce: false });
                frag.appendChild(wrap);
            }

            scroller.innerHTML = '';
            scroller.appendChild(frag);
        }

        function clearCanvasWhite(ctx, canvas) {
            ctx.save();
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.restore();
        }

        async function renderPage(pageNumber) {
            if (!pdfDoc) return;
            const rec = rendered.get(pageNumber);
            if (!rec || rec.rendering) return;

            rec.rendering = true;

            try {
                const page = await pdfDoc.getPage(pageNumber);

                const w = innerScrollerWidth();
                const viewport1 = page.getViewport({ scale: 1 });
                const scale = Math.max(0.1, w / viewport1.width);

                const viewport = page.getViewport({ scale });
                const dpr = window.devicePixelRatio || 1;

                rec.canvas.style.width = Math.round(viewport.width) + 'px';
                rec.canvas.style.height = Math.round(viewport.height) + 'px';

                rec.canvas.width  = Math.floor(viewport.width * dpr);
                rec.canvas.height = Math.floor(viewport.height * dpr);

                const ctx = rec.canvas.getContext('2d', { alpha: true });
                clearCanvasWhite(ctx, rec.canvas);

                await page.render({
                    canvasContext: ctx,
                    viewport: viewport,
                    transform: [dpr, 0, 0, dpr, 0, 0],
                    intent: 'display'
                }).promise;

                rec.renderedOnce = true;
                rec.rendering = false;
            } catch (e) {
                rec.rendering = false;
                setStatus('Render failed (page ' + pageNumber + ').');
            }
        }

        function renderVisiblePages() {
            const top = scroller.scrollTop;
            const bottom = top + scroller.clientHeight;

            for (const el of scroller.querySelectorAll('.page')) {
                const pn = parseInt(el.dataset.pageNumber, 10);
                const rectTop = el.offsetTop;
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
                        const pn = parseInt(entry.target.dataset.pageNumber, 10);
                        renderPage(pn);
                    }
                }, {
                    root: scroller,
                    rootMargin: '900px 0px',
                    threshold: 0.01
                });

                scroller.querySelectorAll('.page').forEach(el => io.observe(el));
            } catch (e) {
                scroller.addEventListener('scroll', () => requestAnimationFrame(renderVisiblePages), { passive: true });
                renderVisiblePages();
            }
        }

        let resizeTimer = null;
        function handleResize() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (!pdfDoc) return;

                const w = innerScrollerWidth();
                const h = Math.round(w * pageAspect);

                for (const rec of rendered.values()) {
                    if (!rec.renderedOnce) {
                        rec.canvas.style.width = w + 'px';
                        rec.canvas.style.height = h + 'px';
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

        async function getFailureHint() {
            try {
                const r = await fetch(pdfUrl, { method: 'GET', cache: 'no-store' });
                const ct = (r.headers.get('content-type') || '').toLowerCase();
                if (!r.ok) return 'HTTP ' + r.status;
                if (!ct.includes('application/pdf')) return 'Unexpected content-type';
                return 'Unknown';
            } catch (e) {
                return 'Network error';
            }
        }

        (async function init() {
            try {
                setStatus('Loading PDF…');

                const loadingTask = pdfjsLib.getDocument({ url: pdfUrl, withCredentials: false });
                pdfDoc = await loadingTask.promise;

                pageCount = pdfDoc.numPages;
                await computeAspectFromFirstPage();

                setStatus('Loaded ' + pageCount + ' page' + (pageCount === 1 ? '' : 's') + '.');

                buildPlaceholders(pageCount);
                startObservers();

                requestAnimationFrame(() => renderPage(1));
                requestAnimationFrame(() => renderPage(2));
            } catch (e) {
                const hint = await getFailureHint();
                setStatus('Failed to load PDF (' + hint + ').');
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