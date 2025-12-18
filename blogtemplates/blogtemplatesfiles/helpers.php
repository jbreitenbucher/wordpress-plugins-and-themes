<?php

function nbtpl_get_model() {
	return blog_templates_model::get_instance();
}

function nbtpl_get_settings_handler() {
	return NBT_Plugin_Settings_Handler::get_instance();
}

function nbtpl_get_settings() {
	$handler = nbtpl_get_settings_handler();
	return $handler->get_settings();
}

function nbtpl_update_settings( $new_settings ) {
	$handler = nbtpl_get_settings_handler();
	$handler->update_settings( $new_settings );
}

function nbtpl_get_default_settings() {
	$handler = nbtpl_get_settings_handler();
	return $handler->get_default_settings();
}

function nbtpl_theme_selection_toolbar( $templates ) {
    require_once( NBTPL_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates_theme_selection_toolbar.php' );
	$settings = nbtpl_get_settings();
	$toolbar = new Blog_Templates_Theme_Selection_Toolbar( $settings['registration-templates-appearance'] );
	$toolbar->display();
	$category_id = $toolbar->default_category_id;

	if ( $category_id !== 0 ) {
		$model = nbtpl_get_model();
		$templates = $model->get_templates_by_category( $category_id );
	}

	return $templates;
}

function nbtpl_get_template_selection_types() {
	$types = array(
		0 => __( 'As simple selection box', 'blogtemplates' ),
		'description' => __( 'As radio-box selection with descriptions', 'blogtemplates' ),
		'screenshot' => __( 'As theme screenshot selection', 'blogtemplates' ),
		'screenshot_plus' => __( 'As theme screenshot selection with titles and description', 'blogtemplates' ),
		'previewer' => __( 'As a theme previewer', 'blogtemplates' ),
		'page_showcase' => __( 'As a showcase inside a page', 'blogtemplates' ),
	);

	// Back-compat: run legacy filter name, then the new one.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	$types = apply_filters( 'nbt_get_template_selection_types', $types );
	$types = apply_filters( 'nbtpl_get_template_selection_types', $types );

	return $types;
}

/**
 * Get a list of WordPress DB tables in a blog (not the default ones)
 *
 * @param Integer $blog_id
 * @return Array of tables attributes:
 		Array(
			'name' => Table name
			'prefix.name' => Table name and Database if MultiDB is activated. Same than 'name' in other case.
 		)
 */
function nbtpl_get_additional_tables( $blog_id ) {
	global $wpdb;

	$blog_id = absint( $blog_id );
	$blog_details = get_blog_details( $blog_id );

	if ( ! $blog_details )
		return array();


	switch_to_blog( $blog_id );

	// MultiDB Plugin hack: build a LIKE-safe prefix when not using m_wpdb.
	if ( class_exists( 'm_wpdb' ) ) {
		$pfx = $wpdb->prefix;
	} else {
		$pfx = $wpdb->esc_like( $wpdb->prefix );
	}
	// Get all the tables for that blog (cached); SHOW TABLES has no core API equivalent.
	$cache_group = 'blogtemplates';
	$cache_key   = 'nbt_show_tables_' . $blog_id . '_' . md5( (string) $pfx );
	$results     = wp_cache_get( $cache_key, $cache_group );
	if ( false === $results ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLES LIKE %s', "{$pfx}%" ), ARRAY_N );
		wp_cache_set( $cache_key, $results, $cache_group, 300 );
	}
    $default_tables = array( 'posts', 'comments', 'links', 'options', 'postmeta', 'terms', 'term_taxonomy', 'termmeta', 'term_relationships', 'commentmeta' );

    $tables = array();
    if ( ! empty( $results ) ) {
    	foreach ( $results as $result ) {
    		if ( ! in_array( str_replace( $wpdb->prefix, '', $result['0'] ), $default_tables ) ) {
    			if ( class_exists( 'm_wpdb' ) ) {
    				// MultiDB Plugin
				// MultiDB Plugin: analyze_query() only inspects the SQL to route to the correct dataset and does not execute it directly.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$db = $wpdb->analyze_query( $wpdb->prepare( 'SHOW TABLES LIKE %s', "{$pfx}%" ) );
                    $dataset = $db['dataset'];
                    $current_db = '';

                    foreach ( $wpdb->dbh_connections as $connection ) {
                    	if ( $connection['ds'] == $dataset ) {
                    		$current_db = $connection['name'];
                    		break;
                    	}
                    }

                    $val = $current_db . '.' . $result[0];

                } else {
                    $val =  $result[0];
                }

                if ( stripslashes_deep( $pfx ) == $wpdb->base_prefix ) {
                    // If we are on the main blog, we'll have to avoid those tables from other blogs
                    $pattern = '/^' . stripslashes_deep( $pfx ) . '[0-9]/';
                    if ( preg_match( $pattern, $result[0] ) )
                        continue;
                }

                $tables[] = array(
                	'name' => $result[0] ,
                	'prefix.name' => $val
                );
    		}
    	}
    }

    restore_current_blog();

    return $tables;
    // End changed
}

/*
 * Backward-compat wrappers for legacy 3-char `nbt_` / unprefixed helper functions.
 * WPCS now enforces a 4+ character project prefix; keep these for older integrations.
 */

if ( ! function_exists( 'nbt_get_model' ) ) {
	/**
	 * @deprecated 3.0.3 Use nbtpl_get_model()
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function nbt_get_model() {
		return nbtpl_get_model();
	}
}

if ( ! function_exists( 'get_settings_handler' ) ) {
	/**
	 * @deprecated 3.0.3 Use nbtpl_get_settings_handler()
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function get_settings_handler() {
		return nbtpl_get_settings_handler();
	}
}

if ( ! function_exists( 'nbt_get_settings' ) ) {
	/**
	 * @deprecated 3.0.3 Use nbtpl_get_settings()
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function nbt_get_settings() {
		return nbtpl_get_settings();
	}
}

if ( ! function_exists( 'nbt_update_settings' ) ) {
	/**
	 * @deprecated 3.0.3 Use nbtpl_update_settings()
	 *
	 * @param array $new_settings Settings array.
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function nbt_update_settings( $new_settings ) {
		nbtpl_update_settings( $new_settings );
	}
}

if ( ! function_exists( 'nbt_get_default_settings' ) ) {
	/**
	 * @deprecated 3.0.3 Use nbtpl_get_default_settings()
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function nbt_get_default_settings() {
		return nbtpl_get_default_settings();
	}
}

if ( ! function_exists( 'nbt_theme_selection_toolbar' ) ) {
	/**
	 * @deprecated 3.0.3 Use nbtpl_theme_selection_toolbar()
	 *
	 * @param array $templates Templates array.
	 * @return array
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function nbt_theme_selection_toolbar( $templates ) {
		return nbtpl_theme_selection_toolbar( $templates );
	}
}

if ( ! function_exists( 'nbt_get_template_selection_types' ) ) {
	/**
	 * @deprecated 3.0.3 Use nbtpl_get_template_selection_types()
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function nbt_get_template_selection_types() {
		return nbtpl_get_template_selection_types();
	}
}

if ( ! function_exists( 'nbt_get_additional_tables' ) ) {
	/**
	 * @deprecated 3.0.3 Use nbtpl_get_additional_tables()
	 *
	 * @param int $blog_id Blog ID.
	 * @return array
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function nbt_get_additional_tables( $blog_id ) {
		return nbtpl_get_additional_tables( $blog_id );
	}
}
