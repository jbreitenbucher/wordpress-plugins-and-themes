<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Upgrade routines run rarely and must operate on custom tables; wpdb is required. Caching is not meaningful during upgrades.


function blog_templates_upgrade_19() {

	global $wpdb;

	$options = get_site_option( 'blog_templates_options', array( 'templates' => array() ) );
	$default = isset( $options['default'] ) ? absint( $options['default'] ) : false;


	foreach ( $options['templates'] as $key => $template ) {

		$tmp_template = $template;

		$blog_id = $tmp_template['blog_id'];
		unset( $tmp_template['blog_id'] );
		$name = $tmp_template['name'];
		unset( $tmp_template['name'] );
		$description = $tmp_template['description'];
		unset( $tmp_template['description'] );

		$tmp_template['screenshot'] = false;

		// Inserting templates
		$wpdb->insert(
			$wpdb->base_prefix . 'nbt_templates',
			array(
				'blog_id' =>  $blog_id,
				'name' => $name,
				'description' => $description,
				'options' => maybe_serialize( $tmp_template )
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s'
			)
		);

		$template_id = $wpdb->insert_id;

		if ( $default === $key ) {
			$wpdb->update(
				$wpdb->base_prefix . 'nbt_templates',
				array( 'is_default' => 1 ),
				array( 'ID' => $template_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

	}

	$new_options = array();
	$new_options['show-registration-templates'] = isset( $options['show-registration-templates'] ) ? $options['show-registration-templates'] : false;
	$new_options['registration-templates-appearance'] = isset( $options['registration-templates-appearance'] ) ? $options['registration-templates-appearance'] : '';
	$new_options['previewer_button_text'] = isset( $options['previewer_button_text'] ) ? $options['previewer_button_text'] : __( 'Select this theme', 'blogtemplates' );			  			  	 
	$new_options['toolbar-color'] = isset( $options['toolbar-color'] ) ? $options['toolbar-color'] : '#8B8B8B';
	$new_options['toolbar-text-color'] = isset( $options['toolbar-text-color'] ) ? $options['toolbar-text-color'] : '#FFFFFF';
	$new_options['toolbar-border-color'] = isset( $options['toolbar-border-color'] ) ? $options['toolbar-border-color'] : '#333333';
	$new_options['show-categories-selection'] = isset($options['show-categories-selection']) ? $options['show-categories-selection'] : 0;

	update_site_option( 'blog_templates_options', $new_options );
}

function blog_templates_upgrade_191() {
	global $wpdb;

	$templates = $wpdb->get_results( "SELECT * FROM " . $wpdb->base_prefix . 'nbt_templates', ARRAY_A );

	if ( ! empty( $templates ) ) {
		$final_results = array();
		foreach ( $templates as $template ) {
			$final_results[$template['ID']] = $template;
			$final_results[$template['ID']]['options'] = maybe_unserialize( $template['options'] );
		}
		$templates = $final_results;
	}
	else {
		$templates = array();
	}

    foreach ( $templates as $key => $template ) {
        $options = $template['options'];
        $options['pages_ids'] = array( 'all-pages');
        $wpdb->update(
        	$wpdb->base_prefix . 'nbt_templates',
        	array( 'options' => maybe_serialize( $options ) ),
        	array( 'ID' => $template['ID'] ),
        	array( '%s' ),
        	array( '%d' )
        );
    }
}

function blog_templates_upgrade_20() {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		// Reseting categories as it has been never used

		$templates_table = $wpdb->base_prefix . 'nbt_templates';
		$categories_table = $wpdb->base_prefix . 'nbt_templates_categories';
		$categories_relationships_table = $wpdb->base_prefix . 'nbt_categories_relationships_table';

		$wpdb->query( "DELETE FROM " . esc_sql( $categories_table ) );
		$wpdb->query( "DELETE FROM " . esc_sql( $categories_relationships_table ) );

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$default_cat = $wpdb->get_row( "SELECT * FROM " . esc_sql( $categories_table ) . " WHERE is_default = 1" );

		if ( ! empty( $default_cat ) ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( "UPDATE $categories_table SET is_default = 0 WHERE is_default = 1 AND ID != $default_cat->ID" );
		}

		// Adding default category
		if ( empty( $default_cat ) ) {
			$wpdb->insert(
				$categories_table,
				array(
					'name' => __( 'Default category', 'blogtemplates' ),
					'description' => '',
					'is_default' => 1
				),
				array(
					'%s',
					'%s',
					'%d'
				)
			);

		}


		// check_for_uncategorized_templates
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
				$uncategorized_templates = $wpdb->get_results(
// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- %i is valid since WP 6.2; plugin requires WP 6.2+.
			$wpdb->prepare(
				"SELECT t.ID
				FROM %i t
				LEFT OUTER JOIN %i ct ON ct.template_id = t.ID
				WHERE ct.cat_id IS NULL",
				$templates_table,
				$categories_relationships_table
			)
		);
if ( ! empty( $uncategorized_templates ) ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- %i is valid since WP 6.2; plugin requires WP 6.2+.
	$default_cat_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM %i WHERE is_default = 1", $categories_table ) );
if ( ! empty( $default_cat_id ) ) {
			foreach ( $uncategorized_templates as $template ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- %i is valid since WP 6.2; plugin requires WP 6.2+.
					$wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE template_id = %d", $categories_relationships_table, (int) $template->ID ) );
					$cats = array( $default_cat_id );
					foreach ( $cats as $cat ) {
                        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
                        $wpdb->query(
// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- %i is valid since WP 6.2; plugin requires WP 6.2+.
                            $wpdb->prepare( "INSERT INTO %i (cat_id,template_id) VALUES (%d,%d)", $categories_relationships_table, (int) $cat, (int) $template->ID )
                        );
					}
				}
			}
		}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		
	$templates = $wpdb->get_results(

// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- %i is valid since WP 6.2; plugin requires WP 6.2+.
		$wpdb->prepare(

			"SELECT r.cat_id, COUNT(t.ID) AS the_count

			FROM %i t

			JOIN %i r ON r.template_id = t.ID

			GROUP BY r.cat_id",

			$templates_table,

			$categories_relationships_table

		)

	);
if ( ! empty( $templates ) ) {
			foreach ( $templates as $template ) {
				$wpdb->update(
					$categories_table,
					array( 'templates_count' => $template->the_count ),
					array( 'ID' => $template->cat_id ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}

}

function blog_templates_upgrade_22() {
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$templates_table = $wpdb->base_prefix . 'nbt_templates';
	if ( ! empty($wpdb->charset) )
		$db_charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty($wpdb->collate) )
		$db_charset_collate .= " COLLATE $wpdb->collate";

	$sql = "CREATE TABLE $templates_table (
		ID bigint(20) NOT NULL AUTO_INCREMENT,
		blog_id bigint(20) NOT NULL,
		name varchar(255) NOT NULL,
		description mediumtext,
		is_default int(1) DEFAULT 0,
		options longtext NOT NULL,
		network_id bigint(20) NOT NULL DEFAULT 1,
		PRIMARY KEY  (ID)
	      ) $db_charset_collate;";

	dbDelta($sql);

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
	$results = $wpdb->get_results( "SELECT * FROM $templates_table" );

	if ( ! empty( $results ) ) {
		foreach( $results as $template ) {
			$blog_id = $template->blog_id;
			$blog_details = get_blog_details( $blog_id );

			if ( ! empty( $blog_details ) ) {
				$network_id = $blog_details->site_id;
				$wpdb->update(
					$templates_table,
					array( 'network_id' => $network_id ),
					array( 'ID' => $template->ID ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}
	}

	$settings = nbt_get_settings();

	if ( empty( $settings['templates'] ) ) {
		$settings['default'] = '';
	}
	else {
		foreach ( $settings['templates'] as $template ) {
			if ( $template['is_default'] ) {
				$settings['default'] = $template['ID'];
				break;
			}
		}
	}

	nbt_update_settings( $settings );
}

function blog_templates_upgrade_262() {
	global $wpdb;

	$templates_table = $wpdb->base_prefix . 'nbt_templates';

	// remove all main sites. This is a solution for Edublogs/CampusPress more than WPMUDEV, but just in case :)
	$main_blog_id = BLOG_ID_CURRENT_SITE;
	$wpdb->query( $wpdb->prepare( "DELETE FROM " . esc_sql( $templates_table ) . " WHERE blog_id = %d", $main_blog_id ) );

}
