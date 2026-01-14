<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitize list of slugs.
 *
 * @param mixed $value Value.
 * @return string[]
 */

/**
 * Convert REST/serialized values to a real boolean.
 *
 * ServerSideRender passes nested attributes via querystring, so values often arrive as strings like
 * "true"/"false". Using empty() on "false" is a classic footgun.
 *
 * @param mixed $value Value.
 * @param bool  $default Default.
 * @return bool
 */
function nfp_to_bool( $value, $default = false ) {
    if ( is_bool( $value ) ) {
        return $value;
    }
    if ( is_int( $value ) ) {
        return 0 !== $value;
    }
    if ( is_string( $value ) ) {
        $v = strtolower( trim( $value ) );
        if ( '' === $v ) {
            return $default;
        }
        if ( in_array( $v, array( '1', 'true', 'yes', 'on' ), true ) ) {
            return true;
        }
        if ( in_array( $v, array( '0', 'false', 'no', 'off' ), true ) ) {
            return false;
        }
    }
    return (bool) $value;
}

/**
 * Sanitize a meta order list (known keys only).
 *
 * @param mixed $value Value.
 * @return string[]
 */
function nfp_sanitize_meta_order( $value ) {
    $allowed = array( 'site', 'author', 'date', 'categories' );
    $out = array();

    if ( is_array( $value ) ) {
        foreach ( $value as $k ) {
            if ( is_string( $k ) ) {
                $k = sanitize_key( $k );
                if ( in_array( $k, $allowed, true ) && ! in_array( $k, $out, true ) ) {
                    $out[] = $k;
                }
            }
        }
    }

    foreach ( $allowed as $k ) {
        if ( ! in_array( $k, $out, true ) ) {
            $out[] = $k;
        }
    }

    return $out;
}


/**
 * Convert a dateRange string (30d/90d/6m/1y/all) to a GMT unix timestamp cutoff.
 *
 * @param string $range Range key.
 * @return int Cutoff timestamp in GMT; 0 means no cutoff.
 */
function nfp_date_range_to_cutoff_gmt( $range ) {
    $range = sanitize_key( (string) $range );

    if ( '' === $range || 'all' === $range ) {
        return 0;
    }

    $now_gmt = (int) current_time( 'timestamp', true ); // GMT.
    $seconds = 0;

    if ( '30d' === $range ) {
        $seconds = 30 * DAY_IN_SECONDS;
    } elseif ( '90d' === $range ) {
        $seconds = 90 * DAY_IN_SECONDS;
    } elseif ( '6m' === $range ) {
        // Approximation: 6 * 30 days.
        $seconds = 180 * DAY_IN_SECONDS;
    } elseif ( '1y' === $range ) {
        $seconds = YEAR_IN_SECONDS;
    } else {
        return 0;
    }

    $cutoff = $now_gmt - $seconds;
    return max( 0, $cutoff );
}


function nfp_sanitize_slug_list( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }

    $out = array();
    foreach ( $value as $item ) {
        if ( is_string( $item ) ) {
            $slug = sanitize_title( $item );
            if ( '' !== $slug ) {
                $out[] = $slug;
            }
        }
    }

    return array_values( array_unique( $out ) );
}

/**
 * Sanitize list of integers.
 *
 * @param mixed $value Value.
 * @return int[]
 */
function nfp_sanitize_int_list( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }

    $out = array();
    foreach ( $value as $v ) {
        $i = absint( $v );
        if ( $i > 0 ) {
            $out[] = $i;
        }
    }

    return array_values( array_unique( $out ) );
}

/**
 * Compute effective blog IDs (allowed list intersected with per-block selection).
 *
 * @param int[] $include_blog_ids Included blog IDs.
 * @return int[]
 */
function nfp_get_allowed_blog_ids_effective( $include_blog_ids ) {
    $allowed = get_site_option( 'nfp_allowed_blog_ids', array() );
    $allowed = is_array( $allowed ) ? $allowed : array();

    if ( ! empty( $allowed ) ) {
        if ( ! empty( $include_blog_ids ) ) {
            return array_values( array_intersect( $allowed, $include_blog_ids ) );
        }
        return $allowed;
    }

    if ( ! empty( $include_blog_ids ) ) {
        return $include_blog_ids;
    }

    $sites = get_sites(
        array(
            'number'   => 0,
            'deleted'  => 0,
            'spam'     => 0,
            'archived' => 0,
        )
    );

    $ids = array();
    foreach ( $sites as $s ) {
        $ids[] = (int) $s->blog_id;
    }

    return $ids;
}

/**
 * Query the index table.
 *
 * @param array $args Query args.
 * @return array<int,array<string,mixed>>
 */


/**
 * Check whether the index has any rows for a given blog ID.
 *
 * @param int $blog_id Blog ID.
 * @return bool
 */
function nfp_index_has_rows_for_blog( $blog_id ) {
    global $wpdb;
    $table = nfp_get_index_table_name();
    $blog_id = absint( $blog_id );
    if ( $blog_id <= 0 ) {
        return false;
    }
    $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM {$table} WHERE blog_id = %d", $blog_id ) );
    return $count > 0;
}

/**
 * Seed the index for a single blog by indexing its most recent published posts.
 * This is a safety net for environments where WP-Cron is disabled or backfill hasn't run yet.
 *
 * @param int $blog_id Blog ID.
 * @param int $limit   Max posts to index.
 * @return void
 */
function nfp_seed_index_for_blog( $blog_id, $limit = 200 ) {
    $blog_id = absint( $blog_id );
    if ( $blog_id <= 0 ) {
        return;
    }

    // Don't repeatedly re-seed the same blog on every render.
    $seeded = get_site_option( 'nfp_seeded_blog_ids', array() );
    $seeded = is_array( $seeded ) ? array_values( array_filter( array_map( 'absint', $seeded ) ) ) : array();
    if ( in_array( $blog_id, $seeded, true ) ) {
        return;
    }

    switch_to_blog( $blog_id );

    $q = new WP_Query(
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => max( 1, min( 500, absint( $limit ) ) ),
            'fields'         => 'ids',
            'no_found_rows'  => true,
        )
    );

    if ( ! empty( $q->posts ) ) {
        foreach ( $q->posts as $pid ) {
            nfp_upsert_index_row_for_post( (int) $pid );
        }
    }

    restore_current_blog();

    $seeded[] = $blog_id;
    $seeded   = array_values( array_unique( $seeded ) );
    update_site_option( 'nfp_seeded_blog_ids', $seeded );
}

function nfp_query_index( $args ) {
    global $wpdb;
    $table = nfp_get_index_table_name();

    $posts_to_show = max( 1, absint( $args['posts_to_show'] ?? 6 ) );
    $blog_ids      = $args['blog_ids'] ?? array();
    $cat_slugs     = $args['cat_slugs'] ?? array();
    $exclude_slugs = $args['exclude_slugs'] ?? array();
    $post_type     = sanitize_key( $args['post_type'] ?? 'post' );

    $cutoff_gmt = absint( $args['cutoff_gmt'] ?? 0 );
    $order_by   = sanitize_key( $args['order_by'] ?? 'date' );
    $order      = sanitize_key( $args['order'] ?? 'desc' );
    $order      = in_array( $order, array( 'asc', 'desc' ), true ) ? strtoupper( $order ) : 'DESC';
    $order_by   = ( 'title' === $order_by ) ? 'title' : 'date';
    $order_by_sql = ( 'title' === $order_by ) ? 'post_title' : 'post_date_gmt';

    if ( empty( $blog_ids ) ) {
        return array();
    }

    $where  = array();
    $params = array();

    $where[]  = "post_status = 'publish'";
    $where[]  = "post_type = %s";
    $params[] = $post_type;

    if ( $cutoff_gmt > 0 ) {
        $where[]  = "post_date_gmt >= %d";
        $params[] = $cutoff_gmt;
    }

    $placeholders = implode( ',', array_fill( 0, count( $blog_ids ), '%d' ) );
    $where[]      = "blog_id IN ($placeholders)";
    $params       = array_merge( $params, $blog_ids );

    // Include categories: match ANY slug.
    if ( ! empty( $cat_slugs ) ) {
        $likes = array();
        foreach ( $cat_slugs as $slug ) {
            $likes[]  = "cat_slugs LIKE %s";
            $params[] = '%' . $wpdb->esc_like( '|' . $slug . '|' ) . '%';
        }
        $where[] = '(' . implode( ' OR ', $likes ) . ')';
    }

    // Exclude categories: match NONE.
    if ( ! empty( $exclude_slugs ) ) {
        foreach ( $exclude_slugs as $slug ) {
            $where[]  = "cat_slugs NOT LIKE %s";
            $params[] = '%' . $wpdb->esc_like( '|' . $slug . '|' ) . '%';
        }
    }

    $sql = "
        SELECT blog_id, post_id, post_date_gmt, post_title, permalink, site_name, author_name, excerpt,
               cat_names, img_thumbnail, img_medium, img_large, img_full
        FROM {$table}
        WHERE " . implode( ' AND ', $where ) . "
        ORDER BY {$order_by_sql} {$order}
        LIMIT %d
    ";

    $params[] = $posts_to_show;

    $prepared = $wpdb->prepare( $sql, $params );
    return $wpdb->get_results( $prepared, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
}

/**
 * Render callback for the block (fast: queries the index table + caches).
 *
 * @param array  $attributes Block attributes.
 * @param string $content Block content (unused; dynamic).
 * @return string
 */
function nfp_render_network_featured_posts_block( $attributes, $content ) {
    if ( ! is_multisite() ) {
        return '';
    }

    $posts_to_show = isset( $attributes['postsToShow'] ) ? max( 1, absint( $attributes['postsToShow'] ) ) : 6;
    $layout        = isset( $attributes['layout'] ) ? sanitize_key( $attributes['layout'] ) : 'grid';
    $columns       = isset( $attributes['columns'] ) ? max( 1, min( 6, absint( $attributes['columns'] ) ) ) : 3;

    $show_image = isset( $attributes['showFeaturedImage'] ) ? nfp_to_bool( $attributes['showFeaturedImage'], true ) : true;
    $image_size = isset( $attributes['imageSize'] ) ? sanitize_key( $attributes['imageSize'] ) : 'large';

    $category_slugs   = isset( $attributes['categorySlugs'] ) ? nfp_sanitize_slug_list( $attributes['categorySlugs'] ) : array();
    $exclude_slugs    = isset( $attributes['excludeCategorySlugs'] ) ? nfp_sanitize_slug_list( $attributes['excludeCategorySlugs'] ) : array();
    $include_blog_ids = isset( $attributes['includeBlogIds'] ) ? nfp_sanitize_int_list( $attributes['includeBlogIds'] ) : array();

    $post_type = isset( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : 'post';

    $date_range = isset( $attributes['dateRange'] ) ? sanitize_key( $attributes['dateRange'] ) : 'all';
    $order_by   = isset( $attributes['orderBy'] ) ? sanitize_key( $attributes['orderBy'] ) : 'date';
    $sort_order = isset( $attributes['sortOrder'] ) ? sanitize_key( $attributes['sortOrder'] ) : 'desc';
    $sort_order = in_array( $sort_order, array( 'asc', 'desc' ), true ) ? $sort_order : 'desc';
    $order_by   = in_array( $order_by, array( 'date', 'title' ), true ) ? $order_by : 'date';
    $cutoff_gmt = nfp_date_range_to_cutoff_gmt( $date_range );

    $meta = ( isset( $attributes['meta'] ) && is_array( $attributes['meta'] ) ) ? $attributes['meta'] : array();
    $show_author = isset( $meta['showAuthor'] ) ? nfp_to_bool( $meta['showAuthor'], true ) : true;
    $show_date = isset( $meta['showDate'] ) ? nfp_to_bool( $meta['showDate'], true ) : true;
    $show_site = isset( $meta['showSite'] ) ? nfp_to_bool( $meta['showSite'], true ) : true;
    $show_categories = isset( $meta['showCategories'] ) ? nfp_to_bool( $meta['showCategories'], false ) : false;
    $show_excerpt = isset( $meta['showExcerpt'] ) ? nfp_to_bool( $meta['showExcerpt'], true ) : true;
    $meta_position   = isset( $meta['position'] ) ? sanitize_key( $meta['position'] ) : 'below';
    $meta_separator  = isset( $meta['separator'] ) ? sanitize_text_field( $meta['separator'] ) : ' • ';

	$meta_order = isset( $attributes['metaOrder'] ) ? nfp_sanitize_meta_order( $attributes['metaOrder'] ) : array( 'site', 'author', 'date', 'categories' );

    $meta_style = ( isset( $attributes['metaStyle'] ) && is_array( $attributes['metaStyle'] ) ) ? $attributes['metaStyle'] : array();
    $meta_opacity = isset( $meta_style['opacity'] ) ? (float) $meta_style['opacity'] : 0.8;
    $meta_opacity = max( 0.3, min( 1.0, $meta_opacity ) );
    $meta_uppercase = isset( $meta_style['uppercase'] ) ? nfp_to_bool( $meta_style['uppercase'], false ) : false;
	$meta_font_size = isset( $meta_style['fontSize'] ) ? absint( $meta_style['fontSize'] ) : 14;
	$meta_font_size = max( 10, min( 28, $meta_font_size ) );
	$meta_text_color = isset( $meta_style['textColor'] ) ? sanitize_hex_color( $meta_style['textColor'] ) : '';
	if ( null === $meta_text_color ) {
		$meta_text_color = '';
	}
    

    $blog_ids = nfp_get_allowed_blog_ids_effective( $include_blog_ids );

    $ttl           = (int) get_site_option( 'nfp_cache_ttl', 300 );
    $cache_version = (int) get_site_option( 'nfp_cache_version', 1 );

    $cache_key_data = array(
    'v' => $cache_version,
    'a' => array(
        // Query-shaping attrs.
        'postsToShow' => $posts_to_show,
        'cat'         => $category_slugs,
        'ex'          => $exclude_slugs,
        'blogs'       => $blog_ids,
        'postType'    => $post_type,

        // Presentation attrs.
        'showImage'   => $show_image,
        'imageSize'   => $image_size,
        'layout'      => $layout,
        'columns'     => $columns,
        'meta'        => array(
            'showSite'       => $show_site,
            'showAuthor'     => $show_author,
            'showDate'       => $show_date,
            'showCategories' => $show_categories,
            'showExcerpt'    => $show_excerpt,
            'position'       => $meta_position,
            'separator'      => $meta_separator,
        ),
        'metaStyle'   => array(
            'opacity'   => $meta_opacity,
            'uppercase' => $meta_uppercase,
                    ),
    ),
);

$cache_key   = 'nfp_' . md5( wp_json_encode( $cache_key_data ) );
    $cache_group = 'nfp';

    $rows = wp_cache_get( $cache_key, $cache_group );
    if ( false === $rows ) {
        $rows = get_transient( $cache_key );
    }

    if ( false === $rows || ! is_array( $rows ) ) {
        $rows = nfp_query_index(
            array(
                'posts_to_show' => $posts_to_show,
                'blog_ids'      => $blog_ids,
                'cat_slugs'     => $category_slugs,
                'exclude_slugs' => $exclude_slugs,
                'post_type'     => $post_type,
                'cutoff_gmt'    => $cutoff_gmt,
                'order_by'      => $order_by,
                'order'         => $sort_order,
            )
        );

        
// On-demand index seeding: if WP-Cron backfill hasn't run (common on large networks),
// and the block is explicitly targeting certain sites, seed those sites once.
if ( empty( $rows ) && ! empty( $include_blog_ids ) ) {
    $seeded = 0;
    foreach ( $include_blog_ids as $bid ) {
        if ( $seeded >= 3 ) {
            break;
        }
        $bid = absint( $bid );
        if ( $bid <= 0 ) {
            continue;
        }
        if ( ! nfp_index_has_rows_for_blog( $bid ) ) {
            nfp_seed_index_for_blog( $bid, 250 );
            $seeded++;
        }
    }

    if ( $seeded > 0 ) {
        $rows = nfp_query_index(
            array(
                'posts_to_show' => $posts_to_show,
                'blog_ids'      => $blog_ids,
                'cat_slugs'     => $category_slugs,
                'exclude_slugs' => $exclude_slugs,
                'post_type'     => $post_type,
                'cutoff_gmt'    => $cutoff_gmt,
                'order_by'      => $order_by,
                'order'         => $sort_order,
            )
        );
    }
}

wp_cache_set( $cache_key, $rows, $cache_group, $ttl );
        set_transient( $cache_key, $rows, $ttl );
    }

    if ( empty( $rows ) ) {
        return '';
    }

    $classes = array(
        'nfp',
        ( 'masonry' === $layout ? 'nfp--masonry' : 'nfp--grid' ),
        'nfp--cols-' . $columns,
    );

    if ( $meta_uppercase ) {
        $classes[] = 'nfp--meta-uppercase';
    }
    if ( $meta_pill ) {
        $classes[] = 'nfp--meta-pill';
    }

    $styles = array(
    '--nfp-meta-opacity:' . $meta_opacity,
    '--nfp-meta-font-size:' . ( $meta_font_size / 16 ) . 'rem',
);
if ( '' !== $meta_text_color ) {
    $styles[] = '--nfp-meta-color:' . $meta_text_color;
}

$wrapper_attributes = get_block_wrapper_attributes(
        array(
            'class' => implode( ' ', array_map( 'sanitize_html_class', $classes ) ),
            'style' => implode( ';', $styles ),
        )
    );

    $img_field = 'img_large';
    if ( 'thumbnail' === $image_size ) {
        $img_field = 'img_thumbnail';
    } elseif ( 'medium' === $image_size ) {
        $img_field = 'img_medium';
    } elseif ( 'full' === $image_size ) {
        $img_field = 'img_full';
    }

    ob_start();
    ?>
    <div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <?php foreach ( $rows as $row ) : ?>
            <article class="nfp__card">
                <?php if ( $show_image && ! empty( $row[ $img_field ] ) ) : ?>
                    <a class="nfp__thumb" href="<?php echo esc_url( $row['permalink'] ); ?>">
                        <img src="<?php echo esc_url( $row[ $img_field ] ); ?>" alt="" loading="lazy" />
                    </a>
                <?php endif; ?>

                <div class="nfp__body">
                    <h3 class="nfp__title">
                        <a href="<?php echo esc_url( $row['permalink'] ); ?>">
                            <?php echo esc_html( $row['post_title'] ); ?>
                        </a>
                    </h3>

                    <?php
                    $meta_bits = array();

foreach ( $meta_order as $field ) {
    if ( 'site' === $field && $show_site && ! empty( $row['site_name'] ) ) {
        $meta_bits[] = sanitize_text_field( $row['site_name'] );
    } elseif ( 'author' === $field && $show_author && ! empty( $row['author_name'] ) ) {
        $meta_bits[] = sanitize_text_field( $row['author_name'] );
    } elseif ( 'date' === $field && $show_date && ! empty( $row['post_date_gmt'] ) ) {
        $meta_bits[] = sanitize_text_field( date_i18n( get_option( 'date_format' ), (int) $row['post_date_gmt'] ) );
    } elseif ( 'categories' === $field && $show_categories && ! empty( $row['cat_names'] ) ) {
        $meta_bits[] = sanitize_text_field( $row['cat_names'] );
    }
}

$meta_text = '';
if ( ! empty( $meta_bits ) ) {
    // Separator is user-controlled; escape it safely.
    $sep = wp_strip_all_tags( $meta_separator );
    $meta_text = implode( $sep, array_map( 'esc_html', $meta_bits ) );
}
?>

                    <?php if ( 'above' === $meta_position && '' !== $meta_text ) : ?>
                        <div class="nfp__meta"><?php echo wp_kses_post( $meta_text ); ?></div>
                    <?php endif; ?>

                    <?php if ( $show_excerpt && ! empty( $row['excerpt'] ) ) : ?>
                        <p class="nfp__excerpt"><?php echo esc_html( $row['excerpt'] ); ?></p>
                    <?php endif; ?>

                    <?php if ( 'below' === $meta_position && '' !== $meta_text ) : ?>
                        <div class="nfp__meta"><?php echo wp_kses_post( $meta_text ); ?></div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
