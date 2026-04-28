<?php
/**
 * Plugin Name: SRP Search
 * Plugin URI:  https://wooster.edu
 * Description: Senior Research Project search block for the College of Wooster. Queries an external MSSQL database via PDO with SSL.
 * Version:     1.2.0
 * Author:      College of Wooster
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * Text Domain: srp-search
 *
 * Installation:
 * 1. Add the following constants to wp-config.php:
 *    define( 'SRP_DB_HOST',     'mssql2022.local.wooster.edu' );
 *    define( 'SRP_DB_NAME',     'R18-DataOrch-PROD' );
 *    define( 'SRP_DB_USER',     'srp_readonly' );
 *    define( 'SRP_DB_PASSWORD', 'your_strong_password_here' );
 *    define( 'SRP_DB_ENCRYPT',  true );
 *    define( 'SRP_DEBUG_DB',    true );  // set false or remove in production
 *
 * 2. Upload to /wp-content/plugins/ on the network.
 * 3. Network Admin: install (do NOT network-activate).
 * 4. Site Admin: activate on the target site only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SRP_VIEW',       '[IS_TITLES]' );

// ── 1. CONFIG CHECK ───────────────────────────────────────────────────────────

function srp_check_config(): bool {
	foreach ( [ 'SRP_DB_HOST', 'SRP_DB_NAME', 'SRP_DB_USER', 'SRP_DB_PASSWORD' ] as $c ) {
		if ( ! defined( $c ) ) return false;
	}
	return true;
}

/**
 * Returns a safe error message.
 * If SRP_DEBUG_DB is true, returns the real exception message.
 * In production (SRP_DEBUG_DB false or absent) returns a generic message.
 */
function srp_error_message( \Exception $e, string $context ): string {
	if ( defined( 'SRP_DEBUG_DB' ) && SRP_DEBUG_DB ) {
		return "[SRP DEBUG] {$context}: " . $e->getMessage();
	}
	return $context . ' — please contact the site administrator.';
}

add_action( 'admin_notices', function () {
	if ( ! srp_check_config() ) {
		echo '<div class="notice notice-error"><p><strong>SRP Search:</strong> One or more database constants are missing from <code>wp-config.php</code>. Required: <code>SRP_DB_HOST</code>, <code>SRP_DB_NAME</code>, <code>SRP_DB_USER</code>, <code>SRP_DB_PASSWORD</code>.</p></div>';
	}

	// Show driver check in admin when debug is on.
	if ( defined( 'SRP_DEBUG_DB' ) && SRP_DEBUG_DB ) {
		$pdo_loaded    = extension_loaded( 'pdo_sqlsrv' ) ? '✅ loaded' : '❌ NOT loaded';
		$sqlsrv_loaded = extension_loaded( 'sqlsrv' )     ? '✅ loaded' : '❌ NOT loaded';
		echo "<div class='notice notice-info'><p><strong>SRP Debug:</strong> pdo_sqlsrv: {$pdo_loaded} &nbsp;|&nbsp; sqlsrv: {$sqlsrv_loaded} &nbsp;|&nbsp; PHP: " . PHP_VERSION . " &nbsp;|&nbsp; SAPI: " . PHP_SAPI . "</p></div>";
	}
} );

// ── 2. PDO CONNECTION ─────────────────────────────────────────────────────────

function srp_get_pdo(): PDO {
	$host    = SRP_DB_HOST;
	$db      = SRP_DB_NAME;
	$encrypt = defined( 'SRP_DB_ENCRYPT' ) ? ( SRP_DB_ENCRYPT ? 'yes' : 'no' ) : 'yes';
	$dsn     = "sqlsrv:Server={$host};Database={$db};Encrypt={$encrypt};TrustServerCertificate=no";

	return new PDO( $dsn, SRP_DB_USER, SRP_DB_PASSWORD, [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_TIMEOUT            => 5,
	] );
}

// ── 3. BLOCK REGISTRATION ─────────────────────────────────────────────────────

add_action( 'init', function () {
	register_block_type(
		SRP_PLUGIN_DIR . 'build',
		[ 'render_callback' => 'srp_render_block' ]
	);
} );

// ── 4. WOOSTER BLOCKS CATEGORY ────────────────────────────────────────────────

function srp_filter_block_categories( array $categories, $post ): array {
	$target      = [ 'slug' => 'wbp-content', 'title' => __( 'Wooster Blocks', 'srp-search' ), 'icon' => null ];
	$found_index = null;

	foreach ( $categories as $i => $cat ) {
		if ( isset( $cat['slug'] ) && 'wbp-content' === $cat['slug'] ) {
			$found_index = $i;
			break;
		}
	}

	if ( null !== $found_index ) {
		array_splice( $categories, $found_index, 1 );
	}

	array_unshift( $categories, $target );
	return $categories;
}
add_filter( 'block_categories_all', 'srp_filter_block_categories', 0, 2 );

// ── 5. ASSETS ─────────────────────────────────────────────────────────────────

function srp_localize( string $handle ): void {
	wp_localize_script( $handle, 'srpData', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'srp_search_nonce' ),
		'debug'   => ( defined( 'SRP_DEBUG_DB' ) && SRP_DEBUG_DB ),
	] );
}

// Front end.
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script(
		'srp-search-frontend',
		SRP_PLUGIN_URL . 'build/frontend.js',
		[ 'jquery' ],
		filemtime( SRP_PLUGIN_DIR . 'build/frontend.js' ),
		true
	);
	srp_localize( 'srp-search-frontend' );
} );

// Block editor — frontend.js bound after SSR via MutationObserver (see frontend.js).
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_script(
		'srp-search-frontend-editor',
		SRP_PLUGIN_URL . 'build/frontend.js',
		[ 'jquery' ],
		filemtime( SRP_PLUGIN_DIR . 'build/frontend.js' ),
		true
	);
	srp_localize( 'srp-search-frontend-editor' );
} );

// ── 6. RENDER CALLBACK ────────────────────────────────────────────────────────

function srp_render_block( array $attributes, string $content ): string {
	// Unique ID per block instance so multiple blocks on one page don't clash.
	static $instance = 0;
	$instance++;
	$uid = 'srp-' . $instance;

	ob_start();
	?>
	<div class="srp-search-wrap" id="<?php echo esc_attr( $uid ); ?>" data-srp-uid="<?php echo esc_attr( $uid ); ?>">
		<form class="srp-form" novalidate>
			<div class="srp-fields">
				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-last-name">Last Name</label>
					<input type="text" id="<?php echo esc_attr( $uid ); ?>-last-name" name="last_name" placeholder="e.g. Smith" autocomplete="off" />
				</div>
				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-year">Graduation Year</label>
					<input type="number" id="<?php echo esc_attr( $uid ); ?>-year" name="year" placeholder="e.g. 2023" min="1900" max="2100" />
				</div>
				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-title">Title Contains</label>
					<input type="text" id="<?php echo esc_attr( $uid ); ?>-title" name="title" placeholder="e.g. climate" autocomplete="off" />
				</div>
				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-major">Major</label>
					<select id="<?php echo esc_attr( $uid ); ?>-major" name="major">
						<option value="">— Any Major —</option>
					</select>
				</div>
				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-advisor">Advisor Last Name</label>
					<input type="text" id="<?php echo esc_attr( $uid ); ?>-advisor" name="advisor" placeholder="e.g. Jones" autocomplete="off" />
				</div>
			</div>
			<div class="srp-form-footer">
				<p class="srp-validation-msg" role="alert" aria-live="polite"></p>
				<button type="submit" class="srp-submit">
					<span class="srp-submit-label">Search</span>
					<span class="srp-submit-spinner" aria-hidden="true"></span>
				</button>
			</div>
		</form>
		<div class="srp-results" aria-live="polite"></div>
	</div>
	<?php
	return ob_get_clean();
}

// ── 7. AJAX: DRIVER & CONNECTION DIAGNOSTIC ───────────────────────────────────

add_action( 'wp_ajax_srp_check',        'srp_ajax_check' );
add_action( 'wp_ajax_nopriv_srp_check', 'srp_ajax_check' );

function srp_ajax_check(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );

	$report = [
		'php_version'    => PHP_VERSION,
		'php_sapi'       => PHP_SAPI,
		'pdo_sqlsrv'     => extension_loaded( 'pdo_sqlsrv' ),
		'sqlsrv'         => extension_loaded( 'sqlsrv' ),
		'config_ok'      => srp_check_config(),
		'connection'     => false,
		'connection_err' => '',
		'query_test'     => false,
		'query_err'      => '',
	];

	if ( $report['pdo_sqlsrv'] && $report['config_ok'] ) {
		try {
			$pdo                 = srp_get_pdo();
			$report['connection'] = true;

			// Try a minimal query against the view.
			$stmt                  = $pdo->query( 'SELECT TOP 1 [STUDENT_LAST] FROM ' . SRP_VIEW );
			$report['query_test']  = ( $stmt !== false );
		} catch ( \Exception $e ) {
			$report['connection_err'] = $e->getMessage();
		}
	}

	wp_send_json_success( $report );
}

// ── 8. AJAX: LOAD MAJORS ──────────────────────────────────────────────────────

add_action( 'wp_ajax_srp_get_majors',        'srp_ajax_get_majors' );
add_action( 'wp_ajax_nopriv_srp_get_majors', 'srp_ajax_get_majors' );

function srp_ajax_get_majors(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );

	if ( ! srp_check_config() ) {
		wp_send_json_error( [ 'message' => 'Database not configured — check wp-config.php constants.' ], 500 );
	}

	try {
		$pdo  = srp_get_pdo();
		$sql  = "SELECT DISTINCT [MAJOR_1] AS major FROM " . SRP_VIEW . " WHERE [MAJOR_1] IS NOT NULL AND [MAJOR_1] <> ''
                 UNION
                 SELECT DISTINCT [MAJOR_2] FROM " . SRP_VIEW . " WHERE [MAJOR_2] IS NOT NULL AND [MAJOR_2] <> ''
                 ORDER BY major ASC";
		$stmt = $pdo->query( $sql );
		wp_send_json_success( [ 'majors' => $stmt->fetchAll( PDO::FETCH_COLUMN ) ] );
	} catch ( \Exception $e ) {
		wp_send_json_error( [ 'message' => srp_error_message( $e, 'Could not load majors' ) ], 500 );
	}
}

// ── 9. AJAX: SEARCH ───────────────────────────────────────────────────────────

add_action( 'wp_ajax_srp_search',        'srp_ajax_search' );
add_action( 'wp_ajax_nopriv_srp_search', 'srp_ajax_search' );

function srp_ajax_search(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );

	if ( ! srp_check_config() ) {
		wp_send_json_error( [ 'message' => 'Database not configured — check wp-config.php constants.' ], 500 );
	}

	$last_name = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
	$year      = sanitize_text_field( wp_unslash( $_POST['year']      ?? '' ) );
	$title     = sanitize_text_field( wp_unslash( $_POST['title']     ?? '' ) );
	$major     = sanitize_text_field( wp_unslash( $_POST['major']     ?? '' ) );
	$advisor   = sanitize_text_field( wp_unslash( $_POST['advisor']   ?? '' ) );

	if ( $last_name === '' && $year === '' && $title === '' && $major === '' && $advisor === '' ) {
		wp_send_json_error( [ 'message' => 'Please enter at least one search field.' ], 422 );
	}

	if ( $year !== '' && ! ctype_digit( $year ) ) {
		wp_send_json_error( [ 'message' => 'Graduation year must be a number.' ], 422 );
	}

	$where  = [];
	$params = [];

	if ( $last_name !== '' ) { $where[] = '[STUDENT_LAST] LIKE :last_name'; $params[':last_name'] = '%' . $last_name . '%'; }
	if ( $year      !== '' ) { $where[] = '[YEAR] = :year';                  $params[':year']      = (int) $year; }
	if ( $title     !== '' ) { $where[] = '[IS_TITLE] LIKE :title';          $params[':title']     = '%' . $title . '%'; }
	if ( $major     !== '' ) { $where[] = '([MAJOR_1] = :major OR [MAJOR_2] = :major2)'; $params[':major'] = $major; $params[':major2'] = $major; }
	if ( $advisor   !== '' ) { $where[] = '[ADVISOR_LAST] LIKE :advisor';    $params[':advisor']   = '%' . $advisor . '%'; }

	$sql = "SELECT [STUDENT_FIRST],[STUDENT_LAST],[YEAR],[IS_TITLE],[MAJOR_1],[MAJOR_2],[ADVISOR_FIRST],[ADVISOR_LAST]
	        FROM " . SRP_VIEW . " WHERE " . implode( ' AND ', $where ) . "
	        ORDER BY [YEAR] ASC, [STUDENT_LAST] ASC";

	try {
		$pdo  = srp_get_pdo();
		$stmt = $pdo->prepare( $sql );
		$stmt->execute( $params );
		$rows = $stmt->fetchAll();
		wp_send_json_success( [ 'results' => $rows, 'count' => count( $rows ) ] );
	} catch ( \Exception $e ) {
		wp_send_json_error( [ 'message' => srp_error_message( $e, 'Search failed' ) ], 500 );
	}
}
