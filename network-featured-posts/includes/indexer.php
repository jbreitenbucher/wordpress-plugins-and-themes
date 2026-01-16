<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bump the cache version (network option) so block caches invalidate immediately.
 *
 * @return void
 */
function nfp_bump_cache_version() {
    $ver = (int) get_site_option( 'nfp_cache_version', 1 );
    update_site_option( 'nfp_cache_version', $ver + 1 );
}

/**
 * Get category slugs for a post as a pipe-wrapped string: |news|events|
 *
 * @param int $post_id Post ID.
 * @return string
 */
function nfp_get_cat_slug_string_for_post( $post_id ) {
    $terms = get_the_terms( $post_id, 'category' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '';
    }

    $slugs = array();
    foreach ( $terms as $t ) {
        if ( isset( $t->slug ) ) {
            $slugs[] = sanitize_title( $t->slug );
        }
    }

    $slugs = array_values( array_unique( array_filter( $slugs ) ) );
    if ( empty( $slugs ) ) {
        return '';
    }

    return '|' . implode( '|', $slugs ) . '|';
}

/**
 * Get category names for a post as a comma-separated string.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function nfp_get_cat_name_string_for_post( $post_id ) {
    $terms = get_the_terms( $post_id, 'category' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '';
    }

    $names = array();
    foreach ( $terms as $t ) {
        if ( isset( $t->name ) ) {
            $names[] = wp_strip_all_tags( $t->name );
        }
    }

    $names = array_values( array_unique( array_filter( $names ) ) );
    return implode( ', ', $names );
}

/**
 * Get featured image URLs for common sizes.
 *
 * @param int $post_id Post ID.
 * @return array<string,string>
 */
function nfp_get_featured_image_urls( $post_id ) {
    $out = array(
        'thumbnail' => '',
        'medium'    => '',
        'large'     => '',
        'full'      => '',
    );

    $thumb_id = get_post_thumbnail_id( $post_id );
    if ( ! $thumb_id ) {
        return $out;
    }

    foreach ( array_keys( $out ) as $size ) {
        $src = wp_get_attachment_image_src( $thumb_id, $size );
        if ( is_array( $src ) && ! empty( $src[0] ) ) {
            $out[ $size ] = esc_url_raw( $src[0] );
        }
    }

    return $out;
}

/**
 * Upsert an index row for a post (current blog).
 *
 * @param int $post_id Post ID.
 * @return void
 */
function nfp_upsert_index_row_for_post( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post ) {
        return;
    }

    // Only index these post types (expandable later).
    $allowed_types = array( 'post' );
    if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
        return;
    }

    // Only keep published content in the index.
    if ( 'publish' !== $post->post_status ) {
        nfp_remove_post_from_index( $post_id, $post );
        return;
    }

    global $wpdb;
    $table  = nfp_get_index_table_name();
    $blog_id = get_current_blog_id();

    $date_gmt   = (int) get_post_time( 'U', true, $post );
    $title      = wp_strip_all_tags( get_the_title( $post ) );
    $permalink  = get_permalink( $post );
    $site_name  = get_bloginfo( 'name' );
    $author     = get_the_author_meta( 'display_name', (int) $post->post_author );

    $excerpt = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 28 );
    $excerpt = wp_strip_all_tags( $excerpt );

    $cat_slugs = nfp_get_cat_slug_string_for_post( $post_id );
    $cat_names = nfp_get_cat_name_string_for_post( $post_id );
    $imgs      = nfp_get_featured_image_urls( $post_id );

    $data = array(
        'blog_id'        => (int) $blog_id,
        'post_id'        => (int) $post_id,
        'post_date_gmt'  => (int) $date_gmt,
        'post_title'     => $title,
        'permalink'      => $permalink,
        'site_name'      => $site_name,
        'author_name'    => $author,
        'excerpt'        => $excerpt,
        'cat_slugs'      => $cat_slugs,
        'cat_names'      => $cat_names,
        'img_thumbnail'  => $imgs['thumbnail'],
        'img_medium'     => $imgs['medium'],
        'img_large'      => $imgs['large'],
        'img_full'       => $imgs['full'],
        'post_type'      => $post->post_type,
        'post_status'    => $post->post_status,
    );

    $formats = array(
        '%d', // blog_id
        '%d', // post_id
        '%d', // post_date_gmt
        '%s', // post_title
        '%s', // permalink
        '%s', // site_name
        '%s', // author_name
        '%s', // excerpt
        '%s', // cat_slugs
        '%s', // cat_names
        '%s', // img_thumbnail
        '%s', // img_medium
        '%s', // img_large
        '%s', // img_full
        '%s', // post_type
        '%s', // post_status
    );

    // Upsert using REPLACE based on UNIQUE(blog_id, post_id).
    $wpdb->replace( $table, $data, $formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

    nfp_bump_cache_version();
}

/**
 * Index on save.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post Post object.
 * @param bool    $update Update flag.
 * @return void
 */
function nfp_index_post_on_save( $post_id, $post, $update ) {
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    if ( ! ( $post instanceof WP_Post ) ) {
        return;
    }

    nfp_upsert_index_row_for_post( $post_id );
}

/**
 * Index on status transition (publish/unpublish).
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post Post.
 * @return void
 */
function nfp_index_post_on_transition( $new_status, $old_status, $post ) {
    if ( ! ( $post instanceof WP_Post ) ) {
        return;
    }
    nfp_upsert_index_row_for_post( $post->ID );
}

/**
 * Remove from index.
 *
 * @param int        $post_id Post ID.
 * @param WP_Post|null $post Optional.
 * @return void
 */
function nfp_remove_post_from_index( $post_id, $post = null ) {
    global $wpdb;
    $table  = nfp_get_index_table_name();
    $blog_id = get_current_blog_id();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Indexed table maintenance; cache is bumped below.
	$wpdb->delete(
        $table,
        array(
            'blog_id' => (int) $blog_id,
            'post_id' => (int) $post_id,
        ),
        array( '%d', '%d' )
	);

    nfp_bump_cache_version();
}
