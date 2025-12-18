<?php

class Blog_Templates_Theme_Selection_Toolbar {
    public $categories;
    public $default_category_id;
    protected $type;

    public function __construct( $type ) {
        $this->type = sanitize_text_field( (string) $type );
        $this->set_categories();

        // Enqueue toolbar assets immediately so they are available on wp-signup.php.
        // We cannot rely on hooking wp_enqueue_scripts here because that action
        // has already fired by the time this class is loaded on the signup page.
        $this->enqueue_assets();
    }

    public function enqueue_assets() {
        if ( ! defined( 'NBTPL_PLUGIN_URL' ) ) {
            return;
        }

        wp_enqueue_script( 'nbt-toolbar-scripts', NBTPL_PLUGIN_URL . 'blogtemplatesfiles/assets/js/toolbar.js', array( 'jquery' ), NBTPL_PLUGIN_VERSION, true );
$params = array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'imagesurl' => esc_url_raw( NBTPL_PLUGIN_URL . 'blogtemplatesfiles/assets/images/' ),
            'nbt_nonce' => wp_create_nonce( 'nbt_toolbar_nonce' ),
            'type' => $this->type,
        );

        wp_localize_script( 'nbt-toolbar-scripts', 'nbt_toolbar_js', $params );

        wp_register_style( 'nbt-toolbar-styles', false, array(), NBTPL_PLUGIN_VERSION );
wp_enqueue_style( 'nbt-toolbar-styles' );

        $inline_css = $this->generate_inline_css();
        if ( $inline_css ) {
            wp_add_inline_style( 'nbt-toolbar-styles', $inline_css );
        }
    }

    protected function generate_inline_css() {
        $options = nbt_get_settings();
        $border  = isset( $options['toolbar-border-color'] ) ? esc_attr( $options['toolbar-border-color'] ) : '#ddd';
        $bg      = isset( $options['toolbar-color'] ) ? esc_attr( $options['toolbar-color'] ) : '#fff';
        $text    = isset( $options['toolbar-text-color'] ) ? esc_attr( $options['toolbar-text-color'] ) : '#000';

        return "
        #nbt-toolbar{width:100%;box-sizing:border-box;margin-bottom:25px;border-top:1px solid {$border};text-align:center;padding-top:25px;}
        #nbt-toolbar a{text-transform:uppercase;display:inline-block;background:{$bg};color:{$text};border-radius:3px;margin:0 5px 5px 0;padding:0 .5em;text-decoration:none;transition:all .2s;font-size:1.1em;line-height:1.5;}
        #nbt-toolbar a:hover{opacity:1!important;}
        #nbt-toolbar .toolbar-item-selected{opacity:.62;}
        #toolbar-loader{text-align:center;padding:50px 0;max-width:100%;width:50px;margin:0 auto;}
        ";
    }

    public function display() {
        // Backwards-compat: older code expects $toolbar->display()
        $this->render_toolbar();
    }

    public function render_toolbar() {
        if ( empty( $this->categories ) ) {
            return;
        }

        $tabs        = array();
        $tabs[0]     = __( 'ALL', 'blogtemplates' );
        foreach ( $this->categories as $category ) {
            $id = isset( $category['ID'] ) ? (int) $category['ID'] : 0;
            $name = isset( $category['name'] ) ? $category['name'] : '';
            if ( 0 === $id ) {
                continue;
            }
            $tabs[ $id ] = $name;
        }

        $tabs = apply_filters( 'nbtpl_selection_toolbar_tabs', $tabs );

        if ( function_exists( 'apply_filters_deprecated' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $tabs = apply_filters_deprecated( 'nbt_selection_toolbar_tabs', array( $tabs ), '3.0.3', 'nbtpl_selection_toolbar_tabs' );
        } else {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $tabs = apply_filters( 'nbt_selection_toolbar_tabs', $tabs );
        }
        $default_tab = apply_filters( 'nbtpl_selection_toolbar_default_tab', key( $tabs ) );

        if ( function_exists( 'apply_filters_deprecated' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $default_tab = apply_filters_deprecated( 'nbt_selection_toolbar_default_tab', array( $default_tab, key( $tabs ) ), '3.0.3', 'nbtpl_selection_toolbar_default_tab' );
        } else {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $default_tab = apply_filters( 'nbt_selection_toolbar_default_tab', $default_tab, key( $tabs ) );
        }
        $this->default_category_id = (int) $default_tab;
        ?>
        <div id="nbt-toolbar" data-toolbar-type="<?php echo esc_attr( $this->type ); ?>">

            <h4 class="nbt-toolbar-title">
            <?php esc_html_e( 'Filter templates by category', 'blogtemplates' ); ?>
            </h4>

            <label class="screen-reader-text" for="nbt-template-category">
                <?php esc_html_e( 'Filter templates by category', 'blogtemplates' ); ?>
            </label>

            <select id="nbt-template-category" class="nbt-template-category-select">
                <?php foreach ( $tabs as $tab_key => $tab_name ) : ?>
                    <option value="<?php echo esc_attr( $tab_key ); ?>"
                        <?php selected( (int) $default_tab, (int) $tab_key ); ?>>
                        <?php echo esc_html( $tab_name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    private function set_categories() {
        $model = nbt_get_model();
        if ( is_object( $model ) && method_exists( $model, 'get_templates_categories' ) ) {
            $this->categories = $model->get_templates_categories();
        } else {
            $this->categories = array();
        }
    }
}

/* AJAX handler */
function nbtpl_filter_categories() {
    // Basic existence checks
    $cat_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
    $type   = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

    // Verify nonce
    $nonce = isset( $_POST['nbt_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nbt_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'nbt_toolbar_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
    }

    // Optionally check capability if this should be restricted:
    // if ( ! current_user_can( 'edit_posts' ) ) {
    //     wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
    // }

    $model = nbt_get_model();
    if ( ! is_object( $model ) || ! method_exists( $model, 'get_templates_by_category' ) ) {
        wp_send_json_error( array( 'message' => 'Model unavailable' ), 500 );
    }

    $templates = $model->get_templates_by_category( $cat_id );

    $options = nbt_get_settings();
    $checked = isset( $options['default'] ) ? $options['default'] : '';

    ob_start();

    if ( '' === $type ) {
        echo '<select name="blog_template">';
        if ( empty( $checked ) ) {
            echo '<option value="none">' . esc_html__( 'None', 'blogtemplates' ) . '</option>';
        }
    }

    foreach ( $templates as $template ) {
        // Provide a safe rendering function; ensure it escapes inside
        if ( function_exists( 'nbt_render_theme_selection_item' ) ) {
            nbt_render_theme_selection_item( $type, (int) $template['ID'], $template, $options );
        }
    }

    if ( '' === $type ) {
        echo '</select>';
    } else {
        echo '<div style="clear:both"></div>';
    }

    $html = ob_get_clean();

    wp_send_json_success( array( 'html' => $html ) );
}


/**
 * Legacy wrapper for backwards-compat.
 *
 * @deprecated 3.0.3 Use nbtpl_filter_categories().
 */
 // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Deprecated wrapper for backwards compatibility.
function nbt_filter_categories() {
	nbtpl_filter_categories();
}

add_action( 'wp_ajax_nbt_filter_categories', 'nbtpl_filter_categories' );
add_action( 'wp_ajax_nopriv_nbt_filter_categories', 'nbtpl_filter_categories' );
