<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST routes.
 *
 * @return void
 */
function nfp_register_rest_routes() {
    register_rest_route(
        'nfp/v1',
        '/sites',
        array(
            'methods'             => 'GET',
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'args'                => array(
                'search'   => array(
                    'type'              => 'string',
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'page'     => array(
                    'type'              => 'integer',
                    'required'          => false,
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                ),
                'per_page' => array(
                    'type'              => 'integer',
                    'required'          => false,
                    'default'           => 50,
                    'sanitize_callback' => 'absint',
                ),
            ),
            'callback'            => 'nfp_rest_get_sites',
        )
    );
}

/**
 * REST callback: list sites (optionally filtered) for the editor site picker.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function nfp_rest_get_sites( WP_REST_Request $request ) {
    $search   = (string) $request->get_param( 'search' );
    $page     = max( 1, (int) $request->get_param( 'page' ) );
    $per_page = min( 200, max( 10, (int) $request->get_param( 'per_page' ) ) );

    $allowed = get_site_option( 'nfp_allowed_blog_ids', array() );
    $allowed = is_array( $allowed ) ? array_values( array_filter( array_map( 'absint', $allowed ) ) ) : array();

    // If a network allowlist is configured, only return those sites.
    if ( ! empty( $allowed ) ) {
        $allowed_map = array_fill_keys( $allowed, true );
    } else {
        $allowed_map = array();
    }

    $sites = get_sites(
        array(
            'number'   => 0,
            'deleted'  => 0,
            'spam'     => 0,
            'archived' => 0,
        )
    );

    $out = array();

    // Simple in-PHP filtering; for 1200 sites this is fine.
    foreach ( $sites as $site ) {
                if ( ! empty( $allowed_map ) && ! isset( $allowed_map[ $bid ] ) ) {
            continue;
        }
        $bid = (int) $site->blog_id;

        if ( ! empty( $allowed ) && ! in_array( $bid, $allowed, true ) ) {
            continue;
        }

        $details = get_blog_details( $bid );
        $name    = $details ? (string) $details->blogname : ( 'Site ' . $bid );
        $url     = $details ? (string) $details->siteurl : '';

        if ( '' !== $search ) {
            $haystack = strtolower( $name . ' ' . $url . ' ' . (string) $bid );
            if ( false === strpos( $haystack, strtolower( $search ) ) ) {
                continue;
            }
        }

        $out[] = array(
            'blogId' => $bid,
            'name'   => $name,
            'url'    => $url,
        );
    }

    // Pagination.
    $total = count( $out );
    $start = ( $page - 1 ) * $per_page;
    $slice = array_slice( $out, $start, $per_page );

    return rest_ensure_response(
        array(
            'items' => $slice,
            'total' => $total,
            'page'  => $page,
            'perPage' => $per_page,
        )
    );
}
