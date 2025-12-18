<?php


function nbtpl_get_sites_search() {
	
	// Term-based search used via authenticated AJAX; no state is changed.
	if ( empty( $_REQUEST['term'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		echo wp_json_encode( array() );
		die();
	}

	$raw_term = sanitize_text_field( wp_unslash( $_REQUEST['term'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$s        = trim( $raw_term );


	$returning = array();

	if ( ! empty( $s ) ) {
		$pattern = $s;
		$search_base = trim( str_replace( '*', '', $pattern ) );
		if ( '' !== $search_base ) {
			$regex = '/' . str_replace( '\\*', '.*', preg_quote( $pattern, '/' ) ) . '/i';

			$sites = get_sites(
				array(
					'search'         => $search_base,
					'search_columns' => array( 'domain', 'path' ),
					'number'         => 50,
				)
			);

			if ( ! empty( $sites ) ) {
				foreach ( $sites as $site ) {
					$blog_id = isset( $site->blog_id ) ? (int) $site->blog_id : ( isset( $site->id ) ? (int) $site->id : 0 );
					if ( 0 === $blog_id ) {
						continue;
					}

					$domain = isset( $site->domain ) ? (string) $site->domain : '';
					$path   = isset( $site->path ) ? (string) $site->path : '';

					// Preserve the original wildcard behavior ("*") without a direct DB query.
					if ( ! preg_match( $regex, $domain ) && ! preg_match( $regex, $path ) ) {
						continue;
					}

					$details = get_blog_details( $blog_id );
					if ( ! $details ) {
						continue;
					}

					$returning[] = array(
						'blog_name' => $details->blogname,
						'path'      => is_subdomain_install() ? $domain : $path,
						'blog_id'   => $blog_id,
					);
				}
			}
		}

	}

	echo wp_json_encode( $returning );

	die();
}
add_action( 'wp_ajax_nbt_get_sites_search', 'nbtpl_get_sites_search' );

if ( ! function_exists( 'nbt_get_sites_search' ) ) {
	/**
	 * @deprecated 3.0.3 Use nbtpl_get_sites_search()
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function nbt_get_sites_search() {
		nbtpl_get_sites_search();
	}
}
