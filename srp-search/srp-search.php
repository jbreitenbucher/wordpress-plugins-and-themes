<?php
/**
 * Plugin Name: SRP Search
 * Plugin URI:  https://wooster.edu
 * Description: Senior Research Project search block for the College of Wooster.
 * Version:     1.5.2
 * Author:      College of Wooster
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * Text Domain: srp-search
 *
 * wp-config.php constants required:
 *   define( 'SRP_DB_HOST',     'mssql2022.local.wooster.edu' );
 *   define( 'SRP_DB_NAME',     'R18-DataOrch-PROD' );
 *   define( 'SRP_DB_USER',     'srp_readonly' );
 *   define( 'SRP_DB_PASSWORD', 'your_strong_password_here' );
 *   define( 'SRP_DB_ENCRYPT',  true );
 *   define( 'SRP_DEBUG_DB',    true );  // remove in production
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SRP_VIEW',       '[IS_TITLES]' );

// ── ORDER BY map ──────────────────────────────────────────────────────────────
// Maps the block attribute string to a safe SQL ORDER BY clause.
// Only values present in this map are ever used — no user input reaches ORDER BY.
const SRP_ORDER_MAP = [
	'year_asc_name_asc'  => '[YEAR] ASC,  [STUDENT_LAST] ASC',
	'name_asc_year_asc'  => '[STUDENT_LAST] ASC,  [YEAR] ASC',
	'year_desc_name_asc' => '[YEAR] DESC, [STUDENT_LAST] ASC',
	'name_asc'           => '[STUDENT_LAST] ASC',
];

// ── 1. CONFIG CHECK ───────────────────────────────────────────────────────────

function srp_check_config(): bool {
	foreach ( [ 'SRP_DB_HOST', 'SRP_DB_NAME', 'SRP_DB_USER', 'SRP_DB_PASSWORD' ] as $c ) {
		if ( ! defined( $c ) ) return false;
	}
	return true;
}

function srp_error_message( \Exception $e, string $context ): string {
	if ( defined( 'SRP_DEBUG_DB' ) && SRP_DEBUG_DB ) {
		return "[SRP DEBUG] {$context}: " . $e->getMessage();
	}
	return $context . ' — please contact the site administrator.';
}

add_action( 'admin_notices', function () {
	if ( ! srp_check_config() ) {
		echo '<div class="notice notice-error"><p><strong>SRP Search:</strong> Database constants missing from <code>wp-config.php</code>.</p></div>';
	}
	if ( defined( 'SRP_DEBUG_DB' ) && SRP_DEBUG_DB ) {
		$pdo_status    = extension_loaded( 'pdo_sqlsrv' ) ? 'loaded' : 'NOT loaded';
		$sqlsrv_status = extension_loaded( 'sqlsrv' )     ? 'loaded' : 'NOT loaded';
		printf(
			'<div class="notice notice-info"><p><strong>SRP Debug:</strong> pdo_sqlsrv: %s | sqlsrv: %s | PHP %s | SAPI: %s</p></div>',
			esc_html( $pdo_status ),
			esc_html( $sqlsrv_status ),
			esc_html( PHP_VERSION ),
			esc_html( PHP_SAPI )
		);
	}
} );

// ── 2. PDO CONNECTION ─────────────────────────────────────────────────────────

function srp_get_pdo(): PDO { // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
	$host    = SRP_DB_HOST;
	$db      = SRP_DB_NAME;
	$encrypt = defined( 'SRP_DB_ENCRYPT' ) ? ( SRP_DB_ENCRYPT ? 'yes' : 'no' ) : 'yes';

	// LoginTimeout is set in the DSN — PDO::ATTR_TIMEOUT is not supported by the
	// sqlsrv driver and throws IMSSP if passed in the options array.
	$dsn = "sqlsrv:Server={$host};Database={$db};Encrypt={$encrypt};TrustServerCertificate=no;LoginTimeout=5";

	// PDO is required here — $wpdb supports MySQL only and cannot connect to an
	// external MSSQL server. All queries use prepared statements with bound parameters.
	return new PDO( $dsn, SRP_DB_USER, SRP_DB_PASSWORD, [ // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
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
		if ( isset( $cat['slug'] ) && 'wbp-content' === $cat['slug'] ) { $found_index = $i; break; }
	}
	if ( null !== $found_index ) array_splice( $categories, $found_index, 1 );
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

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'srp-search-frontend', SRP_PLUGIN_URL . 'build/frontend.js',
		[ 'jquery' ], filemtime( SRP_PLUGIN_DIR . 'build/frontend.js' ), true );
	srp_localize( 'srp-search-frontend' );
} );

add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_script( 'srp-search-frontend-editor', SRP_PLUGIN_URL . 'build/frontend.js',
		[ 'jquery' ], filemtime( SRP_PLUGIN_DIR . 'build/frontend.js' ), true );
	srp_localize( 'srp-search-frontend-editor' );
} );

// ── 6. RENDER CALLBACK ────────────────────────────────────────────────────────

function srp_render_block( array $attributes, string $content ): string {
	static $instance = 0;
	$instance++;
	$uid = 'srp-' . $instance;

	// Sanitize attributes with safe defaults.
	$per_page          = in_array( (int) ( $attributes['perPage'] ?? 25 ), [ 25, 50, 100 ], true )
	                     ? (int) $attributes['perPage'] : 25;
	$no_results_msg    = esc_html( $attributes['noResultsMessage']
	                     ?? 'No projects found matching your search.' );
	$show_major2       = (bool) ( $attributes['showMajor2']  ?? true );
	$show_advisor      = (bool) ( $attributes['showAdvisor'] ?? true );
	$order_by_key      = $attributes['orderBy'] ?? 'year_asc_name_asc';
	if ( ! array_key_exists( $order_by_key, SRP_ORDER_MAP ) ) {
		$order_by_key = 'year_asc_name_asc';
	}

	ob_start();
	?>
	<div class="srp-search-wrap"
		id="<?php echo esc_attr( $uid ); ?>"
		data-srp-uid="<?php echo esc_attr( $uid ); ?>"
		data-srp-per-page="<?php echo esc_attr( $per_page ); ?>"
		data-srp-no-results="<?php echo esc_attr( $no_results_msg ); ?>"
		data-srp-show-major2="<?php echo $show_major2  ? '1' : '0'; ?>"
		data-srp-show-advisor="<?php echo $show_advisor ? '1' : '0'; ?>"
		data-srp-order-by="<?php echo esc_attr( $order_by_key ); ?>">

		<form class="srp-form" novalidate>
			<div class="srp-fields">

				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-last-name">Last Name</label>
					<input type="text"
						id="<?php echo esc_attr( $uid ); ?>-last-name"
						name="last_name"
						placeholder="e.g. Smith"
						autocomplete="off" />
				</div>

				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-year">Graduation Year</label>
					<select id="<?php echo esc_attr( $uid ); ?>-year" name="year">
						<option value="">— Any Year —</option>
					</select>
				</div>

				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-title">Title Contains</label>
					<input type="text"
						id="<?php echo esc_attr( $uid ); ?>-title"
						name="title"
						placeholder="e.g. climate"
						autocomplete="off" />
				</div>

				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-major">Major</label>
					<select id="<?php echo esc_attr( $uid ); ?>-major" name="major">
						<option value="">— Any Major —</option>
					</select>
				</div>

				<?php if ( $show_advisor ) : ?>
				<div class="srp-field-group">
					<label for="<?php echo esc_attr( $uid ); ?>-advisor">Advisor Last Name</label>
					<input type="text"
						id="<?php echo esc_attr( $uid ); ?>-advisor"
						name="advisor"
						placeholder="e.g. Jones"
						autocomplete="off" />
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

		<div class="srp-results" aria-live="polite"></div>

		<div class="srp-load-more-wrap" style="display:none;">
			<button class="srp-load-more" type="button">Load More</button>
		</div>

	</div>
	<?php
	return ob_get_clean();
}

// ── 7. AJAX: DIAGNOSTIC ───────────────────────────────────────────────────────

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
			$pdo                  = srp_get_pdo();
			$report['connection'] = true;
			$stmt                 = $pdo->query( 'SELECT TOP 1 [STUDENT_LAST] FROM ' . SRP_VIEW );
			$report['query_test'] = ( $stmt !== false );
		} catch ( \Exception $e ) {
			$report['connection_err'] = $e->getMessage();
		}
	}
	wp_send_json_success( $report );
}

// ── 8. AJAX: LOAD YEARS ───────────────────────────────────────────────────────

add_action( 'wp_ajax_srp_get_years',        'srp_ajax_get_years' );
add_action( 'wp_ajax_nopriv_srp_get_years', 'srp_ajax_get_years' );

function srp_ajax_get_years(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );
	if ( ! srp_check_config() ) {
		wp_send_json_error( [ 'message' => 'Database not configured.' ], 500 );
	}
	try {
		$pdo  = srp_get_pdo();
		$stmt = $pdo->query( 'SELECT MIN([YEAR]) AS min_year, MAX([YEAR]) AS max_year FROM ' . SRP_VIEW );
		$row  = $stmt->fetch();
		$years = [];
		if ( $row && $row['min_year'] && $row['max_year'] ) {
			for ( $y = (int) $row['min_year']; $y <= (int) $row['max_year']; $y++ ) {
				$years[] = $y;
			}
		}
		wp_send_json_success( [ 'years' => $years ] );
	} catch ( \Exception $e ) {
		wp_send_json_error( [ 'message' => srp_error_message( $e, 'Could not load years' ) ], 500 );
	}
}

// ── 9. AJAX: LOAD MAJORS ──────────────────────────────────────────────────────

add_action( 'wp_ajax_srp_get_majors',        'srp_ajax_get_majors' );
add_action( 'wp_ajax_nopriv_srp_get_majors', 'srp_ajax_get_majors' );

function srp_ajax_get_majors(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );
	if ( ! srp_check_config() ) {
		wp_send_json_error( [ 'message' => 'Database not configured.' ], 500 );
	}
	try {
		$pdo  = srp_get_pdo();
		$sql  = "SELECT DISTINCT [MAJOR_1] AS major FROM " . SRP_VIEW . " WHERE [MAJOR_1] IS NOT NULL AND [MAJOR_1] <> ''
                 UNION
                 SELECT DISTINCT [MAJOR_2] FROM " . SRP_VIEW . " WHERE [MAJOR_2] IS NOT NULL AND [MAJOR_2] <> ''
                 ORDER BY major ASC";
		$stmt = $pdo->query( $sql );
		wp_send_json_success( [ 'majors' => $stmt->fetchAll( PDO::FETCH_COLUMN ) ] ); // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
	} catch ( \Exception $e ) {
		wp_send_json_error( [ 'message' => srp_error_message( $e, 'Could not load majors' ) ], 500 );
	}
}

// ── 10. AJAX: SEARCH ──────────────────────────────────────────────────────────

add_action( 'wp_ajax_srp_search',        'srp_ajax_search' );
add_action( 'wp_ajax_nopriv_srp_search', 'srp_ajax_search' );

function srp_ajax_search(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );

	if ( ! srp_check_config() ) {
		wp_send_json_error( [ 'message' => 'Database not configured.' ], 500 );
	}

	// Sanitize inputs.
	$last_name = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
	$year      = sanitize_text_field( wp_unslash( $_POST['year']      ?? '' ) );
	$title     = sanitize_text_field( wp_unslash( $_POST['title']     ?? '' ) );
	$major     = sanitize_text_field( wp_unslash( $_POST['major']     ?? '' ) );
	$advisor   = sanitize_text_field( wp_unslash( $_POST['advisor']   ?? '' ) );

	// Pagination.
	$per_page = in_array( (int) sanitize_text_field( wp_unslash( $_POST['per_page'] ?? '25' ) ), [ 25, 50, 100 ], true )
	            ? (int) sanitize_text_field( wp_unslash( $_POST['per_page'] ?? '25' ) ) : 25;
	$offset   = max( 0, (int) sanitize_text_field( wp_unslash( $_POST['offset'] ?? '0' ) ) );

	// Order — validate against whitelist map; never trust raw input.
	$order_by_key = sanitize_text_field( wp_unslash( $_POST['order_by'] ?? 'year_asc_name_asc' ) );
	if ( ! array_key_exists( $order_by_key, SRP_ORDER_MAP ) ) {
		$order_by_key = 'year_asc_name_asc';
	}
	$order_clause = SRP_ORDER_MAP[ $order_by_key ];

	if ( $last_name === '' && $year === '' && $title === '' && $major === '' && $advisor === '' ) {
		wp_send_json_error( [ 'message' => 'Please enter at least one search field.' ], 422 );
	}

	if ( $year !== '' && ! ctype_digit( $year ) ) {
		wp_send_json_error( [ 'message' => 'Please select a valid graduation year.' ], 422 );
	}

	$where  = [];
	$params = [];

	if ( $last_name !== '' ) { $where[] = '[STUDENT_LAST] LIKE :last_name'; $params[':last_name'] = '%' . $last_name . '%'; }
	if ( $year      !== '' ) { $where[] = '[YEAR] = :year';                  $params[':year']      = (int) $year; }
	if ( $title     !== '' ) { $where[] = '[IS_TITLE] LIKE :title';          $params[':title']     = '%' . $title . '%'; }
	if ( $major     !== '' ) { $where[] = '([MAJOR_1] = :major OR [MAJOR_2] = :major2)'; $params[':major'] = $major; $params[':major2'] = $major; }
	if ( $advisor   !== '' ) { $where[] = '[ADVISOR_LAST] LIKE :advisor';    $params[':advisor']   = '%' . $advisor . '%'; }

	$where_clause = 'WHERE ' . implode( ' AND ', $where );

	// Total count for Load More visibility.
	$count_sql = "SELECT COUNT(*) FROM " . SRP_VIEW . " {$where_clause}";

	// MSSQL rejects bound parameters for OFFSET/FETCH NEXT row counts — it requires
	// integer literals. Both $offset and $per_page are whitelist-validated integers
	// above so inlining them here is safe; no user-supplied string reaches this SQL.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$result_sql = "SELECT [STUDENT_FIRST],[STUDENT_LAST],[YEAR],[IS_TITLE],[MAJOR_1],[MAJOR_2],[ADVISOR_FIRST],[ADVISOR_LAST]
	               FROM " . SRP_VIEW . "
	               {$where_clause}
	               ORDER BY {$order_clause}
	               OFFSET {$offset} ROWS FETCH NEXT {$per_page} ROWS ONLY";

	try {
		$pdo = srp_get_pdo();

		$count_stmt = $pdo->prepare( $count_sql );
		$count_stmt->execute( $params );
		$total = (int) $count_stmt->fetchColumn();

		$stmt = $pdo->prepare( $result_sql );
		$stmt->execute( $params );
		$rows = $stmt->fetchAll();

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
