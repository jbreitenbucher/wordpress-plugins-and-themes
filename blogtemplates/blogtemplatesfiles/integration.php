<?php

// Other plugins integrations

include_once( 'integration/gravity-forms.php' );

function nbtpl_add_membership_caps( $user_id, $blog_id ) {
	switch_to_blog( $blog_id );
	$user = get_userdata( $user_id );
	$user->add_cap('membershipadmin');
	$user->add_cap('membershipadmindashboard');
	$user->add_cap('membershipadminmembers');
	$user->add_cap('membershipadminlevels');
	$user->add_cap('membershipadminsubscriptions');
	$user->add_cap('membershipadmincoupons');
	$user->add_cap('membershipadminpurchases');
	$user->add_cap('membershipadmincommunications');
	$user->add_cap('membershipadmingroups');
	$user->add_cap('membershipadminpings');
	$user->add_cap('membershipadmingateways');
	$user->add_cap('membershipadminoptions');
	$user->add_cap('membershipadminupdatepermissions');
	update_user_meta( $user_id, 'membership_permissions_updated', 'yes');
	restore_current_blog();
}

function nbtpl_bp_add_register_scripts() {
	?>
	<script>
		jQuery(document).ready(function($) {
			var bt_selector = $('#blog_template-selection').remove();
			bt_selector.appendTo( $('#blog-details') );
		});
	</script>
	<?php
}

add_action( 'plugins_loaded', 'nbtpl_appplus_unregister_action' );
function nbtpl_appplus_unregister_action() {
	if ( class_exists('Appointments' ) ) {
		global $appointments;
		remove_action( 'wpmu_new_blog', array( $appointments, 'new_blog' ), 10, 6 );
	}
}


// Framemarket theme
add_filter( 'framemarket_list_shops', 'nbtpl_framemarket_list_shops' );
function nbtpl_framemarket_list_shops( $blogs ) {
	$return = array();

	if ( ! empty( $blogs ) ) {
		$model = nbt_get_model();
		foreach ( $blogs as $blog ) {
			if ( ! $model->is_template( $blog->blog_id ) )
				$return[] = $blog;
		}
	}

	return $return;
}

add_filter( 'blogs_directory_blogs_list', 'nbtpl_remove_blogs_from_directory' );
function nbtpl_remove_blogs_from_directory( $blogs ) {
	$model = nbt_get_model();
	$new_blogs = array();
	foreach ( $blogs as $blog ) {
		if ( ! $model->is_template( $blog['blog_id'] ) )
			$new_blogs[] = $blog;
	}
	return $new_blogs;
}

/** AUTOBLOG **/
add_action( 'blog_templates-copy-options', 'nbtpl_copy_autoblog_feeds' );
function nbtpl_copy_autoblog_feeds( $template ) {
	global $wpdb;

	// Site ID, blog ID...
	$current_site = get_current_site();
	$current_site_id = absint( $current_site->id );

	if ( ! isset( $template['blog_id'] ) )
		return;

	$source_blog_id = absint( $template['blog_id'] );
	$autoblog_on = false;

	switch_to_blog( $source_blog_id );
	// Is Autoblog activated?
	if ( ! function_exists( 'is_plugin_active' ) )
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if ( is_plugin_active( 'autoblog/autoblogpremium.php' ) )
		$autoblog_on = true;

	// We'll need this values later
	$source_url = get_site_url( $source_blog_id );
	$source_url_ssl = get_site_url( $source_blog_id, '', 'https' );

	restore_current_blog();

	if ( ! $autoblog_on )
		return;

	// Getting all the feed data for the source blog ID
	$autoblog_table = $wpdb->base_prefix . 'autoblog';

	// Cache the source feed rows to avoid repeated scans during a single copy operation.
	$cache_key = 'nbt_autoblog_feeds_' . $current_site_id . '_' . $source_blog_id;
	$results   = wp_cache_get( $cache_key, 'blogtemplates' );
	if ( false === $results ) {
		// $autoblog_table is derived from $wpdb->base_prefix and a fixed suffix; no user input is involved.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table; no core API exists.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- %i is supported since WP 6.2; plugin requires 6.2+.
				'SELECT * FROM %i WHERE blog_id = %d AND site_id = %d',
				$autoblog_table,
				$source_blog_id,
				$current_site_id
			)
		);
		wp_cache_set( $cache_key, $results, 'blogtemplates', 5 * MINUTE_IN_SECONDS );
	}

	if ( ! empty( $results ) ) {
		$current_blog_id = get_current_blog_id();

		$current_url = get_site_url( $current_blog_id );
		$current_url_ssl = get_site_url( $current_blog_id, '', 'https' );

		foreach ( $results as $row ) {
			// Getting the feed metadata
			$feed_meta = maybe_unserialize( $row->feed_meta );

			// We need to replace the source blog URL for the new one
			$feed_meta = str_replace( $source_url, $current_url, $feed_meta );
			$feed_meta = str_replace( $source_url_ssl, $current_url_ssl, $feed_meta );
			$feed_meta['blog'] = $current_blog_id;

			$row->feed_meta = maybe_serialize( $feed_meta );

			// Inserting feed for the new blog
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table write required.
			$wpdb->insert(
				$autoblog_table,
				array(
					'site_id' => $current_site_id,
					'blog_id' => $current_blog_id,
					'feed_meta' => $row->feed_meta,
					'active' => $row->active,
					'nextcheck' => $row->nextcheck,
					'lastupdated' => $row->lastupdated
				),
				array( '%d', '%d', '%s', '%d', '%d', '%d' )
			);
		}
	}

}

/** EASY GOOGLE FONTS **/
add_action( 'blog_templates-copy-after_copying', 'nbtpl_copy_easy_google_fonts_controls', 10, 2 );
function nbtpl_copy_easy_google_fonts_controls( $template, $destination_blog_id ) {
	global $wpdb;

	if ( ! is_plugin_active( 'easy-google-fonts/easy-google-fonts.php' ) )
		return;

	$source_blog_id = $template['blog_id'];

	if ( ! isset( $template['to_copy']['posts'] ) && get_blog_details( $source_blog_id ) && get_blog_details( $destination_blog_id ) ) {
		switch_to_blog( $source_blog_id );

		$posts_cache_key = 'nbt_egf_posts_' . $source_blog_id;
		$posts_results   = wp_cache_get( $posts_cache_key, 'blogtemplates' );
		if ( false === $posts_results ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Bulk read needed for template copy.
			$posts_results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t1.* FROM {$wpdb->posts} t1 WHERE t1.post_type = %s",
					'tt_font_control'
				)
			);
			wp_cache_set( $posts_cache_key, $posts_results, 'blogtemplates', 5 * MINUTE_IN_SECONDS );
		}

		$postmeta_cache_key = 'nbt_egf_postmeta_' . $source_blog_id;
		$postmeta_results   = wp_cache_get( $postmeta_cache_key, 'blogtemplates' );
		if ( false === $postmeta_results ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Bulk read needed for template copy.
			$postmeta_results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t1.* FROM {$wpdb->postmeta} t1 INNER JOIN {$wpdb->posts} t2 ON t1.post_id = t2.ID WHERE t2.post_type = %s",
					'tt_font_control'
				)
			);
			wp_cache_set( $postmeta_cache_key, $postmeta_results, 'blogtemplates', 5 * MINUTE_IN_SECONDS );
		}

		restore_current_blog();

		switch_to_blog( $destination_blog_id );
		foreach ( $posts_results as $row ) {
			$row = (array) $row;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Insert into core tables required to preserve data during template copy.
			$wpdb->insert( $wpdb->posts, $row );
		}

		foreach ( $postmeta_results as $row ) {
			$row = (array) $row;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Insert into core tables required to preserve data during template copy.
			$wpdb->insert( $wpdb->postmeta, $row );
		}

		restore_current_blog();
	}
}



/** WORDPRESS HTTPS **/
add_action( 'blog_templates-copy-options', 'nbtpl_hooks_set_https_settings' );
function nbtpl_hooks_set_https_settings( $template ) {
	if ( ! function_exists( 'is_plugin_active' ) )
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if ( is_plugin_active( 'wordpress-https/wordpress-https.php' ) ) {
		if ( get_option( 'wordpress-https_ssl_admin' ) )
			update_option( 'wordpress-https_ssl_host', trailingslashit( get_site_url( get_current_blog_id(), '', 'https' ) ) );
		else
			update_option( 'wordpress-https_ssl_host', trailingslashit( get_site_url( get_current_blog_id(), '', 'http' ) ) );
	}

}

/** WOOCOMMERCE */

add_filter( 'nbt_copy_files_skip_list', 'nbtpl_woo_copy_files_skip_list', 10, 2 );
function nbtpl_woo_copy_files_skip_list( $skip_list, $dir_to_copy ) {
	if ( is_file( $dir_to_copy . '/woocommerce_uploads/.htaccess' ) )
		$skip_list[] = 'woocommerce_uploads/.htaccess';

	return $skip_list;
}

add_action( "blog_templates-copy-after_copying", 'nbtpl_woo_after_copy' );
function nbtpl_woo_after_copy() {

	if ( is_file( WP_CONTENT_DIR . '/plugins/woocommerce/includes/admin/class-wc-admin-settings.php' ) )
		include_once( WP_CONTENT_DIR . '/plugins/woocommerce/includes/admin/class-wc-admin-settings.php' );

	if ( class_exists( 'WC_Admin_Settings' ) )
		WC_Admin_Settings::check_download_folder_protection();
}

/**
 * UPFRONT
 */
add_action( "blog_templates-copy-after_copying", 'nbtpl_upfront_copy_options', 10, 2 );
function nbtpl_upfront_copy_options( $template, $destination_blog_id ) {
	global $wpdb;

	$source_blog_id = absint( $template['blog_id'] );

	switch_to_blog( $destination_blog_id );
	$theme_name = wp_get_theme();
	restore_current_blog();

	if ( $theme_name->Template === 'upfront' ) {
		$source_url = get_site_url( $source_blog_id );
		$destination_url = get_site_url( $destination_blog_id );
		$source_url = preg_replace( '/^https?\:\/\//', '', $source_url );
		$destination_url = preg_replace( '/^https?\:\/\//', '', $destination_url );


			$theme_name_value = (string) $theme_name;
			$theme_name_like  = $wpdb->esc_like( $theme_name_value ) . '%';

			$cache_key = 'nbt_upfront_option_names_' . get_current_blog_id() . '_' . md5( $theme_name_like );
			$results    = wp_cache_get( $cache_key, 'blogtemplates' );
			if ( false === $results ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Options prefix search has no core API equivalent.
				$results = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
						$theme_name_like
					)
				);
				wp_cache_set( $cache_key, $results, 'blogtemplates', 5 * MINUTE_IN_SECONDS );
			}

		foreach ( $results as $option_name ) {
			$json_value = get_option( $option_name );
			if ( ! is_string( $json_value ) )
				continue;

			$value = json_decode( $json_value );


			if ( is_object( $value ) || is_array( $value ) ) {
				$json_value = str_replace( $source_url, $destination_url, $json_value );
				update_option( $option_name, $json_value );
			}
		}
	}
}


//POPUP PRO
add_filter( 'nbt_copier_settings', 'nbtpl_popover_template_settings', 10, 3 );
function nbtpl_popover_template_settings( $settings, $src_blog_id, $new_blog_id ) {
	global $wpdb;

	switch_to_blog( $src_blog_id );
	if ( ! function_exists( 'is_plugin_active' ) )
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if ( in_array( 'settings', $settings['to_copy'] ) && is_plugin_active( 'popover/popover.php' ) ) {
		if ( ! in_array( $wpdb->prefix . 'popover_ip_cache', $settings['additional_tables'] ) )
			$settings['additional_tables'][] = $wpdb->prefix . 'popover_ip_cache';
	}

	restore_current_blog();
	return $settings;
}
//add_action( 'blog_templates-copy-after_copying', 'nbtpl_popover_copy_settings', 10, 2 );
function nbtpl_popover_copy_settings( $template, $new_blog_id ) {
	if ( in_array( 'settings', $template['to_copy'] ) ) {
		$popup_options = get_blog_option( $template['blog_id'], 'inc_popup-config' );
		if ( ! $popup_options )
			return;

		update_option( 'inc_popup-config', $popup_options );
	}
}


/**
 * PRO SITES
 */
add_filter( 'psts_setting_checkout_url', 'nbtpl_pro_sites_checkout_url_setting' );
function nbtpl_pro_sites_checkout_url_setting( $value ) {
	global $pagenow, $psts;

	if ( ! is_object( $psts ) )
		return $value;

	$show_signup = $psts->get_setting( 'show_signup' );

	$blog_template = '';
	if ( isset( $_REQUEST['blog_template'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$blog_template = sanitize_text_field( wp_unslash( $_REQUEST['blog_template'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( ! is_admin() && 'wp-signup.php' == $pagenow && $show_signup && '' !== $blog_template ) {
		$value = add_query_arg( 'blog_template', $blog_template, $value );
	}

	return $value;
}


add_filter( 'psts_redirect_signup_page_url', 'nbtpl_pro_sites_checkout_url' );
function nbtpl_pro_sites_checkout_url( $url ) {
	$blog_template = '';
	if ( isset( $_REQUEST['blog_template'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$blog_template = sanitize_text_field( wp_unslash( $_REQUEST['blog_template'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( '' !== $blog_template ) {
		$url = add_query_arg( 'blog_template', $blog_template, $url );
	}

	return $url;
}


/**
 * Deprecated wrappers (Phase 3 naming: nbt_* -> nbtpl_*).
 * These are intentionally retained for backward compatibility.
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_add_membership_caps( ...$args ) {
	return nbtpl_add_membership_caps( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_bp_add_register_scripts( ...$args ) {
	return nbtpl_bp_add_register_scripts( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_appplus_unregister_action( ...$args ) {
	return nbtpl_appplus_unregister_action( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_framemarket_list_shops( ...$args ) {
	return nbtpl_framemarket_list_shops( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_remove_blogs_from_directory( ...$args ) {
	return nbtpl_remove_blogs_from_directory( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_copy_autoblog_feeds( ...$args ) {
	return nbtpl_copy_autoblog_feeds( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_copy_easy_google_fonts_controls( ...$args ) {
	return nbtpl_copy_easy_google_fonts_controls( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_hooks_set_https_settings( ...$args ) {
	return nbtpl_hooks_set_https_settings( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_woo_copy_files_skip_list( ...$args ) {
	return nbtpl_woo_copy_files_skip_list( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_woo_after_copy( ...$args ) {
	return nbtpl_woo_after_copy( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_upfront_copy_options( ...$args ) {
	return nbtpl_upfront_copy_options( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_popover_template_settings( ...$args ) {
	return nbtpl_popover_template_settings( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_popover_copy_settings( ...$args ) {
	return nbtpl_popover_copy_settings( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_pro_sites_checkout_url_setting( ...$args ) {
	return nbtpl_pro_sites_checkout_url_setting( ...$args );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function nbt_pro_sites_checkout_url( ...$args ) {
	return nbtpl_pro_sites_checkout_url( ...$args );
}
