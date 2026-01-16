<?php
/**
 * Plugin Name:       Site List
 * Description:       Adds a shortcode that can be used to generate and display a table of all sites with
 *                    associated editors in a multisite network.
 * Version:           1.1.5
 * Author:            The College of Wooster
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Network:           true
 * License:           GPLv2 or later
 * Text Domain:       site-list-shortcode
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin translations.
 *
 * Plugin Check discourages load_plugin_textdomain() for plugins hosted on
 * WordPress.org (Core auto-loads translations). Using load_textdomain() keeps
 * us compatible with both wp.org and non-wp.org deployment.
 *
 * @return void
 */
function cow_site_list_load_textdomain() {
	$domain = 'site-list-shortcode';

	// Try the standard WP.org languages directory first.
	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	// Allow deployments to override locale selection for this plugin's MO file lookup.
	$locale = (string) apply_filters( 'cow_site_list_locale', $locale, $domain );

	$mo_wporg = trailingslashit( WP_LANG_DIR ) . 'plugins/' . $domain . '-' . $locale . '.mo';
	if ( file_exists( $mo_wporg ) ) {
		load_textdomain( $domain, $mo_wporg );
	}

	// Fall back to the plugin's bundled languages directory.
	$mo_local = plugin_dir_path( __FILE__ ) . 'languages/' . $domain . '-' . $locale . '.mo';
	if ( file_exists( $mo_local ) ) {
		load_textdomain( $domain, $mo_local );
	}
}
add_action( 'plugins_loaded', 'cow_site_list_load_textdomain' );

/**
 * Shortcode handler for [site-list].
 *
 * Intended for multisite.
 *
 * Attributes:
 * - show_main    (0|1)  Whether to include the main site (blog_id=1). Default: 1.
 * - exclude      (text) Comma-separated blog IDs to exclude. Example: "3,5".
 * - cache        (int)  Cache TTL in seconds (object cache). Default: 600.
 * - class        (text) CSS class added to the <table>. Default: inside-site-table.
 * - trusted_role (text) Additional role slug to include in the Editors column. Default: trusted_editor.
 *
 * @param array<string,mixed> $atts Shortcode attributes.
 * @return string
 */
function cow_site_list_shortcode( $atts ) {
	if ( ! is_multisite() ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'show_main'    => '1',
			'exclude'      => '',
			'cache'        => '600',
			'class'        => 'inside-site-table',
			'trusted_role' => 'trusted_editor',
		),
		(array) $atts,
		'site-list'
	);

	$show_main = ( '1' === (string) $atts['show_main'] );
	$exclude   = (string) $atts['exclude'];
	$cache_ttl = max( 0, (int) $atts['cache'] );
	$table_css = sanitize_html_class( (string) $atts['class'] );

	$exclude_ids = array();
	if ( '' !== trim( $exclude ) ) {
		$exclude_ids = preg_split( '/\s*,\s*/', $exclude );
		$exclude_ids = array_filter( array_map( 'absint', (array) $exclude_ids ) );
		$exclude_ids = array_values( array_unique( $exclude_ids ) );
	}

	$trusted_role = sanitize_key( (string) $atts['trusted_role'] );
	$trusted_role = (string) apply_filters( 'cow_site_list_trusted_editor_role', $trusted_role );
	if ( '' === $trusted_role ) {
		$trusted_role = 'trusted_editor';
	}

	$cache_key = 'site_list_' . md5( wp_json_encode( array( $show_main, $exclude_ids, $table_css, $trusted_role ) ) );
	$group     = 'cow_site_list_shortcode';

	$rows = wp_cache_get( $cache_key, $group );
	if ( false === $rows ) {
		$rows = array();

		$site_args = array(
			'number'   => 0,
			'public'   => 1,
			'archived' => 0,
			'spam'     => 0,
			'deleted'  => 0,
		);
		if ( ! empty( $exclude_ids ) ) {
			// Supported by WP core (WP_Site_Query). If not, it will be ignored.
			$site_args['site__not_in'] = $exclude_ids;
		}

		$network_sites = get_sites( $site_args );

		foreach ( $network_sites as $site ) {
			$blog_id = (int) $site->blog_id;

			if ( ! empty( $exclude_ids ) && in_array( $blog_id, $exclude_ids, true ) ) {
				continue;
			}

			if ( 1 === $blog_id && ! $show_main ) {
				continue;
			}

			$details = get_blog_details( $blog_id );
			if ( ! $details ) {
				continue;
			}

			$name = (string) $details->blogname;
			$url  = (string) $details->siteurl;

			if ( '' === $name || '' === $url ) {
				continue;
			}

			$editors         = array();
			$trusted_editors = array();
			switch_to_blog( $blog_id );
			try {
				$editors = get_users(
					array(
						'role'    => 'editor',
						'fields'  => 'display_name',
						'orderby' => 'display_name',
						'order'   => 'ASC',
						'number'  => 0,
					)
				);

				$trusted_editors = get_users(
					array(
						'role'    => $trusted_role,
						'fields'  => 'display_name',
						'orderby' => 'display_name',
						'order'   => 'ASC',
						'number'  => 0,
					)
				);
			} finally {
				restore_current_blog();
			}


			$people = array_merge( (array) $editors, (array) $trusted_editors );
			$people = array_values( array_filter( array_map( 'strval', $people ) ) );
			$people = array_values( array_unique( $people ) );
			natcasesort( $people );
			$people = array_values( $people );

			$rows[ $name ] = array(
				'blog_id' => $blog_id,
				'name'    => $name,
				'url'     => $url,
				'editors' => $people,
			);
		}

		ksort( $rows, SORT_NATURAL | SORT_FLAG_CASE );

		if ( $cache_ttl > 0 ) {
			wp_cache_set( $cache_key, $rows, $group, $cache_ttl );
		}
	}

	if ( empty( $rows ) ) {
		return '';
	}

	$body = '';
	foreach ( $rows as $row ) {
		$editor_names = array();
		foreach ( (array) $row['editors'] as $display_name ) {
			$editor_names[] = esc_html( (string) $display_name );
		}

		$editors_cell = ! empty( $editor_names ) ? implode( ', ', $editor_names ) : '&mdash;';

		$body .= sprintf(
			'<tr><td><a href="%s">%s</a></td><td>%s</td></tr>',
			esc_url( (string) $row['url'] ),
			esc_html( (string) $row['name'] ),
			$editors_cell
		);
	}

	$output = sprintf(
		'<table class="%s"><thead><tr><th scope="col">%s</th><th scope="col">%s</th></tr></thead><tbody>%s</tbody></table>',
		esc_attr( $table_css ),
		esc_html__( 'Site', 'site-list-shortcode' ),
		esc_html__( 'Editors', 'site-list-shortcode' ),
		$body
	);

	/**
	 * Filter the shortcode output HTML.
	 *
	 * @param string              $output Output HTML.
	 * @param array<string,string> $atts   Parsed shortcode attributes.
	 */
	return (string) apply_filters( 'cow_site_list_shortcode_output', $output, $atts );
}

/**
 * Whether the shortcode should be registered on the current site.
 *
 * By default, the shortcode is only available on the main site (blog_id = 1),
 * even if the plugin is network activated. You can override this behavior via
 * the `cow_site_list_allowed_blog_ids` filter.
 *
 * @return bool
 */
function cow_site_list_should_register_shortcode() {
	if ( ! is_multisite() ) {
		return false;
	}

	$allowed_blog_ids = (array) apply_filters( 'cow_site_list_allowed_blog_ids', array( 1 ) );
	$allowed_blog_ids = array_map( 'intval', $allowed_blog_ids );

	return in_array( (int) get_current_blog_id(), $allowed_blog_ids, true );
}

/**
 * Register the shortcode on init.
 *
 * @return void
 */
function cow_site_list_register_shortcode() {
	if ( cow_site_list_should_register_shortcode() ) {
		add_shortcode( 'site-list', 'cow_site_list_shortcode' );
	}
}
add_action( 'init', 'cow_site_list_register_shortcode' );
