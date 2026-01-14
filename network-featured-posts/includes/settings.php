<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register network admin settings page.
 *
 * @return void
 */
function nfp_register_network_admin_menu() {
    add_submenu_page(
        'settings.php',
        __( 'Network Featured Posts', 'network-featured-posts' ),
        __( 'Network Featured Posts', 'network-featured-posts' ),
        'manage_network_options',
        'nfp-settings',
        'nfp_render_network_settings_page'
    );
}

/**
 * Render settings page.
 *
 * @return void
 */
function nfp_render_network_settings_page() {
    if ( ! current_user_can( 'manage_network_options' ) ) {
        return;
    }

    if ( isset( $_POST['nfp_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nfp_settings_nonce'] ) ), 'nfp_save_settings' ) ) {
        $ttl = isset( $_POST['nfp_cache_ttl'] ) ? absint( $_POST['nfp_cache_ttl'] ) : 300;
        update_site_option( 'nfp_cache_ttl', max( 30, $ttl ) );

        $allowed = isset( $_POST['nfp_allowed_blog_ids'] ) ? (array) $_POST['nfp_allowed_blog_ids'] : array();
        $allowed = array_map( 'absint', $allowed );
        $allowed = array_values( array_filter( array_unique( $allowed ) ) );
        update_site_option( 'nfp_allowed_blog_ids', $allowed );

        if ( isset( $_POST['nfp_backfill_start'] ) ) {
            nfp_set_backfill_state( array( 'running' => true, 'done' => false ) );
        }
        if ( isset( $_POST['nfp_backfill_stop'] ) ) {
            $state = nfp_get_backfill_state();
            $state['running'] = false;
            nfp_set_backfill_state( $state );
        }

        echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'network-featured-posts' ) . '</p></div>';
    }

    $ttl     = (int) get_site_option( 'nfp_cache_ttl', 300 );
    $allowed = get_site_option( 'nfp_allowed_blog_ids', array() );
    $allowed = is_array( $allowed ) ? $allowed : array();

    $state   = nfp_get_backfill_state();
    $running = ! empty( $state['running'] );
    $done    = ! empty( $state['done'] );

    $sites = get_sites(
        array(
            'number'   => 0,
            'deleted'  => 0,
            'spam'     => 0,
            'archived' => 0,
        )
    );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Network Featured Posts', 'network-featured-posts' ); ?></h1>

        <form method="post">
            <?php wp_nonce_field( 'nfp_save_settings', 'nfp_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Cache TTL (seconds)', 'network-featured-posts' ); ?></th>
                    <td>
                        <input type="number" name="nfp_cache_ttl" value="<?php echo esc_attr( $ttl ); ?>" min="30" step="10" />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php echo esc_html__( 'Allowed Sites (optional)', 'network-featured-posts' ); ?></th>
                    <td>
                        <p class="description"><?php echo esc_html__( 'If none selected, all public sites are eligible. This limits what the block can query and what the editor site picker will show.', 'network-featured-posts' ); ?></p>
                        <select name="nfp_allowed_blog_ids[]" multiple size="10" style="min-width: 420px;">
                            <?php foreach ( $sites as $site ) : ?>
                                <?php
                                $bid     = (int) $site->blog_id;
                                $details = get_blog_details( $bid );
                                $label   = $details ? $details->blogname . ' (ID ' . $bid . ')' : 'ID ' . $bid;
                                ?>
                                <option value="<?php echo esc_attr( $bid ); ?>" <?php selected( in_array( $bid, $allowed, true ) ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php echo esc_html__( 'Index Backfill', 'network-featured-posts' ); ?></th>
                    <td>
                        <p class="description">
                            <?php echo esc_html__( 'Backfill builds the index from existing published posts. It runs in small batches via WP-Cron.', 'network-featured-posts' ); ?>
                        </p>

                        <?php if ( $done ) : ?>
                            <p><strong><?php echo esc_html__( 'Backfill complete.', 'network-featured-posts' ); ?></strong></p>
                        <?php endif; ?>

                        <?php if ( $running ) : ?>
                            <button class="button" name="nfp_backfill_stop" value="1"><?php echo esc_html__( 'Stop Backfill', 'network-featured-posts' ); ?></button>
                        <?php else : ?>
                            <button class="button button-primary" name="nfp_backfill_start" value="1"><?php echo esc_html__( 'Start Backfill', 'network-featured-posts' ); ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
