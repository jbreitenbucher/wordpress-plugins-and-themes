<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get index table name (network-wide).
 *
 * @return string
 */
function nfp_get_index_table_name() {
    global $wpdb;
    return $wpdb->base_prefix . 'network_featured_posts_index';
}

/**
 * Plugin activation: create/upgrade index table and set defaults.
 *
 * @param bool $network_wide Whether plugin was network-activated.
 * @return void
 */
function nfp_activate_plugin( $network_wide ) {
    if ( ! is_multisite() ) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    global $wpdb;
    $table            = nfp_get_index_table_name();
    $charset_collate  = $wpdb->get_charset_collate();

    // Note: dbDelta will handle adding new columns/keys on upgrades.
    $sql = "CREATE TABLE {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        blog_id BIGINT(20) UNSIGNED NOT NULL,
        post_id BIGINT(20) UNSIGNED NOT NULL,
        post_date_gmt BIGINT(20) UNSIGNED NOT NULL,
        post_title TEXT NOT NULL,
        permalink TEXT NOT NULL,
        site_name TEXT NOT NULL,
        author_name TEXT NOT NULL,
        excerpt TEXT NOT NULL,
        cat_slugs TEXT NOT NULL,
        cat_names TEXT NOT NULL,
        img_thumbnail TEXT NOT NULL,
        img_medium TEXT NOT NULL,
        img_large TEXT NOT NULL,
        img_full TEXT NOT NULL,
        post_type VARCHAR(20) NOT NULL DEFAULT 'post',
        post_status VARCHAR(20) NOT NULL DEFAULT 'publish',
        PRIMARY KEY  (id),
        UNIQUE KEY blog_post (blog_id, post_id),
        KEY date_gmt (post_date_gmt),
        KEY post_type (post_type),
        KEY post_status (post_status)
    ) {$charset_collate};";

    dbDelta( $sql );

    // Default network settings.
    if ( false === get_site_option( 'nfp_allowed_blog_ids', false ) ) {
        update_site_option( 'nfp_allowed_blog_ids', array() ); // empty = all public sites.
    }
    if ( false === get_site_option( 'nfp_cache_ttl', false ) ) {
        update_site_option( 'nfp_cache_ttl', 300 ); // seconds.
    }
    if ( false === get_site_option( 'nfp_cache_version', false ) ) {
        update_site_option( 'nfp_cache_version', 1 );
    }

    // Cron schedule handled in includes/cron.php.
    if ( ! wp_next_scheduled( 'nfp_backfill_cron' ) ) {
        wp_schedule_event( time() + 60, 'minute', 'nfp_backfill_cron' );
    }
}

/**
 * Plugin deactivation: unschedule cron.
 *
 * @return void
 */
function nfp_deactivate_plugin() {
    $timestamp = wp_next_scheduled( 'nfp_backfill_cron' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'nfp_backfill_cron' );
    }
}
