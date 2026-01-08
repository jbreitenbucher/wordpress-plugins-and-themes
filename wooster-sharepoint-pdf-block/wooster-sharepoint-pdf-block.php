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

        // Provide REST base to block.js (no debug text, no category creation here).
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

        $u = self::b64url_encode( $share_url );

        $viewer_url = add_query_arg(
            [
                'u'  => $u,
                'fn' => $filename,
            ],
            rest_url( self::NS . '/viewer' )
        );

        $iframe = sprintf(
            '<iframe class="wspdf-viewer" src="%s" style="width:100%%;height:%dpx;border:0;" loading="lazy" referrerpolicy="no-referrer"></iframe>',
            esc_url( $viewer_url ),
            (int) $height
        );

        // Wrapper helps theme spacing, but is optional.
        return '<div class="wspdf-embed">' . $iframe . '</div>';
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

        // IMPORTANT: This must *echo HTML and exit* (not JSON) to avoid "\r\n\r\n..." escaped junk.
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
     * Streams assets/pdfjs/pdf.min.js and assets/pdfjs/pdf.worker.min.js with explicit JS content-type.
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

        // Stream file with correct MIME type.
        nocache_headers();
        header( 'Content-Type: application/javascript; charset=utf-8' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Cache-Control: public, max-age=86400' );

        readfile( $path );
        exit;
    }

    /**
     * /wp-json/wspdf/v1/pdf?u=<base64url(original_share_url)>&fn=<filename>
     * Proxies the PDF bytes from SharePoint download endpoint.
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

        $response = wp_remote_get(
            $download_url,
            [
                'timeout'     => 30,
                'redirection' => 5,
                'headers'     => [
                    'Accept' => 'application/pdf',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'wspdf_fetch_failed', $response->get_error_message(), [ 'status' => 502 ] );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code < 200 || $code >= 300 || $body === '' ) {
            return new WP_Error( 'wspdf_bad_response', 'Failed to retrieve PDF.', [ 'status' => 502, 'code' => $code ] );
        }

        $filename = $fn !== '' ? sanitize_file_name( $fn ) : 'document.pdf';
        if ( ! preg_match( '/\.pdf$/i', $filename ) ) {
            $filename .= '.pdf';
        }

        // Stream PDF bytes.
        nocache_headers();
        header( 'Content-Type: application/pdf' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Content-Disposition: inline; filename="' . $filename . '"' );

        echo $body;
        exit;
    }

    /**
     * /wp-json/wspdf/v1/viewer?u=<base64url(original_share_url)>&fn=<filename>
     * Echoes minimal HTML that loads PDF.js via REST asset route and renders pages continuously, fit-to-width,
     * with NO text layer and NO annotation tools/UI.
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
                'u'  => self::b64url_encode( $share_url ),
                'fn' => sanitize_file_name( (string) $fn ),
            ],
            rest_url( self::NS . '/pdf' )
        );

        $pdfjs_url   = add_query_arg( [ 'f' => 'pdf' ], rest_url( self::NS . '/asset' ) );
        $worker_url  = add_query_arg( [ 'f' => 'worker' ], rest_url( self::NS . '/asset' ) );
        $title_label = $fn ? esc_html( (string) $fn ) : 'PDF';

        // Output real HTML (NOT JSON).
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
        #scroller { flex: 1; overflow: auto; padding: 16px 0; }
        .page {
            display: flex;
            justify-content: center;
            margin: 0 auto 14px auto;
            width: 100%;
        }
        canvas {
            width: min(100%, 980px); /* fit-to-width with a sensible max */
            height: auto;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            border-radius: 6px;
        }
        #status {
            padding: 10px 14px;
            font-size: 13px;
            color: #111827;
            background: #ffffff;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        #status small { color: #6b7280; }
    </style>
</head>
<body>
    <div id="app">
        <div id="status">Loading PDF… <small>(fit-to-width, continuous scroll)</small></div>
        <div id="scroller" role="document" aria-label="PDF document"></div>
    </div>

    <script src="<?php echo esc_url( $pdfjs_url ); ?>"></script>
    <script>
    (function () {
        const pdfUrl = <?php echo wp_json_encode( $pdf_url ); ?>;
        const workerUrl = <?php echo wp_json_encode( $worker_url ); ?>;

        const statusEl = document.getElementById('status');
        const scroller = document.getElementById('scroller');

        if (!window.pdfjsLib) {
            statusEl.textContent = 'PDF.js failed to load.';
            return;
        }

        // Explicit worker route (served via REST with proper JS MIME).
        pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

        let pdfDoc = null;
        let pageCount = 0;
        const rendered = new Map(); // pageNumber -> { canvas, rendering }

        function setStatus(msg) {
            statusEl.textContent = msg;
        }

        function fitWidthScale(page) {
            const containerWidth = Math.min(scroller.clientWidth, 980) - 24; // padding-ish
            const viewport1 = page.getViewport({ scale: 1 });
            const scale = containerWidth / viewport1.width;
            return Math.max(0.1, scale);
        }

        async function renderPage(pageNumber) {
            if (!pdfDoc) return;
            const rec = rendered.get(pageNumber);
            if (!rec || rec.rendering) return;

            rec.rendering = true;

            try {
                const page = await pdfDoc.getPage(pageNumber);
                const scale = fitWidthScale(page);

                const viewport = page.getViewport({ scale });
                const dpr = window.devicePixelRatio || 1;

                // CSS size is handled by canvas { width: ...; height: auto }.
                // Actual buffer size must be scaled for crispness.
                rec.canvas.width  = Math.floor(viewport.width * dpr);
                rec.canvas.height = Math.floor(viewport.height * dpr);

                const ctx = rec.canvas.getContext('2d', { alpha: false });
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                await page.render({
                    canvasContext: ctx,
                    viewport: viewport,
                    // No text layer, no annotation layer: pure canvas render.
                    intent: 'display',
                }).promise;

                rec.rendering = false;
            } catch (e) {
                rec.rendering = false;
            }
        }

        function buildPlaceholders(numPages) {
            const frag = document.createDocumentFragment();

            for (let i = 1; i <= numPages; i++) {
                const wrap = document.createElement('div');
                wrap.className = 'page';
                wrap.dataset.pageNumber = String(i);

                const canvas = document.createElement('canvas');
                canvas.setAttribute('aria-label', 'Page ' + i);
                wrap.appendChild(canvas);

                rendered.set(i, { canvas, rendering: false });
                frag.appendChild(wrap);
            }

            scroller.innerHTML = '';
            scroller.appendChild(frag);
        }

        function observeAndLazyRender() {
            const io = new IntersectionObserver((entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) continue;
                    const pageNumber = parseInt(entry.target.dataset.pageNumber, 10);
                    renderPage(pageNumber);
                }
            }, {
                root: scroller,
                rootMargin: '800px 0px', // pre-render ahead for smooth scrolling
                threshold: 0.01,
            });

            scroller.querySelectorAll('.page').forEach(el => io.observe(el));
        }

        let resizeTimer = null;
        function handleResize() {
            // Re-render pages that have been rendered at least once.
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (!pdfDoc) return;
                for (const [pageNumber, rec] of rendered.entries()) {
                    // If canvas has a buffer, it was rendered.
                    if (rec.canvas.width > 0 && rec.canvas.height > 0) {
                        // Mark not rendering and rerender (recompute scale).
                        rec.rendering = false;
                        renderPage(pageNumber);
                    }
                }
            }, 150);
        }

        window.addEventListener('resize', handleResize);

        (async function init() {
            try {
                setStatus('Loading PDF…');
                const loadingTask = pdfjsLib.getDocument({
                    url: pdfUrl,
                    withCredentials: false
                });
                pdfDoc = await loadingTask.promise;
                pageCount = pdfDoc.numPages;

                setStatus('Loaded ' + pageCount + ' page' + (pageCount === 1 ? '' : 's') + '.');

                buildPlaceholders(pageCount);
                observeAndLazyRender();

                // Render the first page immediately so the user sees something fast.
                renderPage(1);
            } catch (e) {
                setStatus('Failed to load PDF.');
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

    /**
     * Converts an "Anyone" share link into:
     * https://livewooster.sharepoint.com/sites/<SITE>/_layouts/15/download.aspx?share=<TOKEN>
     */
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

        // If it's already a download endpoint with a share param, accept it.
        if ( strpos( $path, '/_layouts/15/download.aspx' ) !== false && ! empty( $query['share'] ) ) {
            return $share_url;
        }

        $site = self::extract_site_from_path( $path );
        if ( ! $site ) {
            return new WP_Error( 'wspdf_no_site', 'Could not determine SharePoint site from URL path.', [ 'status' => 400 ] );
        }

        $token = self::extract_share_token( $parts, $query );
        if ( ! $token ) {
            return new WP_Error( 'wspdf_no_token', 'Could not extract share token from URL.', [ 'status' => 400 ] );
        }

        $download = sprintf(
            'https://%s/sites/%s/_layouts/15/download.aspx?share=%s',
            self::TENANT_HOST,
            rawurlencode( $site ),
            rawurlencode( $token )
        );

        return $download;
    }

    private static function extract_site_from_path( string $path ) : string {
        $path = trim( $path, '/' );
        if ( $path === '' ) {
            return '';
        }
        $segs = explode( '/', $path );

        // Typical: /sites/<SITE>/...
        $idx = array_search( 'sites', $segs, true );
        if ( $idx !== false && isset( $segs[ $idx + 1 ] ) ) {
            return $segs[ $idx + 1 ];
        }

        // Some tenants use /teams/<TEAM>/...
        $idx = array_search( 'teams', $segs, true );
        if ( $idx !== false && isset( $segs[ $idx + 1 ] ) ) {
            return $segs[ $idx + 1 ];
        }

        return '';
    }

    private static function extract_share_token( array $parts, array $query ) : string {
        // Common: ?share=<TOKEN> or ?surl=<TOKEN>
        if ( ! empty( $query['share'] ) && is_string( $query['share'] ) ) {
            return $query['share'];
        }
        if ( ! empty( $query['surl'] ) && is_string( $query['surl'] ) ) {
            return $query['surl'];
        }

        // Short share links often place the token as the last path segment:
        // https://livewooster.sharepoint.com/:b:/s/<SITE>/<TOKEN>?e=...
        $path = isset( $parts['path'] ) ? trim( (string) $parts['path'], '/' ) : '';
        if ( $path !== '' ) {
            $segs = explode( '/', $path );
            $last = end( $segs );
            if ( is_string( $last ) ) {
                $last = rawurldecode( $last );

                // Accept token-y looking segments.
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