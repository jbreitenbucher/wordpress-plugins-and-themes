<?php
#[\AllowDynamicProperties]
class blog_templates_settings_menu {

    var $menu_slug = 'blog_templates_settings';

    var $page_id;

    var $updated_message = '';


	function __construct() {
		global $wp_version;



        // Admin notices and data processing
        add_action( 'admin_init', array($this, 'admin_options_page_posted' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'add_javascript' ) );


	}


    public function add_javascript($hook) {
    	if ( get_current_screen()->id == $this->page_id . '-network' ) {
            wp_enqueue_style( 'wp-color-picker' );
    		wp_enqueue_script( 'nbt-settings-js', NBTPL_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-settings.js', array( 'jquery', 'wp-color-picker' ), NBTPL_PLUGIN_VERSION, true );
wp_enqueue_style( 'nbt-settings-css', NBTPL_PLUGIN_URL . 'blogtemplatesfiles/assets/css/settings.css', array(), NBTPL_PLUGIN_VERSION );
}
    }

	/**
     * Adds the options subpanel
     *
     * @since 1.2.1
     */
    function network_admin_page() {
        $this->page_id = add_submenu_page( 'blog_templates_main', __( 'Settings', 'blogtemplates' ), __( 'Settings', 'blogtemplates' ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
    }


    /**
     * Adds settings/options page
     *
     * @since 1.0
     */
    function admin_options_page() {

        $settings = nbt_get_settings();

		?>

			<div class="wrap">
			    <form method="post" id="options">
		        	<?php wp_nonce_field('blog_templates-update-options', '_nbtnonce'); ?>

                    
		            <h2><?php esc_html_e('Options', 'blogtemplates'); ?></h2>

					<h3><?php esc_html_e( 'Template selection', 'blogtemplates' ); ?></h3>
		            <table class="form-table">
		            	<?php ob_start(); ?>
			                <label for="show-registration-templates">
			                    <input type="checkbox" <?php checked( !empty($settings['show-registration-templates']) ); ?> name="show-registration-templates" id="show-registration-templates" value="1"/>
			                    <?php esc_html_e('Selecting this option will allow your new users to choose between templates when they sign up for a site.', 'blogtemplates'); ?>
			                </label><br/>
			                <?php $this->render_row( __('Show templates selection on registration:', 'blogtemplates'), ob_get_clean() ); ?>

			            <?php ob_start(); ?>

                        <?php
                            $appearance_template = $settings['registration-templates-appearance'];
                            if ( empty( $appearance_template ) )
                                $appearance_template = 0;

                            $selection_types = nbt_get_template_selection_types();
                        ?>

                        <?php foreach ( $selection_types as $type => $label ): ?>
                            <label for="registration-templates-appearance-<?php echo esc_attr( $type ); ?>">
                                <input type="radio" <?php checked( $appearance_template, $type ); ?> name="registration-templates-appearance" id="registration-templates-appearance-<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $type ); ?>"/>
                                <?php echo esc_html( $label ); ?>
                            </label>
                            <?php if ( $type === 'page_showcase' ) {
                                wp_dropdown_pages( array(
                                    'selected' => absint( $settings['page-showcase-id'] ),
                                    'name' => 'page-showcase-id',
                                    'show_option_none' => esc_html__( 'Select a page', 'blogtemplates' ),
                                    'option_none_value' => ''
                                ) );
                            }
                            ?>
                            <br/>
                        <?php endforeach; ?>


                        <div class="previewer-hidden-fields page_showcase-hidden-fields selection-type-hidden-fields">
                            <label style="margin-left:20px;margin-top:20px;display:block;" for="registration-templates-appearance-button-text">
                                <?php esc_html_e( '"Select this Theme" button text', 'blogtemplates'); ?>
                                <input type="text" name="registration-templates-button-text" id="registration-templates-appearance-button-text" value="<?php echo esc_attr( $settings['previewer_button_text'] ); ?>" />
                            </label>
                        </div>

                        <div class="page_showcase-hidden-fields selection-type-hidden-fields">
                            <label style="margin-left:20px;margin-top:20px;display:block;" for="registration-templates-screenshots-width">
                                <?php esc_html_e( 'Screenshots width', 'blogtemplates'); ?>
                                <input type="text" name="registration-screenshots-width" id="registration-templates-screenshots-width" value="<?php echo esc_attr( $settings['screenshots_width'] ); ?>" class="small-text" /> px
                            </label>
                            <label style="margin-left:20px;margin-top:20px;display:block;">
                                <?php esc_html_e( 'Selected overlay/border color', 'blogtemplates'); ?><br/>
                                <input type="text" class="color-field" id="selected-overlay-color" name="selected-overlay-color" value="<?php echo esc_attr( $settings['overlay_color'] ); ?>" />
                            </label>
                        </div>

                        <div class="screenshot-hidden-fields screenshot_plus-hidden-fields selection-type-hidden-fields">
                            <label style="margin-left:20px;margin-top:20px;display:block;">
                                <?php esc_html_e( 'Unselected background color screenshot', 'blogtemplates'); ?><br/>
                                <input type="text" class="color-field" id="selected-background-color" name="unselected-background-color" value="<?php echo esc_attr( $settings['unselected-background-color'] ); ?>" />
                            </label>
                            <label style="margin-left:20px;margin-top:20px;display:block;">
                                <?php esc_html_e( 'Selected background color screenshot', 'blogtemplates'); ?><br/>
                                <input type="text" class="color-field" id="selected-background-color" name="selected-background-color" value="<?php echo esc_attr( $settings['selected-background-color'] ); ?>" />
                            </label>
                        </div>
                        <?php $this->render_row( __('Type of selection', 'blogtemplates'), ob_get_clean() ); ?>
			        </table>

                    <?php
					$nbtpl_activate_categories_feature = apply_filters( 'nbtpl_activate_categories_feature', true );

					if ( function_exists( 'apply_filters_deprecated' ) ) {
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
						$nbtpl_activate_categories_feature = apply_filters_deprecated( 'nbt_activate_categories_feature', array( $nbtpl_activate_categories_feature ), '3.0.3', 'nbtpl_activate_categories_feature' );
					} else {
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
						$nbtpl_activate_categories_feature = apply_filters( 'nbt_activate_categories_feature', $nbtpl_activate_categories_feature );
					}

					if ( $nbtpl_activate_categories_feature ) :
				?>
                        <h3><?php esc_html_e( 'Categories Toolbar', 'blogtemplates' ); ?></h3>
                        <table class="form-table">
                            <?php ob_start(); ?>
                                <label for="show-categories-selection">
                                    <input type="checkbox" <?php checked( !empty($settings['show-categories-selection']) ); ?> name="show-categories-selection" id="show-categories-selection" value="1"/>
                                    <?php esc_html_e( 'A new toolbar will appear on to on the selection screen. Users will be able to filter by templates categories.', 'blogtemplates'); ?>
                                </label><br/>
                                <?php $this->render_row( __('Show categories menu', 'blogtemplates'), ob_get_clean() ); ?>

                            <?php ob_start(); ?>

                            <?php ob_start(); ?>
                                <label for="toolbar-color">
                                    <input type="text" class="color-field" name="toolbar-color" id="toolbar-color" value="<?php echo esc_attr( $settings['toolbar-color'] ); ?>"/>
                                </label>
                                <?php $this->render_row( __( 'Toolbar background color', 'blogtemplates' ), ob_get_clean() ); ?>

                            <?php ob_start(); ?>

                            <?php ob_start(); ?>
                                <label for="toolbar-text-color">
                                    <input type="text" class="color-field" name="toolbar-text-color" id="toolbar-text-color" value="<?php echo esc_attr( $settings['toolbar-text-color'] ); ?>"/>
                                </label>
                                <?php $this->render_row( __( 'Toolbar text color', 'blogtemplates' ), ob_get_clean() ); ?>

                            <?php ob_start(); ?>

                            <?php ob_start(); ?>
                                <label for="toolbar-border-color">
                                    <input type="text" class="color-field" name="toolbar-border-color" id="toolbar-border-color" value="<?php echo esc_attr( $settings['toolbar-border-color'] ); ?>"/>
                                </label>
                                <?php $this->render_row( __( 'Toolbar border color', 'blogtemplates' ), ob_get_clean() ); ?>

                        </table>
                    <?php endif; ?>
		            <p><div class="submit"><input type="submit" name="save_options" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'blogtemplates' );?>" /></div></p>
                </form>
			   </div>
			<?php
	    }

        /**
        * Adds the Settings link to the plugin activate/deactivate page
        *
        * @param array $links The ID of the blog to copy
        *
        * @since 1.0
        */
        function filter_plugin_actions( $links ) {
            global $wp_version;

            if ( version_compare( $wp_version , '3.0.9', '>' ) )
                $settings_link = '<a href="' . network_admin_url( 'settings.php?page=' . basename(__FILE__) ) . '">' . __( 'Settings', 'blogtemplates' ) . '</a>';
            elseif ( version_compare( $wp_version , '3.0', '<' ) )
                $settings_link = '<a href="wpmu-admin.php?page=' . basename(__FILE__) . '">' . __( 'Settings', 'blogtemplates' ) . '</a>';
            else
                $settings_link = '<a href="ms-admin.php?page=' . basename(__FILE__) . '">' . __( 'Settings', 'blogtemplates' ) . '</a>';
            array_unshift( $links, $settings_link ); // add before other links

            return $links;
        }

        private function render_row( $title, $markup ) {
            ?>
                <tr valign="top">
                    <th scope="row"><label for="site_name"><?php echo esc_html( $title ); ?></label></th>
                    <td>
                        <?php
			// The markup for this row is composed locally in this file and its dynamic values are escaped individually.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $markup;
		?>
                    </td>
                </tr>
            <?php
        }


        /**
         * Separated into its own function so we could include it in the init hook
         *
         * @since 1.0
         */
        function admin_options_page_posted() {
            if ( ! isset( $_GET['page'] ) || $_GET['page'] !== $this->menu_slug )
                return;


            $model = nbt_get_model();
            $settings = nbt_get_settings();


            if ( isset( $_POST['save_options'] ) ) {

    $nonce = isset( $_POST['_nbtnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nbtnonce'] ) ) : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'blog_templates-update-options' ) ) {
        wp_die( esc_html__( 'Whoops! There was a problem with the security check. Please try again. (Generated by New Blog Templates)', 'blogtemplates' ) );
    }

    $defaults  = nbt_get_default_settings();
    $settings['show-registration-templates'] = isset( $_POST['show-registration-templates'] ) ? absint( wp_unslash( $_POST['show-registration-templates'] ) ) : 0;

    $selection_types = nbt_get_template_selection_types();
    $appearance_raw  = isset( $_POST['registration-templates-appearance'] ) ? sanitize_text_field( wp_unslash( $_POST['registration-templates-appearance'] ) ) : '';
    $appearance      = array_key_exists( $appearance_raw, $selection_types ) ? $appearance_raw : key( $selection_types );

    if ( 'page_showcase' === $appearance && ! empty( $_POST['page-showcase-id'] ) && $page = get_post( absint( wp_unslash( $_POST['page-showcase-id'] ) ) ) ) {
        $settings['registration-templates-appearance'] = $appearance;
        $settings['page-showcase-id']                  = $page->ID;
    } elseif ( 'page-showcase' !== $appearance ) {
        $settings['registration-templates-appearance'] = $appearance;
    }

    $settings['show-categories-selection'] = isset( $_POST['show-categories-selection'] ) ? absint( wp_unslash( $_POST['show-categories-selection'] ) ) : 0;

    $toolbar_color = isset( $_POST['toolbar-color'] ) ? sanitize_hex_color( wp_unslash( $_POST['toolbar-color'] ) ) : '';
    $settings['toolbar-color'] = $toolbar_color ? $toolbar_color : $defaults['toolbar-color'];

    $toolbar_text_color = isset( $_POST['toolbar-text-color'] ) ? sanitize_hex_color( wp_unslash( $_POST['toolbar-text-color'] ) ) : '';
    $settings['toolbar-text-color'] = $toolbar_text_color ? $toolbar_text_color : $defaults['toolbar-text-color'];

    $toolbar_border_color = isset( $_POST['toolbar-border-color'] ) ? sanitize_hex_color( wp_unslash( $_POST['toolbar-border-color'] ) ) : '';
    $settings['toolbar-border-color'] = $toolbar_border_color ? $toolbar_border_color : $defaults['toolbar-border-color'];

    $selected_background_color = isset( $_POST['selected-background-color'] ) ? sanitize_hex_color( wp_unslash( $_POST['selected-background-color'] ) ) : '';
    $settings['selected-background-color'] = $selected_background_color ? $selected_background_color : $defaults['selected-background-color'];

    $unselected_background_color = isset( $_POST['unselected-background-color'] ) ? sanitize_hex_color( wp_unslash( $_POST['unselected-background-color'] ) ) : '';
    $settings['unselected-background-color'] = $unselected_background_color ? $unselected_background_color : $defaults['unselected-background-color'];

    // Overlay color may be rgba or other CSS color value; use a generic text sanitizer.
    $overlay_color_raw = isset( $_POST['selected-overlay-color'] ) ? sanitize_text_field( wp_unslash( $_POST['selected-overlay-color'] ) ) : '';
    $settings['overlay_color'] = $overlay_color_raw ? sanitize_text_field( $overlay_color_raw ) : $defaults['overlay_color'];

    if ( ! empty( $_POST['registration-templates-button-text'] ) ) {
        $settings['previewer_button_text'] = sanitize_text_field( wp_unslash( $_POST['registration-templates-button-text'] ) );
    }

    if ( ! empty( $_POST['registration-screenshots-width'] ) ) {
        $settings['screenshots_width'] = absint( wp_unslash( $_POST['registration-screenshots-width'] ) );
    }

    nbt_update_settings( $settings );

    $this->updated_message = __( 'Options saved.', 'blogtemplates' );
}

            if ( isset( $_POST['submit_repair_database'] ) && isset( $_POST['action'] ) && 'repair_database' == $_POST['action' ] ) {

$repair_nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
if ( ! $repair_nonce || ! wp_verify_nonce( $repair_nonce, 'repair-database' ) ) {
    return false;
}

                if ( ! ( isset( $_POST['repair-tables'] ) ) )
                    return false;

                $model = nbt_get_model();
                $model->create_tables();

            }

        }

        public function show_admin_notice() {
        	?>
				<div class="updated">
					<p><?php echo esc_html( $this->updated_message ); ?></p>
				</div>
        	<?php
        }

}