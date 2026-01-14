<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add a 1-minute cron schedule (used only for controlled backfill batching).
 */
add_filter(
    'cron_schedules',
    function( $schedules ) {
        if ( ! isset( $schedules['minute'] ) ) {
            $schedules['minute'] = array(
                'interval' => 60,
                'display'  => __( 'Every Minute', 'network-featured-posts' ),
            );
        }
        return $schedules;
    }
);

/**
 * Read backfill state.
 *
 * @return array
 */
function nfp_get_backfill_state() {
    $state = get_site_option( 'nfp_backfill_state', array() );
    return is_array( $state ) ? $state : array();
}

/**
 * Save backfill state.
 *
 * @param array $state State.
 * @return void
 */
function nfp_set_backfill_state( $state ) {
    update_site_option( 'nfp_backfill_state', $state );
}

add_action( 'nfp_backfill_cron', 'nfp_run_backfill_batch' );

/**
 * Backfill in small batches to avoid timeouts.
 *
 * @return void
 */
function nfp_run_backfill_batch() {
    if ( ! is_multisite() ) {
        return;
    }

    $state   = nfp_get_backfill_state();
    $running = ! empty( $state['running'] );

    if ( ! $running ) {
        return;
    }

    $blog_ids   = ( isset( $state['blog_ids'] ) && is_array( $state['blog_ids'] ) ) ? $state['blog_ids'] : array();
    $blog_index = isset( $state['blog_index'] ) ? (int) $state['blog_index'] : 0;
    $offset     = isset( $state['offset'] ) ? (int) $state['offset'] : 0;

    $batch_size = 100;

    if ( empty( $blog_ids ) ) {
        $sites = get_sites(
            array(
                'number'   => 0,
                'deleted'  => 0,
                'spam'     => 0,
                'archived' => 0,
            )
        );

        foreach ( $sites as $site ) {
            $blog_ids[] = (int) $site->blog_id;
        }

        $blog_index = 0;
        $offset     = 0;
    }

    if ( ! isset( $blog_ids[ $blog_index ] ) ) {
        // Done.
        $state['running'] = false;
        $state['done']    = true;
        nfp_set_backfill_state( $state );
        return;
    }

    $blog_id = (int) $blog_ids[ $blog_index ];

    switch_to_blog( $blog_id );

    $q = new WP_Query(
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $batch_size,
            'offset'         => $offset,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        )
    );

    $post_ids = $q->posts;

    restore_current_blog();

    if ( empty( $post_ids ) ) {
        // Move to next site.
        $blog_index++;
        $offset = 0;
    } else {
        // Index this batch.
        switch_to_blog( $blog_id );
        foreach ( $post_ids as $pid ) {
            nfp_upsert_index_row_for_post( (int) $pid );
        }
        restore_current_blog();

        $offset += $batch_size;
    }

    $state['running']    = true;
    $state['blog_ids']   = $blog_ids;
    $state['blog_index'] = $blog_index;
    $state['offset']     = $offset;
    nfp_set_backfill_state( $state );
}
