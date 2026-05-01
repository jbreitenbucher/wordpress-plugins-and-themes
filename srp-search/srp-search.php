<?php
/**
 * Plugin Name: SRP Search
 * Plugin URI:  https://wooster.edu
 * Description: Senior Research Project search block for the College of Wooster.
 *              Provides a Gutenberg block that queries an external Microsoft SQL
 *              Server database and renders a searchable, paginated results table.
 * Version:     2.2.0
 * Author:      College of Wooster
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 * Text Domain: srp-search
 *
 * ── ARCHITECTURE OVERVIEW ────────────────────────────────────────────────────
 *
 * This plugin connects WordPress to an external MSSQL database that is not
 * accessible via $wpdb (which is MySQL-only). All DB access uses PHP's PDO
 * extension with the sqlsrv driver.
 *
 * Data flow:
 *   1. Block renders a search form via PHP (server-side render callback).
 *   2. On page load, JS fires two AJAX requests to populate the Year and Major
 *      dropdowns from the DB (cached via WordPress transients).
 *   3. On form submit, JS fires an AJAX search request. PHP builds a
 *      parameterised SQL query and returns paginated JSON results.
 *   4. JS renders results into a responsive table below the form.
 *   5. Search parameters are pushed to the URL so searches are bookmarkable.
 *
 * Block settings (stored as block attributes, editable in the inspector panel):
 *   - perPage:          results per page (25 / 50 / 100)
 *   - orderBy:          one of four ORDER BY presets (whitelist-validated)
 *   - noResultsMessage: custom no-results text
 *   - showMajor2:       toggle Major 2 column visibility
 *   - showAdvisor:      toggle Advisor column visibility
 *
 * Settings are passed from PHP to JS via data-* attributes on the block wrapper.
 * This decouples the block render from the JS logic and means multiple block
 * instances on one page can have different settings independently.
 *
 * ── REQUIRED wp-config.php CONSTANTS ─────────────────────────────────────────
 *
 *   define( 'SRP_DB_HOST',     'mssql2022.local.wooster.edu' );
 *   define( 'SRP_DB_NAME',     'R18-DataOrch-PROD' );
 *   define( 'SRP_DB_USER',     'srp_readonly' );    // read-only DB user
 *   define( 'SRP_DB_PASSWORD', 'your_password' );
 *   define( 'SRP_DB_ENCRYPT',  true );              // TLS — always true in prod
 *   define( 'SRP_DEBUG_DB',    true );              // remove in production
 *
 * ── INSTALLATION ─────────────────────────────────────────────────────────────
 *
 *   1. Add constants above to wp-config.php.
 *   2. Upload plugin folder to /wp-content/plugins/ on the network server.
 *   3. Network Admin: do NOT network-activate. Leave installed only.
 *   4. Site Admin: activate on the target site only.
 *   5. Add the SRP Search block (Wooster Blocks category) to any page.
 *
 * See README.md for full server setup, certificate install, and troubleshooting.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── PLUGIN-WIDE CONSTANTS ─────────────────────────────────────────────────────

define( 'SRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/** The MSSQL view that exposes IS project data. Square brackets required by MSSQL. */
define( 'SRP_VIEW', '[IS_TITLES]' );

/**
 * Maximum character length accepted for any text search input.
 * Enforced both client-side (maxlength attribute) and server-side (substr).
 * Prepared statements already prevent SQL injection regardless, but capping
 * length avoids unnecessary load from absurdly long inputs.
 */
const SRP_MAX_INPUT_LENGTH = 100;

/**
 * Maximum search AJAX requests allowed per IP address per 60-second window.
 * Implemented via WordPress transients — no external infrastructure needed.
 * Prevents automated scraping or accidental hammering of the MSSQL server.
 */
const SRP_RATE_LIMIT = 30;

/**
 * Whitelist map of block attribute values to SQL ORDER BY clauses.
 *
 * IMPORTANT: User-supplied order_by values are validated against this map
 * before use. Only values present here ever reach the SQL ORDER BY clause —
 * no user input is interpolated directly into SQL.
 *
 * Uses ATTENDED_AS_LAST (the name the student used while at Wooster) rather
 * than STUDENT_LAST (their legal/current name) for ordering, since ATTENDED_AS_LAST
 * is the primary display name.
 */
const SRP_ORDER_MAP = [
	'year_asc_name_asc'  => '[YEAR] ASC,  [ATTENDED_AS_LAST] ASC',
	'name_asc_year_asc'  => '[ATTENDED_AS_LAST] ASC,  [YEAR] ASC',
	'year_desc_name_asc' => '[YEAR] DESC, [ATTENDED_AS_LAST] ASC',
	'name_asc'           => '[ATTENDED_AS_LAST] ASC',
];

// ── 1. CONFIG CHECK ───────────────────────────────────────────────────────────

/**
 * Checks that all required DB connection constants are defined in wp-config.php.
 *
 * Called before any DB operation. If this returns false, all DB-dependent
 * AJAX endpoints return a 500 error and the admin sees a notice.
 *
 * @return bool True if all constants are defined.
 */
function srp_check_config(): bool {
	foreach ( [ 'SRP_DB_HOST', 'SRP_DB_NAME', 'SRP_DB_USER', 'SRP_DB_PASSWORD' ] as $c ) {
		if ( ! defined( $c ) ) return false;
	}
	return true;
}

/**
 * Returns a safe error message from a caught exception.
 *
 * In debug mode (SRP_DEBUG_DB = true): returns the raw exception message so
 * developers can diagnose connection and query failures without reading logs.
 *
 * In production: logs the real error to the WordPress debug log (wp-content/debug.log)
 * when WP_DEBUG_LOG is enabled, and returns a generic message to the visitor.
 * This keeps sensitive DB details out of public-facing error messages.
 *
 * @param \Exception $e       The caught exception.
 * @param string     $context Human-readable description of what was being attempted.
 * @return string             Safe error message for the AJAX response.
 */
function srp_error_message( \Exception $e, string $context ): string {
	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'SRP Search [' . $context . ']: ' . $e->getMessage() );
	}
	if ( defined( 'SRP_DEBUG_DB' ) && SRP_DEBUG_DB ) {
		return '[SRP DEBUG] ' . $context . ': ' . $e->getMessage();
	}
	return $context . ' — please contact the site administrator.';
}

// ── 2. ADMIN NOTICES + CACHE FLUSH ───────────────────────────────────────────

/**
 * Shows admin notices for:
 *   - Missing wp-config.php constants (error)
 *   - Debug mode driver/PHP status (info, only when SRP_DEBUG_DB = true)
 *   - Cache flush confirmation (success, after flushing)
 *   - Cache management link (info, admins only)
 */
add_action( 'admin_notices', function () {
	if ( ! srp_check_config() ) {
		echo '<div class="notice notice-error"><p><strong>SRP Search:</strong> Database constants missing from <code>wp-config.php</code>. Required: SRP_DB_HOST, SRP_DB_NAME, SRP_DB_USER, SRP_DB_PASSWORD.</p></div>';
	}

	if ( defined( 'SRP_DEBUG_DB' ) && SRP_DEBUG_DB ) {
		$pdo_status    = extension_loaded( 'pdo_sqlsrv' ) ? 'loaded' : 'NOT loaded';
		$sqlsrv_status = extension_loaded( 'sqlsrv' )     ? 'loaded' : 'NOT loaded';
		printf(
			'<div class="notice notice-info"><p><strong>SRP Debug:</strong> pdo_sqlsrv: %s | sqlsrv: %s | PHP %s | SAPI: %s</p></div>',
			esc_html( $pdo_status ), esc_html( $sqlsrv_status ),
			esc_html( PHP_VERSION ), esc_html( PHP_SAPI )
		);
	}

	// Shown after a successful cache flush redirect.
	if ( isset( $_GET['srp_flushed'] ) && '1' === $_GET['srp_flushed'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p><strong>SRP Search:</strong> Year and major caches cleared successfully.</p></div>';
	}

	// Cache management — visible to admins only.
	// The flush URL includes a WordPress nonce so only authenticated admins
	// with manage_options capability can trigger a cache clear.
	if ( current_user_can( 'manage_options' ) ) {
		$flush_url = wp_nonce_url( add_query_arg( 'srp_flush_cache', '1' ), 'srp_flush_cache' );
		printf(
			'<div class="notice notice-info"><p><strong>SRP Search:</strong> Years cached 24 h · Majors cached 7 days. <a href="%s">Clear cache now</a></p></div>',
			esc_url( $flush_url )
		);
	}
} );

/**
 * Handles the cache flush admin action.
 *
 * Triggered by the "Clear cache now" link in the admin notice.
 * Validates the nonce and capability before deleting transients.
 * Redirects back to the same page with a confirmation parameter.
 *
 * Lock transients are also cleared so the next page load can immediately
 * repopulate the cache rather than waiting for the 10-second lock to expire.
 */
add_action( 'admin_init', function () {
	if ( ! isset( $_GET['srp_flush_cache'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	check_admin_referer( 'srp_flush_cache' );

	delete_transient( 'srp_years' );
	delete_transient( 'srp_majors' );
	delete_transient( 'srp_years_lock' );
	delete_transient( 'srp_majors_lock' );

	wp_safe_redirect( add_query_arg( [ 'srp_flush_cache' => false, 'srp_flushed' => '1' ] ) );
	exit;
} );

/**
 * Cleans up transients when the plugin is deactivated.
 * Prevents stale cached data from persisting in the WordPress options table.
 */
register_deactivation_hook( __FILE__, function () {
	delete_transient( 'srp_years' );
	delete_transient( 'srp_majors' );
	delete_transient( 'srp_years_lock' );
	delete_transient( 'srp_majors_lock' );
} );

// ── 3. PDO CONNECTION ─────────────────────────────────────────────────────────

/**
 * Creates and returns a PDO connection to the external MSSQL database.
 *
 * WHY PDO INSTEAD OF $wpdb:
 * WordPress's $wpdb class only supports MySQL/MariaDB. Connecting to an external
 * Microsoft SQL Server requires PDO with the sqlsrv driver, which is Microsoft's
 * official PHP extension for SQL Server. The phpcs ignores below suppress the
 * WordPress coding standards linter which flags any PDO usage — the linter
 * assumes all DB access goes through $wpdb, which is impossible here.
 *
 * CONNECTION SECURITY:
 * - Encrypt=yes enforces TLS on the connection (set via SRP_DB_ENCRYPT constant).
 * - TrustServerCertificate=no requires the server certificate to be verified
 *   against the CA certificate installed on the WordPress server.
 * - LoginTimeout=5 is set in the DSN string because PDO::ATTR_TIMEOUT is not
 *   supported by the sqlsrv driver and throws an IMSSP exception if passed
 *   in the options array.
 * - The DB user (srp_readonly) has SELECT-only access to one view.
 *
 * @return PDO                Active PDO connection.
 * @throws \RuntimeException  On connection failure (caught by callers).
 */
function srp_get_pdo(): PDO { // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
	$host    = SRP_DB_HOST;
	$db      = SRP_DB_NAME;
	$encrypt = defined( 'SRP_DB_ENCRYPT' ) ? ( SRP_DB_ENCRYPT ? 'yes' : 'no' ) : 'yes';
	$dsn     = "sqlsrv:Server={$host};Database={$db};Encrypt={$encrypt};TrustServerCertificate=no;LoginTimeout=5";

	return new PDO( $dsn, SRP_DB_USER, SRP_DB_PASSWORD, [ // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
	] );
}

// ── 4. RATE LIMITING ──────────────────────────────────────────────────────────

/**
 * Transient-based rate limiter for the search AJAX endpoint.
 *
 * Tracks request counts per IP address using WordPress transients with a
 * 60-second TTL. No external infrastructure (Redis, Memcached) required.
 *
 * WHY TRANSIENTS:
 * WordPress transients are stored in the database by default, but automatically
 * use an object cache (Redis/Memcached) if one is configured — so this scales
 * without code changes if the university adds a cache layer later.
 *
 * LIMITATION:
 * IP-based limiting can be bypassed by rotating IPs, and shared IPs (NAT,
 * university proxy) could affect multiple users. For a research tool with
 * legitimate academic use this is an acceptable tradeoff — the limit of 30
 * requests/minute is high enough not to impact normal usage.
 *
 * @return bool True if the request should be blocked (limit exceeded).
 */
function srp_is_rate_limited(): bool {
	$ip    = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
	$key   = 'srp_rl_' . md5( $ip ); // md5 keeps the key short and consistent
	$count = (int) get_transient( $key );
	if ( $count >= SRP_RATE_LIMIT ) return true;
	set_transient( $key, $count + 1, 60 );
	return false;
}

// ── 5. BLOCK REGISTRATION ─────────────────────────────────────────────────────

/**
 * Registers the srp/search block using build/block.json for metadata.
 *
 * block.json defines: name, title, category (wbp-content / Wooster Blocks),
 * icon, description, supported alignments, and block attributes with defaults.
 *
 * The render_callback means this is a server-side rendered (SSR) block —
 * the save() function in index.js returns null and WordPress calls
 * srp_render_block() on every page load. This ensures the search form always
 * reflects current block settings without requiring a post re-save.
 */
add_action( 'init', function () {
	register_block_type( SRP_PLUGIN_DIR . 'build', [ 'render_callback' => 'srp_render_block' ] );
} );

// ── 6. WOOSTER BLOCKS CATEGORY ────────────────────────────────────────────────

/**
 * Ensures the Wooster Blocks category exists at the top of the block inserter.
 *
 * Safe to run alongside other Wooster plugins (e.g. Network Featured Posts)
 * that register the same category — deduplicates the entry rather than
 * creating a second one. Priority 0 keeps it pinned above default categories.
 *
 * @param array        $categories Registered block categories.
 * @param WP_Post|null $post       Post being edited.
 * @return array                   Modified categories array.
 */
function srp_filter_block_categories( array $categories, $post ): array {
	$target      = [ 'slug' => 'wbp-content', 'title' => __( 'Wooster Blocks', 'srp-search' ), 'icon' => null ];
	$found_index = null;
	foreach ( $categories as $i => $cat ) {
		if ( isset( $cat['slug'] ) && 'wbp-content' === $cat['slug'] ) { $found_index = $i; break; }
	}
	if ( null !== $found_index ) array_splice( $categories, $found_index, 1 );
	array_unshift( $categories, $target );
	return $categories;
}
add_filter( 'block_categories_all', 'srp_filter_block_categories', 0, 2 );

// ── 7. ASSETS ─────────────────────────────────────────────────────────────────

/**
 * Passes server-side data to the frontend JS via wp_localize_script().
 *
 * srpData is available globally in frontend.js as window.srpData.
 * Includes the AJAX URL, a security nonce, debug flag, timeout, and input
 * length cap so the JS enforces the same limits as the PHP without hardcoding.
 *
 * WHY NONCE:
 * Every AJAX request includes this nonce which WordPress validates via
 * check_ajax_referer(). This prevents cross-site request forgery — a malicious
 * site cannot trigger our AJAX endpoints on behalf of a logged-in user.
 *
 * @param string $handle The registered script handle to attach data to.
 */
function srp_localize( string $handle ): void {
	wp_localize_script( $handle, 'srpData', [
		'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
		'nonce'       => wp_create_nonce( 'srp_search_nonce' ),
		'debug'       => ( defined( 'SRP_DEBUG_DB' ) && SRP_DEBUG_DB ),
		'ajaxTimeout' => 15000,          // ms — consistent timeout across all AJAX calls
		'maxInputLen' => SRP_MAX_INPUT_LENGTH,
	] );
}

// Enqueue on the front end for visitors.
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'srp-search-frontend', SRP_PLUGIN_URL . 'build/frontend.js',
		[ 'jquery' ], filemtime( SRP_PLUGIN_DIR . 'build/frontend.js' ), true );
	srp_localize( 'srp-search-frontend' );
} );

// Also enqueue in the block editor so ServerSideRender previews are interactive.
// The MutationObserver in frontend.js detects when SSR injects the form into
// the editor canvas and binds the AJAX handlers to it.
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_script( 'srp-search-frontend-editor', SRP_PLUGIN_URL . 'build/frontend.js',
		[ 'jquery' ], filemtime( SRP_PLUGIN_DIR . 'build/frontend.js' ), true );
	srp_localize( 'srp-search-frontend-editor' );
} );

// ── 8. RENDER CALLBACK ────────────────────────────────────────────────────────

/**
 * Server-side renders the search block HTML.
 *
 * Called by WordPress on every page load (not cached in post content).
 * Also called by ServerSideRender in the block editor for live preview.
 *
 * BLOCK ATTRIBUTE → DATA ATTRIBUTE PATTERN:
 * Block settings are written as data-* attributes on the outermost div.
 * frontend.js reads these attributes when binding each block instance.
 * This means multiple blocks on one page can have different settings
 * (different sort orders, different per-page counts, etc.) independently,
 * because each block's JS instance reads only its own wrapper's attributes.
 *
 * URL STATE:
 * The render callback reads srp_* query parameters from the URL to pre-fill
 * search fields and trigger an auto-run search on page load. This makes
 * searches bookmarkable and shareable. No nonce is needed here because these
 * are read-only GET params used only to populate form fields — no privileged
 * action is taken. The actual search runs through the nonce-protected AJAX handler.
 *
 * UNIQUE IDs:
 * A static counter generates unique IDs per block instance (srp-1, srp-2, etc.)
 * so multiple blocks on one page have non-colliding element IDs.
 *
 * @param array  $attributes Block attributes from the editor.
 * @param string $content    Inner block content (unused — no inner blocks).
 * @return string            HTML output for the block.
 */
function srp_render_block( array $attributes, string $content ): string {
	static $instance = 0;
	$instance++;
	$uid = 'srp-' . $instance;

	// Sanitize block attributes with safe fallbacks.
	// perPage is whitelisted to prevent arbitrary LIMIT values in SQL.
	$per_page       = in_array( (int) ( $attributes['perPage'] ?? 25 ), [ 25, 50, 100 ], true )
	                  ? (int) $attributes['perPage'] : 25;
	$no_results_msg = esc_html( $attributes['noResultsMessage'] ?? 'No projects found matching your search.' );
	$show_major2    = (bool) ( $attributes['showMajor2']  ?? true );
	$show_advisor   = (bool) ( $attributes['showAdvisor'] ?? true );
	$order_by_key   = $attributes['orderBy'] ?? 'year_asc_name_asc';
	if ( ! array_key_exists( $order_by_key, SRP_ORDER_MAP ) ) $order_by_key = 'year_asc_name_asc';

	// Read URL state for bookmarkable searches.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	// These $_GET reads are intentionally nonce-free — they are read-only,
	// sanitized, and used only to pre-populate form fields. No data is written
	// and no privileged action is taken. The nonce check occurs in the AJAX
	// handler (srp_ajax_search) when the search actually executes.
	$url_last_name = sanitize_text_field( wp_unslash( $_GET['srp_last_name'] ?? '' ) );
	$url_year      = sanitize_text_field( wp_unslash( $_GET['srp_year']      ?? '' ) );
	$url_title     = sanitize_text_field( wp_unslash( $_GET['srp_title']     ?? '' ) );
	$url_major     = sanitize_text_field( wp_unslash( $_GET['srp_major']     ?? '' ) );
	$url_advisor   = sanitize_text_field( wp_unslash( $_GET['srp_advisor']   ?? '' ) );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$has_url_state = ( $url_last_name || $url_year || $url_title || $url_major || $url_advisor );

	ob_start();
	?>
	<div class="srp-search-wrap"
		id="<?php echo esc_attr( $uid ); ?>"
		data-srp-uid="<?php echo esc_attr( $uid ); ?>"
		data-srp-per-page="<?php echo esc_attr( $per_page ); ?>"
		data-srp-no-results="<?php echo esc_attr( $no_results_msg ); ?>"
		data-srp-show-major2="<?php echo $show_major2  ? '1' : '0'; ?>"
		data-srp-show-advisor="<?php echo $show_advisor ? '1' : '0'; ?>"
		data-srp-order-by="<?php echo esc_attr( $order_by_key ); ?>"
		data-srp-autorun="<?php echo $has_url_state ? '1' : '0'; ?>">

		<form class="srp-form" novalidate>
			<div class="srp-fields">

				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-last-name">Last Name</label>
					<input type="text"
						id="<?php echo esc_attr( $uid ); ?>-last-name"
						name="last_name" placeholder="e.g. Smith"
						autocomplete="off"
						maxlength="<?php echo esc_attr( SRP_MAX_INPUT_LENGTH ); ?>"
						value="<?php echo esc_attr( $url_last_name ); ?>" />
				</div>

				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-year">Year</label>
					<?php // Year is a dropdown populated via AJAX from the DB (newest first). ?>
					<?php // data-srp-selected pre-selects a year from URL state. ?>
					<select id="<?php echo esc_attr( $uid ); ?>-year" name="year"
						data-srp-selected="<?php echo esc_attr( $url_year ); ?>">
						<option value="">— Any Year —</option>
					</select>
				</div>

				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-title">Title Contains</label>
					<?php // Title search: multiple words are split and each must appear (AND logic). ?>
					<?php // The hint span is position:absolute so it does not affect field alignment. ?>
					<input type="text"
						id="<?php echo esc_attr( $uid ); ?>-title"
						name="title" placeholder="e.g. climate change"
						autocomplete="off"
						maxlength="<?php echo esc_attr( SRP_MAX_INPUT_LENGTH ); ?>"
						value="<?php echo esc_attr( $url_title ); ?>" />
					<span class="srp-field-hint">Multiple words means all words must be in the title</span>
				</div>

				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-major">Major</label>
					<?php // Major dropdown populated via AJAX from MAJOR_1_DESC and MAJOR_2_DESC. ?>
					<?php // Searches against description columns, not the 4-character codes. ?>
					<select id="<?php echo esc_attr( $uid ); ?>-major" name="major"
						data-srp-selected="<?php echo esc_attr( $url_major ); ?>">
						<option value="">— Any Major —</option>
					</select>
				</div>

				<?php if ( $show_advisor ) : ?>
				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-advisor">Advisor</label>
					<input type="text"
						id="<?php echo esc_attr( $uid ); ?>-advisor"
						name="advisor" placeholder="e.g. Jones"
						autocomplete="off"
						maxlength="<?php echo esc_attr( SRP_MAX_INPUT_LENGTH ); ?>"
						value="<?php echo esc_attr( $url_advisor ); ?>" />
				</div>
				<?php endif; ?>

			</div>
			<div class="srp-form-footer">
				<p class="srp-validation-msg" role="alert" aria-live="polite"></p>
				<button type="submit" class="srp-submit">
					<span class="srp-submit-label">Search</span>
					<span class="srp-submit-spinner" aria-hidden="true"></span>
				</button>
			</div>
		</form>

		<div class="srp-results" aria-live="polite" aria-label="Search results"></div>

		<?php // Load More section — hidden until a search returns more results than perPage. ?>
		<div class="srp-load-more-wrap" style="display:none;">
			<p class="srp-showing-count" aria-live="polite"></p>
			<button class="srp-load-more" type="button">Load More</button>
		</div>

	</div>
	<?php
	return ob_get_clean();
}

// ── 9. AJAX: DIAGNOSTIC ───────────────────────────────────────────────────────

/**
 * Diagnostic AJAX endpoint — run srp_run_diagnostic() in the browser console.
 *
 * Returns a JSON object with:
 *   - PHP version and SAPI (CLI vs fpm-fcgi vs apache2handler)
 *   - Whether pdo_sqlsrv and sqlsrv extensions are loaded in THIS SAPI
 *   - Whether DB constants are defined
 *   - Whether a PDO connection can be established
 *   - Whether a test query against IS_TITLES succeeds
 *
 * WHY SAPI MATTERS:
 * The PHP CLI and the web server (Apache mod_php) have separate php.ini files
 * on SUSE Linux. Extensions installed for CLI may not be loaded for the web
 * server. This is the most common cause of "works in terminal, fails on the site."
 *
 * Available to both logged-in and anonymous users because it's needed during
 * initial setup before any user accounts may exist. Does not expose sensitive
 * data — only extension status and connection success/failure.
 */
add_action( 'wp_ajax_srp_check',        'srp_ajax_check' );
add_action( 'wp_ajax_nopriv_srp_check', 'srp_ajax_check' );

function srp_ajax_check(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );
	$report = [
		'php_version' => PHP_VERSION, 'php_sapi' => PHP_SAPI,
		'pdo_sqlsrv'  => extension_loaded( 'pdo_sqlsrv' ),
		'sqlsrv'      => extension_loaded( 'sqlsrv' ),
		'config_ok'   => srp_check_config(),
		'connection'  => false, 'connection_err' => '',
		'query_test'  => false, 'query_err'      => '',
	];
	if ( $report['pdo_sqlsrv'] && $report['config_ok'] ) {
		try {
			$pdo = srp_get_pdo();
			$report['connection'] = true;
			$stmt = $pdo->query( 'SELECT TOP 1 [STUDENT_LAST] FROM ' . SRP_VIEW );
			$report['query_test'] = ( $stmt !== false );
		} catch ( \Exception $e ) {
			$report['connection_err'] = $e->getMessage();
		}
	}
	wp_send_json_success( $report );
}

// ── 10. AJAX: LOAD YEARS ──────────────────────────────────────────────────────

add_action( 'wp_ajax_srp_get_years',        'srp_ajax_get_years' );
add_action( 'wp_ajax_nopriv_srp_get_years', 'srp_ajax_get_years' );

/**
 * Returns distinct years from the IS_TITLES view for the year dropdown.
 *
 * CACHING:
 * Results are cached in a WordPress transient (srp_years) for 24 hours.
 * New IS projects are typically added once a year (April–June), so daily
 * expiry is appropriate. Admins can force a refresh via the admin notice link.
 *
 * STAMPEDE PROTECTION:
 * When the cache expires, multiple simultaneous page loads could all try to
 * query the DB at once. A short-lived lock transient (srp_years_lock, 10 sec)
 * ensures only one request populates the cache. Others receive a retry signal
 * and the JS retries after 2 seconds. This prevents thundering herd against MSSQL.
 *
 * ORDER:
 * Returns newest year first (DESC) so the most recent cohort is at the top
 * of the dropdown without scrolling.
 */
function srp_ajax_get_years(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );
	if ( ! srp_check_config() ) {
		wp_send_json_error( [ 'message' => 'Database not configured.' ], 500 );
	}

	$years = get_transient( 'srp_years' );
	if ( false !== $years ) {
		wp_send_json_success( [ 'years' => $years, 'cached' => true ] );
		return;
	}

	if ( get_transient( 'srp_years_lock' ) ) {
		wp_send_json_success( [ 'years' => [], 'cached' => false, 'retry' => true ] );
		return;
	}

	set_transient( 'srp_years_lock', 1, 10 );
	try {
		$pdo   = srp_get_pdo();
		$stmt  = $pdo->query( 'SELECT DISTINCT [YEAR] FROM ' . SRP_VIEW . ' WHERE [YEAR] IS NOT NULL ORDER BY [YEAR] DESC' );
		$years = $stmt->fetchAll( PDO::FETCH_COLUMN ); // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
		set_transient( 'srp_years', $years, DAY_IN_SECONDS );
		delete_transient( 'srp_years_lock' );
		wp_send_json_success( [ 'years' => $years, 'cached' => false ] );
	} catch ( \Exception $e ) {
		delete_transient( 'srp_years_lock' );
		wp_send_json_error( [ 'message' => srp_error_message( $e, 'Could not load years' ) ], 500 );
	}
}

// ── 11. AJAX: LOAD MAJORS ─────────────────────────────────────────────────────

add_action( 'wp_ajax_srp_get_majors',        'srp_ajax_get_majors' );
add_action( 'wp_ajax_nopriv_srp_get_majors', 'srp_ajax_get_majors' );

/**
 * Returns distinct majors from the IS_TITLES view for the major dropdown.
 *
 * Uses MAJOR_1_DESC and MAJOR_2_DESC (human-readable descriptions) rather than
 * MAJOR_1 and MAJOR_2 (4-character department codes). The UNION deduplicates
 * across both columns so each major appears once regardless of whether it
 * appears as a first or second major.
 *
 * Cached for 7 days (WEEK_IN_SECONDS) — curriculum changes are rare.
 * Same stampede protection pattern as srp_ajax_get_years().
 */
function srp_ajax_get_majors(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );
	if ( ! srp_check_config() ) {
		wp_send_json_error( [ 'message' => 'Database not configured.' ], 500 );
	}

	$majors = get_transient( 'srp_majors' );
	if ( false !== $majors ) {
		wp_send_json_success( [ 'majors' => $majors, 'cached' => true ] );
		return;
	}

	if ( get_transient( 'srp_majors_lock' ) ) {
		wp_send_json_success( [ 'majors' => [], 'cached' => false, 'retry' => true ] );
		return;
	}

	set_transient( 'srp_majors_lock', 1, 10 );
	try {
		$pdo    = srp_get_pdo();
		$sql    = "SELECT DISTINCT [MAJOR_1_DESC] AS major FROM " . SRP_VIEW . " WHERE [MAJOR_1_DESC] IS NOT NULL AND [MAJOR_1_DESC] <> ''
                  UNION
                  SELECT DISTINCT [MAJOR_2_DESC] FROM " . SRP_VIEW . " WHERE [MAJOR_2_DESC] IS NOT NULL AND [MAJOR_2_DESC] <> ''
                  ORDER BY major ASC";
		$stmt   = $pdo->query( $sql );
		$majors = $stmt->fetchAll( PDO::FETCH_COLUMN ); // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
		set_transient( 'srp_majors', $majors, WEEK_IN_SECONDS );
		delete_transient( 'srp_majors_lock' );
		wp_send_json_success( [ 'majors' => $majors, 'cached' => false ] );
	} catch ( \Exception $e ) {
		delete_transient( 'srp_majors_lock' );
		wp_send_json_error( [ 'message' => srp_error_message( $e, 'Could not load majors' ) ], 500 );
	}
}

// ── 12. AJAX: SEARCH ──────────────────────────────────────────────────────────

add_action( 'wp_ajax_srp_search',        'srp_ajax_search' );
add_action( 'wp_ajax_nopriv_srp_search', 'srp_ajax_search' );

/**
 * Executes the search query and returns paginated results as JSON.
 *
 * INPUT HANDLING:
 * All text inputs are sanitized via sanitize_text_field() and capped at
 * SRP_MAX_INPUT_LENGTH characters. Year is validated as a numeric string.
 * Major and advisor are sanitized and length-capped.
 *
 * SQL INJECTION PREVENTION:
 * All search values are passed as PDO bound parameters (:last_name, :year, etc.).
 * The ORDER BY clause uses only values from the SRP_ORDER_MAP whitelist —
 * the user-supplied order_by string selects a pre-written clause, never
 * reaches SQL directly.
 *
 * MSSQL PAGINATION:
 * MSSQL's OFFSET/FETCH NEXT syntax requires integer literals, not bound
 * parameters, for row counts. Both $offset and $per_page are validated integers
 * (whitelist for per_page, max(0, int cast) for offset) before interpolation,
 * so inlining them is safe. The phpcs ignore suppresses the linter warning.
 *
 * TITLE SEARCH — AND LOGIC:
 * Multiple words in the title field are split on whitespace and each generates
 * a separate LIKE clause joined by AND. "climate change" returns only titles
 * containing both "climate" AND "change" in any order. Single words work as before.
 *
 * LAST NAME SEARCH — OR ACROSS TWO COLUMNS:
 * Searches both ATTENDED_AS_LAST (name used while at Wooster) and STUDENT_LAST
 * (legal/current name). This handles students who changed their name (e.g. marriage)
 * so searching either name finds the record. Display uses ATTENDED_AS_LAST as
 * primary with STUDENT_LAST in parentheses if they differ.
 *
 * RESPONSE:
 * Returns results array, count of this page, total matching records,
 * current offset, per_page, and has_more flag for the Load More button.
 */
function srp_ajax_search(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );

	// Rate limiting — checked before any DB work to fail fast.
	if ( srp_is_rate_limited() ) {
		wp_send_json_error( [ 'message' => 'Too many requests — please wait a moment and try again.' ], 429 );
	}

	if ( ! srp_check_config() ) {
		wp_send_json_error( [ 'message' => 'Database not configured.' ], 500 );
	}

	// Sanitize and length-cap all text inputs.
	$last_name = substr( sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ), 0, SRP_MAX_INPUT_LENGTH );
	$year      = sanitize_text_field( wp_unslash( $_POST['year']      ?? '' ) );
	$title     = substr( sanitize_text_field( wp_unslash( $_POST['title']     ?? '' ) ), 0, SRP_MAX_INPUT_LENGTH );
	$major     = substr( sanitize_text_field( wp_unslash( $_POST['major']     ?? '' ) ), 0, SRP_MAX_INPUT_LENGTH );
	$advisor   = substr( sanitize_text_field( wp_unslash( $_POST['advisor']   ?? '' ) ), 0, SRP_MAX_INPUT_LENGTH );

	// Whitelist per_page — prevents arbitrary LIMIT values in SQL.
	$per_page = in_array( (int) sanitize_text_field( wp_unslash( $_POST['per_page'] ?? '25' ) ), [ 25, 50, 100 ], true )
	            ? (int) sanitize_text_field( wp_unslash( $_POST['per_page'] ?? '25' ) ) : 25;
	$offset   = max( 0, (int) sanitize_text_field( wp_unslash( $_POST['offset'] ?? '0' ) ) );

	// Validate order_by against whitelist — never interpolate user input into ORDER BY.
	$order_by_key = sanitize_text_field( wp_unslash( $_POST['order_by'] ?? 'year_asc_name_asc' ) );
	if ( ! array_key_exists( $order_by_key, SRP_ORDER_MAP ) ) $order_by_key = 'year_asc_name_asc';
	$order_clause = SRP_ORDER_MAP[ $order_by_key ];

	// Require at least one field.
	if ( $last_name === '' && $year === '' && $title === '' && $major === '' && $advisor === '' ) {
		wp_send_json_error( [ 'message' => 'Please enter at least one search field.' ], 422 );
	}

	if ( $year !== '' && ! ctype_digit( $year ) ) {
		wp_send_json_error( [ 'message' => 'Please select a valid graduation year.' ], 422 );
	}

	// Build WHERE clause dynamically — only add conditions for filled fields.
	$where = []; $params = [];

	if ( $last_name !== '' ) {
		// Search both ATTENDED_AS_LAST and STUDENT_LAST so students who changed
		// their name can be found by either their current or former name.
		$where[] = '([ATTENDED_AS_LAST] LIKE :last_name OR [STUDENT_LAST] LIKE :last_name2)';
		$params[':last_name']  = '%' . $last_name . '%';
		$params[':last_name2'] = '%' . $last_name . '%';
	}
	if ( $year !== '' ) {
		$where[]       = '[YEAR] = :year';
		$params[':year'] = (int) $year;
	}
	if ( $title !== '' ) {
		// Split title into words — each word generates its own LIKE clause (AND logic).
		// "climate change" → IS_TITLE LIKE '%climate%' AND IS_TITLE LIKE '%change%'
		$words = preg_split( '/\s+/', trim( $title ), -1, PREG_SPLIT_NO_EMPTY );
		foreach ( $words as $i => $word ) {
			$key            = ':title_word_' . $i;
			$where[]        = '[IS_TITLE] LIKE ' . $key;
			$params[ $key ] = '%' . $word . '%';
		}
	}
	if ( $major !== '' ) {
		// Search description columns — human-readable names, not 4-character codes.
		$where[] = '([MAJOR_1_DESC] = :major OR [MAJOR_2_DESC] = :major2)';
		$params[':major']  = $major;
		$params[':major2'] = $major;
	}
	if ( $advisor !== '' ) {
		$where[]            = '[ADVISOR_LAST] LIKE :advisor';
		$params[':advisor'] = '%' . $advisor . '%';
	}

	$where_clause = 'WHERE ' . implode( ' AND ', $where );
	$count_sql    = "SELECT COUNT(*) FROM " . SRP_VIEW . " {$where_clause}";

	// OFFSET and FETCH NEXT must be integer literals in MSSQL — bound parameters
	// are rejected with "row count parameter must be an integer" error.
	// Both values are validated above so interpolation is safe here.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$result_sql = "SELECT [STUDENT_FIRST],[STUDENT_LAST],[ATTENDED_AS_LAST],[YEAR],[IS_TITLE],[MAJOR_1_DESC],[MAJOR_2_DESC],[ADVISOR_FIRST],[ADVISOR_LAST]
	               FROM " . SRP_VIEW . " {$where_clause}
	               ORDER BY {$order_clause}
	               OFFSET {$offset} ROWS FETCH NEXT {$per_page} ROWS ONLY";

	try {
		$pdo        = srp_get_pdo();
		$count_stmt = $pdo->prepare( $count_sql );
		$count_stmt->execute( $params );
		$total      = (int) $count_stmt->fetchColumn();
		$stmt       = $pdo->prepare( $result_sql );
		$stmt->execute( $params );
		$rows       = $stmt->fetchAll();

		wp_send_json_success( [
			'results'  => $rows,
			'count'    => count( $rows ),
			'total'    => $total,
			'offset'   => $offset,
			'per_page' => $per_page,
			'has_more' => ( $offset + count( $rows ) ) < $total,
		] );
	} catch ( \Exception $e ) {
		wp_send_json_error( [ 'message' => srp_error_message( $e, 'Search failed' ) ], 500 );
	}
}
