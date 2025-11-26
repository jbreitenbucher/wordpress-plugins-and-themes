<?php
#[\AllowDynamicProperties]
class NBT_Lock_Posts {

    /**
     * Post types that support locking.
     */
    private $_lock_types = array(
        'post',
        'page',
    );

    /**
     * Meta key used for lock.
     */
    private $meta_key = 'nbt_block_post';

    public function __construct() {
        if ( ! apply_filters( 'nbt_activate_block_posts_feature', true ) ) {
            return;
        }

        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_post_lock' ), 10, 2 );

        // Filter user capabilities: signature is ($all_caps, $caps, $args)
        add_filter( 'user_has_cap', array( $this, 'kill_edit_cap' ), 10, 3 );

        // Admin init check for direct edit URL access
        add_action( 'admin_init', array( $this, 'check_and_redirect' ) );
    }

    public function add_meta_box() {
        if ( ! is_super_admin() ) {
            return;
        }

        foreach ( $this->_lock_types as $type ) {
            add_meta_box(
                'nbt_post_lock_meta_box',
                __( 'Post Status', 'blog_templates' ),
                array( $this, 'meta_box_output' ),
                $type,
                'side',
                'high'
            );
        }
    }

    public function meta_box_output( $post ) {
        if ( ! is_super_admin() ) {
            return;
        }

        $post_lock_status = get_post_meta( $post->ID, $this->meta_key, true );
        $post_lock_status = $post_lock_status ? 'locked' : 'unlocked';

        wp_nonce_field( 'nbt_post_lock_nonce', 'nbt_post_lock_nonce_field' );
        ?>
        <p>
            <label for="nbt_post_lock_status" class="screen-reader-text"><?php esc_html_e( 'Post Status', 'blog_templates' ); ?></label>
            <select name="nbt_post_lock_status" id="nbt_post_lock_status">
                <option value="locked" <?php selected( $post_lock_status, 'locked' ); ?>><?php esc_html_e( 'Locked', 'blog_templates' ); ?></option>
                <option value="unlocked" <?php selected( $post_lock_status, 'unlocked' ); ?>><?php esc_html_e( 'Unlocked', 'blog_templates' ); ?></option>
            </select>
        </p>
        <p><?php esc_html_e( 'Locked posts cannot be edited by anyone other than Super admins.', 'blog_templates' ); ?></p>
        <?php
    }

    public function save_post_lock( $post_id, $post ) {
        // Bail on autosave, revisions, or lack of nonce.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( ! is_super_admin() ) {
            return;
        }

        if ( empty( $_POST['nbt_post_lock_nonce_field'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nbt_post_lock_nonce_field'] ), 'nbt_post_lock_nonce' ) ) {
            return;
        }

        if ( ! isset( $_POST['nbt_post_lock_status'] ) ) {
            return;
        }

        $status = sanitize_text_field( wp_unslash( $_POST['nbt_post_lock_status'] ) );
        if ( 'locked' === $status ) {
            update_post_meta( $post_id, $this->meta_key, '1' );
        } else {
            delete_post_meta( $post_id, $this->meta_key );
        }
    }

    /**
     * Filter capabilities so locked posts cannot be edited by non-super-admins.
     *
     * @param array $all_caps All the user's capabilities.
     * @param array $caps     Actual capability names being checked.
     * @param array $args     Additional args. For edit_post, $args[0] == 'edit_post', $args[2] == $post_id
     * @return array Modified $all_caps
     */
    public function kill_edit_cap( $all_caps, $caps, $args ) {
        // We expect args: capability, maybe object id, maybe user id depending on call.
        if ( empty( $args ) || ! isset( $args[0] ) ) {
            return $all_caps;
        }

        // Only care about edit_post capability checks.
        if ( 'edit_post' !== $args[0] && ! in_array( 'edit_post', (array) $caps, true ) ) {
            return $all_caps;
        }

        // If user already cannot publish posts, no further changes needed.
        if ( empty( $all_caps['publish_posts'] ) ) {
            return $all_caps;
        }

        // Super admins keep full access.
        if ( is_super_admin() ) {
            return $all_caps;
        }

        // Determine post ID: in many calls $args[2] is post ID.
        $post_id = 0;
        if ( isset( $args[2] ) && is_numeric( $args[2] ) ) {
            $post_id = (int) $args[2];
        } elseif ( isset( $args[1] ) && is_numeric( $args[1] ) ) {
            $post_id = (int) $args[1];
        }

        if ( ! $post_id ) {
            return $all_caps;
        }

        $blocked = get_post_meta( $post_id, $this->meta_key, true );

        if ( $blocked ) {
            // Ensure we block capability attempting to edit this post.
            foreach ( (array) $caps as $cap_name ) {
                $all_caps[ $cap_name ] = false;
            }
        }

        return $all_caps;
    }

    /**
     * Prevent direct access to the post edit screen for non-super-admins.
     */
    public function check_and_redirect() {
        if ( is_super_admin() ) {
            return;
        }

        if ( ! is_admin() ) {
            return;
        }

        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        $post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;

        if ( 'edit' !== $action || ! $post_id ) {
            return;
        }

        $blocked = get_post_meta( $post_id, $this->meta_key, true );

        if ( $blocked ) {
            // Redirect to a safe admin page; include post id in query var safely.
            wp_safe_redirect( admin_url( 'edit.php?page=post-locked&post=' . $post_id ) );
            exit;
        }
    }
}

$lock_posts = new NBT_Lock_Posts();
