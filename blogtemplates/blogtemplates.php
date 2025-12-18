<?php
/*
Plugin Name: New Blog Templates
Plugin URI: https://github.com/jbreitenbucher/wordpress-plugins-and-themes/tree/master/blogtemplates
Description: Allows the site admin to create new blogs based on templates, to speed up the blog creation process, and allows users to choose from available templates when creating a site.
Author: WPMU DEV/The College of Wooster
Author URI: https://wooster.edu/
Version: 3.0.4
Requires at least: 6.2
Network: true
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: blogtemplates
Domain Path: /lang
WDP ID: 130
*/

/*  Copyright 2025-?? (https://wooster.edu)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Plugin constants.
// Use modern, 4+ character prefix constants as the canonical names.
// Legacy NBT_* constants are preserved as aliases for back-compat.
if ( ! defined( 'NBTPL_PLUGIN_VERSION' ) ) {
	define( 'NBTPL_PLUGIN_VERSION', '3.0.4' );
}
if ( ! is_multisite() ) {
	wp_die( esc_html__( 'The New Blog Template plugin is only compatible with WordPress Multisite.', 'blogtemplates' ) );
}
if ( ! defined( 'NBTPL_PLUGIN_DIR' ) ) {
	define( 'NBTPL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'NBTPL_PLUGIN_URL' ) ) {
	define( 'NBTPL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'NBTPL_PLUGIN_LANG_DOMAIN' ) ) {
	define( 'NBTPL_PLUGIN_LANG_DOMAIN', 'blogtemplates' );
}

// Back-compat aliases (deprecated): keep older constant names working.
if ( ! defined( 'NBT_PLUGIN_VERSION' ) ) {
	define( 'NBT_PLUGIN_VERSION', NBTPL_PLUGIN_VERSION );
}
if ( ! defined( 'NBT_PLUGIN_DIR' ) ) {
	define( 'NBT_PLUGIN_DIR', NBTPL_PLUGIN_DIR );
}
if ( ! defined( 'NBT_PLUGIN_URL' ) ) {
	define( 'NBT_PLUGIN_URL', NBTPL_PLUGIN_URL );
}
if ( ! defined( 'NBT_PLUGIN_LANG_DOMAIN' ) ) {
	define( 'NBT_PLUGIN_LANG_DOMAIN', NBTPL_PLUGIN_LANG_DOMAIN );
}

require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/model.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/settings-handler.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/helpers.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/filters.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/tables/templates_table.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/tables/categories_table.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates_theme_selection_toolbar.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/integration.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/ajax.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates_lock_posts.php' );


if ( is_network_admin() ) {
}

require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates_lock_posts.php' );
require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/settings-handler.php' );

// Disabled WPMU DEV dashboard notification file for PHP 8+ compatibility.
// include_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/externals/wpmudev-dash-notification.php' );
// WPMU DEV notices global removed (notification integration disabled).

if ( is_network_admin() ) {
	require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/tables/templates_table.php' );
	require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/tables/categories_table.php' );

}

/**
 * Load the plugin text domain and MO files
 *
 * These can be uploaded to the main WP Languages folder
 * or the plugin one
 */
function nbtpl_load_text_domain() {

	$locale = apply_filters( 'plugin_locale', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WP filter.
		 get_locale(), NBTPL_PLUGIN_LANG_DOMAIN );

	load_textdomain( NBTPL_PLUGIN_LANG_DOMAIN, WP_LANG_DIR . '/' . NBTPL_PLUGIN_LANG_DOMAIN . '/' . NBTPL_PLUGIN_LANG_DOMAIN . '-' . $locale . '.mo' );
}
add_action( 'plugins_loaded', 'nbtpl_load_text_domain' );


function nbtpl_get_default_screenshot_url( $blog_id ) {
	switch_to_blog($blog_id);
	$img = untrailingslashit(dirname(get_stylesheet_uri())) . '/screenshot.png';
	restore_current_blog();
	return $img;
}

function nbtpl_display_page_showcase( $content ) {
	if ( is_page() ) {
		$settings = nbt_get_settings();
		if ( 'page_showcase' == $settings['registration-templates-appearance'] && is_page( $settings['page-showcase-id'] ) && is_main_site() ) {

            $tpl_file = "blog_templates-registration-page-showcase.php";
            $templates = $settings['templates'];

            // Setup theme file
            ob_start();
            $theme_file = locate_template( array( $tpl_file ) );
            $theme_file = $theme_file ? $theme_file : NBTPL_PLUGIN_DIR . '/blogtemplatesfiles/template/' . $tpl_file;
            if ( ! file_exists( $theme_file ) )
                return false;

            nbtpl_render_theme_selection_scripts( $settings );


            @include $theme_file;

			$content .= ob_get_clean();

		}
	}

	return $content;
}
add_filter( 'the_content', 'nbtpl_display_page_showcase' );

function nbtpl_get_showcase_redirection_location( $location = false ) {
	$settings = nbt_get_settings();

	if ( ! $settings['show-registration-templates'] ) {
		return false;
	}

	if ( 'page_showcase' !== $settings['registration-templates-appearance'] )
		return $location;

	$redirect_to = get_permalink( $settings['page-showcase-id'] );
	if ( ! $redirect_to ) {
		return $location;
	}

	// The selected blog template is passed via the request when redirecting from the signup page.
	// This does not perform a state-changing action, so a nonce is not strictly required,
	// but the value is sanitized and unslashed before use.
	$blog_template = '';
	if ( isset( $_REQUEST['blog_template'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$blog_template_raw = sanitize_text_field( wp_unslash( $_REQUEST['blog_template'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$blog_template     = ( 'just_user' === $blog_template_raw ) ? 'just_user' : absint( $blog_template_raw );
	}


	if ( 'just_user' === $blog_template ) {
		return $location;
	}

	$model = nbt_get_model();
	$default_template_id = $model->get_default_template_id();

	if ( empty( $blog_template ) && ! $default_template_id ) {
		return $redirect_to;
	}

	$blog_template = ! empty( $blog_template ) ? absint( $blog_template ) : $default_template_id;

	$model    = nbt_get_model();
	$template = $model->get_template( $blog_template );

	if ( ! $template ) {
		return $redirect_to;
	}

	return $location;
}

function nbtpl_redirect_signup() {
	global $pagenow;

	if ( 'wp-signup.php' == $pagenow ) {

		$redirect_to = nbtpl_get_showcase_redirection_location();

		if ( $redirect_to ) {
			wp_safe_redirect( $redirect_to );
			exit();
		}

	}
}
add_action( 'init', 'nbtpl_redirect_signup', 5 );

function nbtpl_bp_redirect_signup_location() {
	if ( ! class_exists( 'BuddyPress' ) )
		return;

	if ( is_admin() || ! bp_has_custom_signup_page() )
		return;

	// If the user has selected a template, do not redirect.
	if ( isset( $_GET['blog_template'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// Only used to detect when a template has been chosen; no state is changed.
		return;
	}

	$signup_slug = bp_get_signup_slug();
	if ( ! $signup_slug )
		return;

	$page = get_posts(
		array(
			'name' => $signup_slug,
			'post_type' => 'page'
		)
	);

	if ( empty( $page ) )
		return;

	$page = $page[0];
	$is_bp_signup_page = is_page( $page->ID );

	if ( $is_bp_signup_page ) {
		$redirect_to = nbtpl_get_showcase_redirection_location();
		if ( $redirect_to ) {
			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

}
add_action( 'template_redirect', 'nbtpl_bp_redirect_signup_location', 15 );

function nbtpl_render_theme_selection_item( $type, $tkey, $template, $options = array() ) {

	$selected = isset( $_REQUEST['blog_template'] ) ? absint( wp_unslash( $_REQUEST['blog_template'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

	if ( $selected == $tkey ) {
		$default = "blog_template-default_item";
	}
	else {
		$default = @$options['default'] == $tkey ? "blog_template-default_item" : "";
	}

	if ( 'previewer' == $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbtpl_get_default_screenshot_url( $template['blog_id'] );
		$tplid = $template['name'];
		$blog_url = get_site_url( $template['blog_id'], '', 'http' );
		?>
			<div class="template-signup-item theme-previewer-wrap <?php echo esc_attr( $default ); ?>" data-tkey="<?php echo esc_attr( $tkey ); ?>" id="theme-previewer-wrap-<?php echo esc_attr( $tkey ); ?>">

				<a href="#<?php echo esc_attr( $tplid ); ?>" class="blog_template-item_selector">
					<img src="<?php echo esc_url( $img ); ?>" />
					<input type="radio" name="blog_template" id="blog-template-radio-<?php echo esc_attr( $tkey );?>" <?php checked( ! empty( $default ) ); ?> value="<?php echo esc_attr( $tkey ); ?>" style="display: none" />
				</a>
				<div class="theme-previewer-overlay">
					<div class="template-name"><?php echo esc_html( $tplid ); ?></div><br/>
					<button rel="nofollow" class="view-demo-button" data-blog-url="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'View demo', 'blogtemplates' ); ?></button><br/><br/>
					<button class="select-theme-button" data-theme-key="<?php echo esc_attr( $tkey ); ?>"><?php echo esc_html( $options['previewer_button_text'] ); ?></button>
				</div>

				<?php if ( ! empty( $template['description'] ) ): ?>
					<div id="nbt-desc-pointer-<?php echo esc_attr( $tkey ); ?>" class="nbt-desc-pointer">
						<?php echo wp_kses_post( nl2br( $template['description'] ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php
	}
	elseif ( 'page-showcase' == $type || 'page_showcase' == $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbtpl_get_default_screenshot_url( $template['blog_id'] );
		$tplid = $template['name'];
		$blog_url = get_site_url( $template['blog_id'], '', 'http' );

		if ( class_exists( 'BuddyPress' ) ) {
			$sign_up_url = bp_get_signup_page();
		}
		else {
			$sign_up_url = network_site_url( 'wp-signup.php' );
			$sign_up_url = apply_filters( 'wp_signup_location', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WP filter.
		 $sign_up_url );
		}
		$sign_up_url = add_query_arg( 'blog_template', $tkey, $sign_up_url );
		?>
			<div class="template-signup-item theme-page-showcase-wrap <?php echo esc_attr( $default ); ?>" data-tkey="<?php echo esc_attr( $tkey ); ?>" id="theme-page-showcase-wrap-<?php echo esc_attr( $tkey ); ?>">

				<a href="#<?php echo esc_attr( $tplid ); ?>" class="blog_template-item_selector">
					<img src="<?php echo esc_url( $img ); ?>" />
				</a>
				<div class="theme-page-showcase-overlay">
					<div class="template-name"><?php echo esc_html( $tplid ); ?></div><br/>
					<button rel="nofollow" class="view-demo-button" data-blog-url="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'View demo', 'blogtemplates' ); ?></button><br/><br/>
					<button class="select-theme-button" data-signup-url="<?php echo esc_url( $sign_up_url );?>"><?php echo esc_html( $options['previewer_button_text'] ); ?></button>
				</div>

				<?php if ( ! empty( $template['description'] ) ): ?>
					<div id="nbt-desc-pointer-<?php echo esc_attr( $tkey ); ?>" class="nbt-desc-pointer">
						<?php echo wp_kses_post( nl2br( $template['description'] ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php
	}
	elseif ( 'screenshot' === $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbtpl_get_default_screenshot_url( $template['blog_id'] );
		$tplid = preg_replace('/[^a-z0-9]/i', '', strtolower($template['name'])) . "-{$tkey}";
		?>
			<div class="template-signup-item theme-screenshot-wrap <?php echo esc_attr( $default ); ?>" data-tkey="<?php echo esc_attr( $tkey ); ?>" id="theme-screenshot-wrap-<?php echo esc_attr( $tkey ); ?>">
				<a href="#<?php echo esc_attr( $tplid ); ?>" data-theme-key="<?php echo esc_attr( $tkey ); ?>" class="blog_template-item_selector <?php echo esc_attr( $default ); ?>">
					<img src="<?php echo esc_url( $img ); ?>" />
					<input type="radio" id="blog-template-radio-<?php echo esc_attr( $tkey );?>" <?php checked( ! empty( $default ) ); ?> name="blog_template" value="<?php echo esc_attr( $tkey ); ?>" style="display: none" />
				</a>

				<?php if ( ! empty( $template['description'] ) ): ?>
					<div id="nbt-desc-pointer-<?php echo esc_attr( $tkey ); ?>" class="nbt-desc-pointer">
						<?php echo wp_kses_post( nl2br( $template['description'] ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php
	}
	elseif ( 'screenshot_plus' === $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbtpl_get_default_screenshot_url( $template['blog_id'] );
		$tplid = preg_replace('/[^a-z0-9]/i', '', strtolower($template['name'])) . "-{$tkey}";
		?>
			<div class="template-signup-item theme-screenshot-plus-wrap" id="theme-screenshot-plus-wrap-<?php echo esc_attr( $tkey ); ?>">
				<h4><?php echo esc_html( wp_strip_all_tags( $template['name'] ) ); ?></h4>
				<div class="theme-screenshot-plus-image-wrap <?php echo esc_attr( $default ); ?>" id="theme-screenshot-plus-image-wrap-<?php echo esc_attr( $tkey ); ?>">
					<a href="#<?php echo esc_attr( $tplid ); ?>" data-theme-key="<?php echo esc_attr( $tkey ); ?>" class="blog_template-item_selector">
						<img src="<?php echo esc_url( $img ); ?>" />
						<input type="radio" id="blog-template-radio-<?php echo esc_attr( $tkey );?>" <?php checked( ! empty( $default ) ); ?> name="blog_template" value="<?php echo esc_attr( $tkey ); ?>" style="display: none" />
					</a>
				</div>
				<p class="blog_template-description">
					<?php echo wp_kses_post( nl2br( $template['description'] ) ); ?>
				</p>
			</div>
		<?php

	}
	elseif ( 'description' === $type ) {
		?>
			<div class="template-signup-item theme-radio-wrap" id="theme-screenshot-radio-<?php echo esc_attr( $tkey ); ?>">
				<label for="blog_template-<?php echo esc_attr( $tkey ); ?>">
					<input type="radio" id="blog_template-<?php echo esc_attr( $tkey ); ?>" name="blog_template" <?php checked( ! empty( $default ) ); ?> value="<?php echo esc_attr( $tkey ); ?>" />
					<strong><?php echo esc_html( wp_strip_all_tags( $template['name'] ) ); ?></strong>
				</label>
				<div class="blog_template-description">
					<?php echo wp_kses_post( nl2br( $template['description'] ) ); ?>
				</div>
			</div>
		<?php
	}
	else {
		?>
			<option value="<?php echo esc_attr( $tkey );?>" <?php selected( ! empty( $default ) ); ?>><?php echo esc_html( wp_strip_all_tags( $template['name'] ) ); ?></option>
		<?php
	}
}

function nbtpl_render_theme_selection_scripts( $options ) {
	$type = $options['registration-templates-appearance'];
	$selected_color = $options['selected-background-color'];
	$unselected_color = $options['unselected-background-color'];
	$overlay_color = $options['overlay_color'];
	$screenshots_width = $options['screenshots_width'];

	wp_enqueue_script( 'nbt-template-selector', NBTPL_PLUGIN_URL . '/blogtemplatesfiles/assets/js/nbt-template-selector.js', array( 'jquery' ), NBTPL_PLUGIN_VERSION, true );
wp_enqueue_style( 'nbt-template-selector', NBTPL_PLUGIN_URL . '/blogtemplatesfiles/assets/css/nbt-template-selector.css', array(), NBTPL_PLUGIN_VERSION );
?>
		<style>
			.theme-previewer-wrap,
			.theme-page-showcase-wrap,
			.theme-screenshot-wrap {
				background:<?php echo esc_html( $unselected_color ); ?>;
			}
			.blog_template-default_item {
				background:<?php echo esc_html( $selected_color ); ?> !important;
			}
		</style>
	<?php
	if ( 'previewer' == $type ) {
		wp_enqueue_script( 'nbt-template-selector-previewer', NBTPL_PLUGIN_URL . '/blogtemplatesfiles/assets/js/nbt-template-selector-previewer.js', array( 'jquery' ), NBTPL_PLUGIN_VERSION, true );
wp_enqueue_style( 'nbt-template-selector-previewer', NBTPL_PLUGIN_URL . '/blogtemplatesfiles/assets/css/nbt-template-selector-previewer.css', array(), NBTPL_PLUGIN_VERSION );
}
	elseif ( 'page_showcase' == $type ) {
		wp_enqueue_script( 'nbt-template-selector-page_showcase', NBTPL_PLUGIN_URL . '/blogtemplatesfiles/assets/js/nbt-template-selector-page_showcase.js', array( 'jquery' ), NBTPL_PLUGIN_VERSION, true );
wp_enqueue_style( 'nbt-template-selector-page_showcase', NBTPL_PLUGIN_URL . '/blogtemplatesfiles/assets/css/nbt-template-selector-page_showcase.css', array(), NBTPL_PLUGIN_VERSION );
?>
			<style>
				.theme-page-showcase-wrap {
					background:<?php echo esc_html( $overlay_color ); ?>;
					width:<?php echo absint( $screenshots_width ); ?>px;
				}
			</style>
		<?php
	}
	elseif ( 'screenshot' === $type ) {
		wp_enqueue_script( 'nbt-template-selector-screenshot', NBTPL_PLUGIN_URL . '/blogtemplatesfiles/assets/js/nbt-template-selector-screenshot.js', array( 'jquery' ), NBTPL_PLUGIN_VERSION, true );
}
	elseif ( 'screenshot_plus' === $type ) {
		wp_enqueue_script( 'nbt-template-selector-screenshot_plus', NBTPL_PLUGIN_URL . '/blogtemplatesfiles/assets/js/nbt-template-selector-screenshot_plus.js', array( 'jquery' ), NBTPL_PLUGIN_VERSION, true );
wp_enqueue_style( 'nbt-template-selector-screenshot_plus', NBTPL_PLUGIN_URL . '/blogtemplatesfiles/assets/css/nbt-template-selector-screenshot_plus.css', array(), NBTPL_PLUGIN_VERSION );
?>
			<style>
				.theme-screenshot-plus-image-wrap {
					background:<?php echo esc_html( $unselected_color ); ?>;
				}
			</style>
		<?php
	}
	elseif ( 'description' === $type ) {
		wp_enqueue_style( 'nbt-template-selector-description', NBTPL_PLUGIN_URL . '/blogtemplatesfiles/assets/css/nbt-template-selector-description.css', array(), NBTPL_PLUGIN_VERSION );
}
}

register_activation_hook( __FILE__, 'nbtpl_activate_plugin' );
function nbtpl_activate_plugin() {
	$model = nbt_get_model();
	$model->create_tables();
	update_site_option( 'nbt_plugin_version', NBTPL_PLUGIN_VERSION );
}

// === Back-compat wrappers (deprecated) ===

/**
 * Back-compat wrapper for the pre-3.0.3 naming scheme.
 *
 * @deprecated 3.0.3 Use nbtpl_load_text_domain() instead.
 */
function nbt_load_text_domain(...$args) {
    _deprecated_function( __FUNCTION__, '3.0.3', 'nbtpl_load_text_domain' );
    return nbtpl_load_text_domain( ...$args );
}

/**
 * Back-compat wrapper for the pre-3.0.3 naming scheme.
 *
 * @deprecated 3.0.3 Use nbtpl_get_default_screenshot_url() instead.
 */
function nbt_get_default_screenshot_url(...$args) {
    _deprecated_function( __FUNCTION__, '3.0.3', 'nbtpl_get_default_screenshot_url' );
    return nbtpl_get_default_screenshot_url( ...$args );
}

/**
 * Back-compat wrapper for the pre-3.0.3 naming scheme.
 *
 * @deprecated 3.0.3 Use nbtpl_display_page_showcase() instead.
 */
function nbt_display_page_showcase(...$args) {
    _deprecated_function( __FUNCTION__, '3.0.3', 'nbtpl_display_page_showcase' );
    return nbtpl_display_page_showcase( ...$args );
}

/**
 * Back-compat wrapper for the pre-3.0.3 naming scheme.
 *
 * @deprecated 3.0.3 Use nbtpl_get_showcase_redirection_location() instead.
 */
function nbt_get_showcase_redirection_location(...$args) {
    _deprecated_function( __FUNCTION__, '3.0.3', 'nbtpl_get_showcase_redirection_location' );
    return nbtpl_get_showcase_redirection_location( ...$args );
}

/**
 * Back-compat wrapper for the pre-3.0.3 naming scheme.
 *
 * @deprecated 3.0.3 Use nbtpl_redirect_signup() instead.
 */
function nbt_redirect_signup(...$args) {
    _deprecated_function( __FUNCTION__, '3.0.3', 'nbtpl_redirect_signup' );
    return nbtpl_redirect_signup( ...$args );
}

/**
 * Back-compat wrapper for the pre-3.0.3 naming scheme.
 *
 * @deprecated 3.0.3 Use nbtpl_bp_redirect_signup_location() instead.
 */
function nbt_bp_redirect_signup_location(...$args) {
    _deprecated_function( __FUNCTION__, '3.0.3', 'nbtpl_bp_redirect_signup_location' );
    return nbtpl_bp_redirect_signup_location( ...$args );
}

/**
 * Back-compat wrapper for the pre-3.0.3 naming scheme.
 *
 * @deprecated 3.0.3 Use nbtpl_render_theme_selection_item() instead.
 */
function nbt_render_theme_selection_item(...$args) {
    _deprecated_function( __FUNCTION__, '3.0.3', 'nbtpl_render_theme_selection_item' );
    return nbtpl_render_theme_selection_item( ...$args );
}

/**
 * Back-compat wrapper for the pre-3.0.3 naming scheme.
 *
 * @deprecated 3.0.3 Use nbtpl_render_theme_selection_scripts() instead.
 */
function nbt_render_theme_selection_scripts(...$args) {
    _deprecated_function( __FUNCTION__, '3.0.3', 'nbtpl_render_theme_selection_scripts' );
    return nbtpl_render_theme_selection_scripts( ...$args );
}

/**
 * Back-compat wrapper for the pre-3.0.3 naming scheme.
 *
 * @deprecated 3.0.3 Use nbtpl_activate_plugin() instead.
 */
function nbt_activate_plugin(...$args) {
    _deprecated_function( __FUNCTION__, '3.0.3', 'nbtpl_activate_plugin' );
    return nbtpl_activate_plugin( ...$args );
}
