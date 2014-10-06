<?php
/**
 * Admin
 *
 * This file contains any functions related to the admin interface
 *
 * @package       gsl
 * @author        Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @copyright     Copyright (c) 2012, The College of Wooster
 * @license       http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version       SVN: $Id$
 * @since         1.0
 *
 */

/**
 * Register Settings
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_register_settings() {
    register_setting( GSL_SETTINGS_FIELD, GSL_SETTINGS_FIELD );
    add_option( GSL_SETTINGS_FIELD , gsl_option_defaults() );
    add_settings_section('gsl_staff','Staff Settings', 'gsl_section_text', GSL_SETTINGS_FIELD );
    add_settings_field('gsl_num_posts', 'Staff Per Page', 'gsl_num_posts_setting', GSL_SETTINGS_FIELD , 'gsl_staff');
    add_settings_field('gsl_staff_slug', 'Staff Info', 'gsl_staff_slug_setting', GSL_SETTINGS_FIELD , 'gsl_staff');
    add_settings_field('gsl_leadership_slug', 'Leadership Team Slugs', 'gsl_leadership_slug_setting', GSL_SETTINGS_FIELD , 'gsl_staff');
    add_settings_section('gsl_general','General Settings', 'gsl_general_section_text', GSL_SETTINGS_FIELD );
    add_settings_field('gsl_blog_cat', 'Blog Category', 'gsl_blog_cat_setting', GSL_SETTINGS_FIELD , 'gsl_general');
    add_settings_field('gsl_staff_page', 'Title of Staff Page', 'gsl_staff_page_setting', GSL_SETTINGS_FIELD , 'gsl_general');
}

/**
 * Set Options Defaults
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_option_defaults() {
        $arr = array(
        'gsl_staff_posts_per_page' => 4,
        'gsl_staff_role' => '',
        'gsl_staff_leadership_roles' => '',
        'gsl_staff_schedule' => '',
        'gsl_blog_cat' => 'blog',
        'gsl_staff_page' => 'staff'
    );
    return $arr;
}

/**
 * Options Description
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_section_text() {
    echo '<p>These options control various aspects of the display of content for the Genesis Staff Listing plugin.</p>';
}

/**
 * Staff Posts Per Page
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_num_posts_setting() {
    echo '<p>' . _e( 'Enter the number of staff you would like to display in staff listings.', 'gsl' ) . '</p>';
    echo "<input type='text' name='" . GSL_SETTINGS_FIELD . "[gsl_staff_posts_per_page]' size='10' value='". genesis_get_option( 'gsl_staff_posts_per_page', GSL_SETTINGS_FIELD ). "' />";
}

/**
 * Staff Related settings
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_staff_slug_setting() {
    echo '<p>' . _e( 'Enter the slug of the term used for staff. (Controls display of the Google spreadsheet for the staff schedule.)', 'gsl' ) . '</p>';
    echo "<input type='text' name='" . GSL_SETTINGS_FIELD . "[gsl_staff_role]' size='10' value='" . genesis_get_option( 'gsl_staff_role', GSL_SETTINGS_FIELD ) . "' /><br /><br />";
    echo '<p>' . _e( 'Enter the key for the Google spreadsheet of the staff schedule. The corresponding spreadsheet will be displayed on the staff page.', 'gsl' ) . '</p>';
    echo "<input type='text' name='" . GSL_SETTINGS_FIELD . "[gsl_staff_schedule]' size='50' value='" . genesis_get_option( 'gsl_staff_schedule', GSL_SETTINGS_FIELD ) . "' />";
}

/**
 * Leadership Slug(s)
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_leadership_slug_setting() {
    echo '<p>' . _e( 'Enter a comma separated list of the slugs used for the leadership team. (like manager, director, vice-president) This controls which staff are displayed on the Leadership page template.', 'gsl' ) . '</p>';
    echo "<input type='text' name='" . GSL_SETTINGS_FIELD . "[gsl_staff_leadership_roles]' size='50' value='" . genesis_get_option( 'gsl_staff_leadership_roles', GSL_SETTINGS_FIELD ) . "' />";
}

/**
 * Blog Category
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_blog_cat_setting() {
    echo '<p>' . _e( 'Enter the name or slug used for the blog category.', 'gsl' ) . '</p>';
    echo "<input type='text' name='" . GSL_SETTINGS_FIELD . "[gsl_blog_cat]' size='20' value='" . genesis_get_option( 'gsl_blog_cat', GSL_SETTINGS_FIELD ) . "' />";
}

/**
 * Staff Page
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_staff_page_setting() {
    echo '<p>' . _e( 'Enter the name or slug used for the Staff page.', 'gsl' ) . '</p>';
    echo "<input type='text' name='" . GSL_SETTINGS_FIELD . "[gsl_staff_page]' size='20' value='" . genesis_get_option( 'gsl_staff_page', GSL_SETTINGS_FIELD ) . "' />";
}

/**
 * Reset
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_reset() {
    if ( ! isset( $_REQUEST['page'] ) || 'gsl-settings' != $_REQUEST['page'] )
        return;

    if ( genesis_get_option( 'reset', GSL_SETTINGS_FIELD ) ) {
        update_option( GSL_SETTINGS_FIELD, gsl_option_defaults() );
        wp_redirect( admin_url( 'admin.php?page=gsl-settings&reset=true' ) );
        exit;
    }
}

/**
 * Admin Notices
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_notices() {
    if ( ! isset( $_REQUEST['page'] ) || 'gsl-settings' != $_REQUEST['page'] )
        return;

    if ( isset( $_REQUEST['reset'] ) && 'true' == $_REQUEST['reset'] ) {
        echo '<div id="message" class="updated"><p><strong>' . __( 'Bucknell Genesis Staff Listing Settings Reset', 'gsl' ) . '</strong></p></div>';
    }
    elseif ( isset( $_REQUEST['settings-updated'] ) && 'true' == $_REQUEST['settings-updated'] ) {  
        echo '<div id="message" class="updated"><p><strong>' . __( 'Bucknell Genesis Staff Listing Settings Saved', 'gsl' ) . '</strong></p></div>';
    }
}

/**
 * Add Options Menu
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_add_menu() {
    add_submenu_page('genesis', __('Genesis Staff Listing Settings','gsl'), __('Genesis Staff Listing Settings','gsl'), 'manage_options', 'gsl-settings', 'gsl_admin_page' );
}

/**
 * Theme Options Page
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_admin_page() { ?>
    
    <div class="wrap">
        <?php screen_icon( 'options-general' ); ?>  
        <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
        
        <form method="post" action="options.php">
        <?php settings_fields( GSL_SETTINGS_FIELD ); // important! ?>
        <?php do_settings_sections( GSL_SETTINGS_FIELD ); ?>
        
            <div class="bottom-buttons">
                <input type="submit" class="button-primary" value="<?php _e('Save Settings', 'genesis') ?>" />
                <input type="submit" class="button-highlighted" name="<?php echo GSL_SETTINGS_FIELD; ?>[reset]" value="<?php _e('Reset Settings', 'genesis'); ?>" />
            </div>
            
        </form>
    </div>
    
<?php }

/**
 * Sanitize Options
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_staff_sanitization_filters() {
    genesis_add_option_filter( 'no_html', GSL_SETTINGS_FIELD, array( 'gsl_staff_posts_per_page' ) );
    genesis_add_option_filter( 'no_html', GSL_SETTINGS_FIELD, array( 'gsl_staff_role' ) );
    genesis_add_option_filter( 'no_html', GSL_SETTINGS_FIELD, array( 'gsl_staff_schedule' ) );
    genesis_add_option_filter( 'no_html', GSL_SETTINGS_FIELD, array( 'gsl_staff_leadership_roles' ) );
    genesis_add_option_filter( 'no_html', GSL_SETTINGS_FIELD, array( 'gsl_blog_cat' ) );
    genesis_add_option_filter( 'no_html', GSL_SETTINGS_FIELD, array( 'gsl_staff_page' ) );
}

/**
 * Admin Header Callback
 *
 * Register a custom admin callback to display the custom header preview with
 * the same style as is shown on the front end.
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_admin_style() {
    $headimg = sprintf( '.appearance_page_custom-header #headimg { background: url(%s) no-repeat; font-family: Shanti, arial, serif; min-height: %spx; }', get_header_image(), HEADER_IMAGE_HEIGHT );
    $h1 = sprintf( '#headimg h1, #headimg h1 a { color: #%s; font-family: Shanti, arial, serif; font-size: 48px; font-weight: normal; line-height: 48px; margin: 10px 0 0; text-align: center; text-decoration: none; text-shadow: #fff 1px 1px; }', esc_html( get_header_textcolor() ) );
    $desc = sprintf( '#headimg #desc { color: #%s; font-family: Arial, Helvetica, Tahoma, sans-serif; font-size: 14px; font-style: italic; }', esc_html( get_header_textcolor() ) );

    printf( '<style type="text/css">%1$s %2$s %3$s</style>', $headimg, $h1, $desc );

}