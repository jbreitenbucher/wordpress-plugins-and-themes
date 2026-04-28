<?php
/**
 * Plugin Name: SRP Search
 * Plugin URI:  https://wooster.edu
 * Description: Senior Research Project search block for the College of Wooster. Queries an external MSSQL database via PDO with SSL.
 * Version:     1.0.0
 * Author:      College of Wooster
 * License:     GPL-2.0-or-later
 * Text Domain: srp-search
 *
 * Installation:
 * 1. Add the following constants to wp-config.php:
 *    define( 'SRP_DB_HOST',     'mssql2022.local.wooster.edu' );
 *    define( 'SRP_DB_NAME',     'R18-DataOrch-PROD' );
 *    define( 'SRP_DB_USER',     'srp_readonly' );
 *    define( 'SRP_DB_PASSWORD', 'your_strong_password_here' );
 *    define( 'SRP_DB_ENCRYPT',  true );   // set false only if SSL unavailable
 *
 * 2. Upload the plugin folder to /wp-content/plugins/ on the network.
 * 3. Network Admin: install (do NOT network-activate).
 * 4. Site Admin: activate on the target site only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ──────────────────────────────────────────────
// 1. CONSTANTS & SANITY CHECK
// ──────────────────────────────────────────────

define( 'SRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SRP_VIEW',       '[IS_TITLES]' );

/**
 * Confirm required constants exist; show admin notice if not.
 */
function srp_check_config(): bool {
	foreach ( [ 'SRP_DB_HOST', 'SRP_DB_NAME', 'SRP_DB_USER', 'SRP_DB_PASSWORD' ] as $c ) {
		if ( ! defined( $c ) ) {
			return false;
		}
	}
	return true;
}

add_action( 'admin_notices', function () {
	if ( ! srp_check_config() ) {
		echo '<div class="notice notice-error"><p><strong>SRP Search:</strong> One or more database constants are missing from <code>wp-config.php</code>. Please define <code>SRP_DB_HOST</code>, <code>SRP_DB_NAME</code>, <code>SRP_DB_USER</code>, and <code>SRP_DB_PASSWORD</code>.</p></div>';
	}
} );

// ──────────────────────────────────────────────
// 2. PDO CONNECTION
// ──────────────────────────────────────────────

/**
 * Returns a PDO connection to the MSSQL database.
 * Uses Encrypt=yes by default; set SRP_DB_ENCRYPT to false only if SSL is unavailable.
 *
 * @throws \RuntimeException on connection failure.
 */
function srp_get_pdo(): PDO {
	$host    = SRP_DB_HOST;
	$db      = SRP_DB_NAME;
	$encrypt = defined( 'SRP_DB_ENCRYPT' ) ? ( SRP_DB_ENCRYPT ? 'yes' : 'no' ) : 'yes';

	$dsn = "sqlsrv:Server={$host};Database={$db};Encrypt={$encrypt};TrustServerCertificate=no";

	return new PDO(
		$dsn,
		SRP_DB_USER,
		SRP_DB_PASSWORD,
		[
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_TIMEOUT            => 5,
		]
	);
}

// ──────────────────────────────────────────────
// 3. ASSETS
// ──────────────────────────────────────────────

add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_script(
		'srp-search-block',
		SRP_PLUGIN_URL . 'build/index.js',
		[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ],
		filemtime( SRP_PLUGIN_DIR . 'build/index.js' ),
		true
	);
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'srp-search-style',
		SRP_PLUGIN_URL . 'build/style.css',
		[],
		filemtime( SRP_PLUGIN_DIR . 'build/style.css' )
	);
	wp_enqueue_script(
		'srp-search-frontend',
		SRP_PLUGIN_URL . 'build/frontend.js',
		[ 'jquery' ],
		filemtime( SRP_PLUGIN_DIR . 'build/frontend.js' ),
		true
	);
	wp_localize_script( 'srp-search-frontend', 'srpData', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'srp_search_nonce' ),
	] );
} );

// ──────────────────────────────────────────────
// 4. GUTENBERG BLOCK REGISTRATION
// ──────────────────────────────────────────────

add_action( 'init', function () {
	register_block_type( 'srp/search', [
		'render_callback' => 'srp_render_block',
		'attributes'      => [],
	] );
} );

/**
 * Server-side render: outputs the search form shell.
 * Majors are loaded via AJAX on page load to keep the form fresh.
 */
function srp_render_block( array $attributes, string $content ): string {
	ob_start();
	?>
	<div class="srp-search-wrap" id="srp-search-wrap">
		<form class="srp-form" id="srp-search-form" novalidate>
			<div class="srp-fields">
				<div class="srp-field-group">
					<label for="srp-last-name">Last Name</label>
					<input type="text" id="srp-last-name" name="last_name" placeholder="e.g. Smith" autocomplete="off" />
				</div>
				<div class="srp-field-group">
					<label for="srp-year">Graduation Year</label>
					<input type="number" id="srp-year" name="year" placeholder="e.g. 2023" min="1900" max="2100" />
				</div>
				<div class="srp-field-group">
					<label for="srp-title">Title Contains</label>
					<input type="text" id="srp-title" name="title" placeholder="e.g. climate" autocomplete="off" />
				</div>
				<div class="srp-field-group">
					<label for="srp-major">Major</label>
					<select id="srp-major" name="major">
						<option value="">— Any Major —</option>
					</select>
				</div>
				<div class="srp-field-group">
					<label for="srp-advisor">Advisor Last Name</label>
					<input type="text" id="srp-advisor" name="advisor" placeholder="e.g. Jones" autocomplete="off" />
				</div>
			</div>
			<div class="srp-form-footer">
				<p class="srp-validation-msg" id="srp-validation-msg" role="alert" aria-live="polite"></p>
				<button type="submit" class="srp-submit" id="srp-submit">
					<span class="srp-submit-label">Search</span>
					<span class="srp-submit-spinner" aria-hidden="true"></span>
				</button>
			</div>
		</form>
		<div class="srp-results" id="srp-results" aria-live="polite"></div>
	</div>
	<?php
	return ob_get_clean();
}

// ──────────────────────────────────────────────
// 5. AJAX: LOAD MAJORS
// ──────────────────────────────────────────────

add_action( 'wp_ajax_srp_get_majors',        'srp_ajax_get_majors' );
add_action( 'wp_ajax_nopriv_srp_get_majors', 'srp_ajax_get_majors' );

function srp_ajax_get_majors(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );

	if ( ! srp_check_config() ) {
		wp_send_json_error( [ 'message' => 'Database not configured.' ], 500 );
	}

	try {
		$pdo = srp_get_pdo();
		$sql = 'SELECT DISTINCT [MAJOR_1] AS major
                FROM ' . SRP_VIEW . '
                WHERE [MAJOR_1] IS NOT NULL AND [MAJOR_1] <> \'\'
                UNION
                SELECT DISTINCT [MAJOR_2]
                FROM ' . SRP_VIEW . '
                WHERE [MAJOR_2] IS NOT NULL AND [MAJOR_2] <> \'\'
                ORDER BY major ASC';

		$stmt   = $pdo->query( $sql );
		$majors = $stmt->fetchAll( PDO::FETCH_COLUMN );
		wp_send_json_success( [ 'majors' => $majors ] );
	} catch ( \Exception $e ) {
		wp_send_json_error( [ 'message' => 'Could not load majors.' ], 500 );
	}
}

// ──────────────────────────────────────────────
// 6. AJAX: SEARCH
// ──────────────────────────────────────────────

add_action( 'wp_ajax_srp_search',        'srp_ajax_search' );
add_action( 'wp_ajax_nopriv_srp_search', 'srp_ajax_search' );

function srp_ajax_search(): void {
	check_ajax_referer( 'srp_search_nonce', 'nonce' );

	if ( ! srp_check_config() ) {
		wp_send_json_error( [ 'message' => 'Database not configured.' ], 500 );
	}

	// ── Sanitize inputs ──────────────────────────────
	$last_name = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
	$year      = sanitize_text_field( wp_unslash( $_POST['year']      ?? '' ) );
	$title     = sanitize_text_field( wp_unslash( $_POST['title']     ?? '' ) );
	$major     = sanitize_text_field( wp_unslash( $_POST['major']     ?? '' ) );
	$advisor   = sanitize_text_field( wp_unslash( $_POST['advisor']   ?? '' ) );

	// ── At least one field required ──────────────────
	if ( $last_name === '' && $year === '' && $title === '' && $major === '' && $advisor === '' ) {
		wp_send_json_error( [ 'message' => 'Please enter at least one search field.' ], 422 );
	}

	// ── Year must be numeric if provided ────────────
	if ( $year !== '' && ! ctype_digit( $year ) ) {
		wp_send_json_error( [ 'message' => 'Graduation year must be a number.' ], 422 );
	}

	// ── Build query ──────────────────────────────────
	$where  = [];
	$params = [];

	if ( $last_name !== '' ) {
		$where[]  = '[STUDENT_LAST] LIKE :last_name';
		$params[':last_name'] = '%' . $last_name . '%';
	}
	if ( $year !== '' ) {
		$where[]        = '[YEAR] = :year';
		$params[':year'] = (int) $year;
	}
	if ( $title !== '' ) {
		$where[]        = '[IS_TITLE] LIKE :title';
		$params[':title'] = '%' . $title . '%';
	}
	if ( $major !== '' ) {
		$where[]         = '([MAJOR_1] = :major OR [MAJOR_2] = :major2)';
		$params[':major']  = $major;
		$params[':major2'] = $major;
	}
	if ( $advisor !== '' ) {
		$where[]           = '[ADVISOR_LAST] LIKE :advisor';
		$params[':advisor'] = '%' . $advisor . '%';
	}

	$where_clause = 'WHERE ' . implode( ' AND ', $where );

	$sql = "SELECT
				[STUDENT_FIRST],
				[STUDENT_LAST],
				[YEAR],
				[IS_TITLE],
				[MAJOR_1],
				[MAJOR_2],
				[ADVISOR_FIRST],
				[ADVISOR_LAST]
			FROM " . SRP_VIEW . "
			{$where_clause}
			ORDER BY [YEAR] ASC, [STUDENT_LAST] ASC";

	try {
		$pdo  = srp_get_pdo();
		$stmt = $pdo->prepare( $sql );
		$stmt->execute( $params );
		$rows = $stmt->fetchAll();

		wp_send_json_success( [ 'results' => $rows, 'count' => count( $rows ) ] );
	} catch ( \Exception $e ) {
		wp_send_json_error( [ 'message' => 'Search failed. Please try again.' ], 500 );
	}
}
