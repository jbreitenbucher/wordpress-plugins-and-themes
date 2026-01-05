<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
// The template copier performs one-off bulk operations during site cloning; caching offers no practical benefit
// and core APIs do not cover all required operations (custom tables, bulk inserts, and transactional steps).
#[\AllowDynamicProperties]
class NBTPL_Template_Copier {

    protected $settings;
    protected $template_blog_id;
    protected $new_blog_id;
    protected $user_id;

    public function __construct( $src_blog_id, $new_blog_id, $user_id, $args ) {
        $defaults = $this->get_default_args();
        $args['to_copy'] = wp_parse_args( $args['to_copy'], $defaults['to_copy'] );
        $this->settings = apply_filters( 'nbtpl_copier_settings', wp_parse_args( $args, $defaults ), $src_blog_id, $new_blog_id, $user_id );

        if ( function_exists( 'apply_filters_deprecated' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $this->settings = apply_filters_deprecated( 'nbt_copier_settings', array( $this->settings, $src_blog_id, $new_blog_id, $user_id ), '3.0.3', 'nbtpl_copier_settings' );
        } else {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $this->settings = apply_filters( 'nbt_copier_settings', $this->settings, $src_blog_id, $new_blog_id, $user_id );
        }

        $this->template_blog_id = $src_blog_id;
        $this->new_blog_id = $new_blog_id;
        $this->user_id = $user_id;

        $model = nbt_get_model();
        $this->template = $model->get_template( $this->settings['template_id'] );

        if ( empty( $this->template ) ) {
            $this->template = array();
            $this->template['blog_id'] = $this->template_blog_id;
            $this->template['to_copy'] = $args['to_copy'];
        }
    }

    protected function get_default_args() {
        return array(
            'to_copy' => array(
                'settings'  => false,
                'posts'     => false,
                'pages'     => false,
                'terms'     => false,
                'users'     => false,
                'menus'     => false,
                'files'     => false
            ),
            'pages_ids'     => array( 'all-pages' ),
            'post_category' => array( 'all-categories' ),
            'template_id'   => 0,
            'additional_tables' => array(),
            'block_posts_pages' => false,
            'update_dates' => false
        );
    }

    public function execute() {
        global $wpdb;

        switch_to_blog( $this->new_blog_id );
	        // Begin the transaction.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction control; no core API.
	        $wpdb->query( 'BEGIN;' );

        // In case we are not copying posts, we'll have to reset the terms count to 0
        if ( $this->settings['to_copy']['posts'] || $this->settings['to_copy']['pages'] ) {
            $this->clear_table($wpdb->posts);
            $this->clear_table($wpdb->postmeta);
            $this->clear_table($wpdb->comments);
            $this->clear_table($wpdb->commentmeta);

            $this->settings['to_copy']['comments'] = true;
        }

        foreach ( $this->settings['to_copy'] as $setting => $value ) {
            if ( $value )
                call_user_func( array( $this, 'copy_' . $setting ) );
        }

        $this->copy_additional_tables();

	        if ( $this->settings['block_posts_pages'] ) {
	            // Remove any existing block flags.
	            delete_post_meta_by_key( 'nbt_block_post' );
	
	            // Apply the block flag to every post record in the new site.
	            // Using core APIs avoids direct SQL and allows WordPress to handle caching.
	            $posts_ids = get_posts(
	                array(
	                    'fields'                 => 'ids',
	                    'post_type'              => 'any',
	                    'post_status'            => 'any',
	                    'nopaging'               => true,
	                    'no_found_rows'          => true,
	                    'update_post_meta_cache' => false,
	                    'update_post_term_cache' => false,
	                )
	            );

	            if ( ! empty( $posts_ids ) ) {
	                foreach ( $posts_ids as $post_id ) {
	                    update_post_meta( $post_id, 'nbt_block_post', true );
	                }
	            }
	        }


        $nbtpl_change_attachments_urls = apply_filters( 'nbtpl_change_attachments_urls', true );

        if ( function_exists( 'apply_filters_deprecated' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $nbtpl_change_attachments_urls = apply_filters_deprecated( 'nbt_change_attachments_urls', array( $nbtpl_change_attachments_urls ), '3.0.3', 'nbtpl_change_attachments_urls' );
        } else {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $nbtpl_change_attachments_urls = apply_filters( 'nbt_change_attachments_urls', $nbtpl_change_attachments_urls );
        }

        if ( $nbtpl_change_attachments_urls )
            $this->set_content_urls( $this->template['blog_id'], $this->new_blog_id );

        if ( ! empty( $this->settings['update_dates'] ) ) {
            $this->update_posts_dates('post');
            do_action('blog_templates-update-posts-dates', $this->template, $this->new_blog_id, $this->user_id );
        }
        if ( ! empty( $this->settings['update_dates'] ) ) {
            $this->update_posts_dates('page');
            do_action('blog_templates-update-pages-dates', $this->template, $this->new_blog_id, $this->user_id );
        }

        // Now we need to update the blog status because of a conflict with Multisite Privacy Plugin
        if ( isset( $this->settings['copy_status'] ) && $this->settings['copy_status'] &&  is_plugin_active( 'sitewide-privacy-options/sitewide-privacy-options.php' ) ) {
            update_blog_status( $this->new_blog_id, 'public', get_blog_status( $this->template_blog_id, 'public' ) );
        }

	        // If we get here, everything's fine. Commit the transaction.
	        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction control; no core API.
	        $wpdb->query( 'COMMIT;' );

        if ( isset( $this->settings['to_copy']['settings'] ) && $this->settings['to_copy']['settings'] ) {
            switch_to_blog( $this->template_blog_id );
            $theme_mods = get_theme_mods();
            restore_current_blog();

            if ( is_array( $theme_mods ) ) {
                foreach ( $theme_mods as $theme_mod => $value ) {
                    set_theme_mod( $theme_mod, $value );
                }
            }

            // Privacy options
	        $blog_details = get_blog_details( $this->template_blog_id );
            update_blog_status( get_current_blog_id(),'public', $blog_details->public );
        }

        do_action( "blog_templates-copy-after_copying", $this->template, $this->new_blog_id, $this->user_id );

        restore_current_blog();


    }

    function update_posts_dates( $post_type ) {
        global $wpdb;

        $wpdb->query(
            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
            $wpdb->prepare(
                "UPDATE {$wpdb->posts}
                    SET post_date = %s,
                        post_date_gmt = %s,
                        post_modified = %s,
                        post_modified_gmt = %s
                    WHERE post_type = %s
                      AND post_status = 'publish'",
                current_time( 'mysql', false ),
                current_time( 'mysql', true ),
                current_time( 'mysql', false ),
                current_time( 'mysql', true ),
                $post_type
            )
        );
    }


    function set_content_urls() {
        global $wpdb;

        $pattern = '/^(http|https):\/\//';
        switch_to_blog( $this->template_blog_id );
        $templated_home_url = preg_replace( $pattern, '', home_url() );
        restore_current_blog();

        switch_to_blog( $this->new_blog_id );
        $new_home_url = preg_replace( $pattern, '', home_url() );

        // Find all published posts whose content contains the templated home URL.
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->posts}
                    WHERE post_content LIKE %s
                      AND post_status = 'publish'",
                '%' . $templated_home_url . '%'
            )
        );

        foreach ( $results as $row ) {
            // Replace the templated home URL with the new blog URL.
            $post_content = str_replace( $templated_home_url, $new_home_url, $row->post_content );

            $wpdb->query(
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                $wpdb->prepare(
                    "UPDATE {$wpdb->posts}
                        SET post_content = %s
                        WHERE ID = %d",
                    $post_content,
                    $row->ID
                )
            );
        }

        restore_current_blog();
    }


    public function copy_settings() {
        global $wpdb;

        $exclude_settings = apply_filters( 'blog_template_exclude_settings', "`option_name` != 'siteurl' AND `option_name` != 'blogname' AND `option_name` != 'admin_email' AND `option_name` != 'new_admin_email' AND `option_name` != 'home' AND `option_name` != 'upload_path' AND `option_name` != 'db_version' AND `option_name` != 'secret' AND `option_name` != 'fileupload_url' AND `option_name` != 'nonce_salt'" );
        $new_prefix = $wpdb->prefix;

        //Delete the current options, except blog-specific options
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE $exclude_settings" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( ! $wpdb->last_error ) {
            //No error. Good! Now copy over the old settings

            //Switch to the template blog, then grab the settings/plugins/templates values from the template blog
            switch_to_blog( $this->template_blog_id );

            $src_blog_settings = $wpdb->get_results( "SELECT * FROM $wpdb->options WHERE $exclude_settings" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
            $template_prefix = $wpdb->prefix;

            //Switch back to the newly created blog
            restore_current_blog();

            //Now, insert the templated settings into the newly created blog
            foreach ( $src_blog_settings as $row ) {
                //Make sure none of the options are using wp_X_ convention, and if they are, replace the value with the new blog ID
                $row->option_name = str_replace( $template_prefix, $new_prefix, $row->option_name );
                if ( 'sidebars_widgets' != $row->option_name ) /* <-- Added this to prevent unserialize() call choking on badly formatted widgets pickled array */
                    $row->option_value = str_replace( $template_prefix, $new_prefix, $row->option_value );

                //To prevent duplicate entry errors, since we're not deleting ALL of the options, there could be an ID collision
                unset( $row->option_id );

                // For template blogs with deprecated DB schema (WP3.4+)
                if ( ! ( defined('NBT_TIGHT_ROW_DUPLICATION') && NBT_TIGHT_ROW_DUPLICATION ) )
                    unset( $row->blog_id );

                // Add further processing for options row
                $row = apply_filters( 'blog_templates-copy-options_row', $row, $this->template, $this->new_blog_id, $this->user_id );

                if ( ! $row )
                    continue; // Prevent empty row insertion

                //Insert the row
                $wpdb->insert( $wpdb->options, (array)$row );

                //Check for errors
                if ( ! empty( $wpdb->last_error ) ) {
                    /* translators: %s: database error message from $wpdb->last_error. */
                    $error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %s - The template was not applied. (New Blog Templates - While inserting templated settings)', 'blogtemplates' ), $wpdb->last_error ) . '</p></div>';
                    $wpdb->query("ROLLBACK;");

                    //We've rolled it back and thrown an error, we're done here
                    restore_current_blog();
                    wp_die( wp_kses_post( $error ) );
                }
            }

            // Membership integration
            if( class_exists( 'membershipadmin' ) ) {
                nbt_add_membership_caps( $this->user_id, $this->new_blog_id );
            }

            $source_blog_details = get_blog_details( $this->template_blog_id );
            $new_blog_details = array(
                'public' => $source_blog_details->public,
                'archived' => $source_blog_details->archived,
                'mature' => $source_blog_details->mature,
                'spam' => $source_blog_details->spam,
                'deleted' => $source_blog_details->deleted,
                'lang_id' => $source_blog_details->lang_id
            );
            update_blog_details( $this->new_blog_id, $new_blog_details );

            do_action( 'blog_templates-copy-options', $this->template );
        }
        else {
            /* translators: %s: database error message from $wpdb->last_error. */
            $error = '<div id="message" class="error"><p>' . sprintf( __( 'Deletion Error: %s - The template was not applied. (New Blog Templates - While removing auto-generated settings)', 'blogtemplates' ), $wpdb->last_error ) . '</p></div>';
            $wpdb->query("ROLLBACK;");
            restore_current_blog(); //Switch back to our current blog
            wp_die( wp_kses_post( $error ) );
        }
    }

    public function copy_posts() {

        $categories = in_array( 'all-categories', $this->settings['post_category'] ) ? false : $this->settings['post_category'];

        $this->copy_posts_table( $this->template_blog_id, 'posts', $categories );
        do_action( 'blog_templates-copy-posts', $this->template, $this->new_blog_id, $this->user_id );

        $this->copy_posts_table( $this->template_blog_id, 'postmeta' );
        do_action( 'blog_templates-copy-postmeta', $this->template, $this->new_blog_id, $this->user_id );
    }

    public function copy_pages() {
        $pages_ids = in_array( 'all-pages', $this->settings['pages_ids'] ) ? false : $this->settings['pages_ids'];

        $this->copy_posts_table( $this->template_blog_id, "pages", $pages_ids );
        do_action( 'blog_templates-copy-pages', $this->template, $this->new_blog_id, $this->user_id );

        $this->copy_posts_table( $this->template_blog_id, "pagemeta" );
        do_action( 'blog_templates-copy-pagemeta', $this->template, $this->new_blog_id, $this->user_id );
    }

    public function copy_comments() {
        global $wpdb;

        switch_to_blog( $this->template_blog_id );
        $source_comments = $wpdb->get_results( "SELECT * FROM $wpdb->comments" );
        $source_commentmeta = $wpdb->get_results( "SELECT * FROM $wpdb->commentmeta" );
        restore_current_blog();

        foreach ( $source_comments as $comment ) {
            $_comment = (array)$comment;
            $wpdb->insert(
                $wpdb->comments,
                $_comment
            );
        }
    }


    public function copy_terms() {
        global $wpdb;

        $this->clear_table( $wpdb->links );
        $this->copy_table( $this->template_blog_id, $wpdb->links );
        do_action( 'blog_templates-copy-links', $this->template, $this->new_blog_id, $this->user_id );

        $this->clear_table( $wpdb->terms );
        $this->copy_table( $this->template_blog_id, $wpdb->terms );
        do_action( 'blog_templates-copy-terms', $this->template, $this->new_blog_id, $this->user_id );

        $this->clear_table( $wpdb->term_relationships );
        $this->copy_table( $this->template_blog_id, $wpdb->term_relationships );
        do_action( 'blog_templates-copy-term_relationships', $this->template, $this->new_blog_id, $this->user_id );

        $this->clear_table( $wpdb->term_taxonomy );
        $this->copy_table( $this->template_blog_id, $wpdb->term_taxonomy );
        do_action( 'blog_templates-copy-term_taxonomy', $this->template, $this->new_blog_id, $this->user_id );

	    if ( isset( $wpdb->termmeta ) ) {
		    $this->clear_table( $wpdb->termmeta );
		    $this->copy_table( $this->template_blog_id, $wpdb->termmeta );
		    do_action( 'blog_templates-copy-termmeta', $this->template, $this->new_blog_id, $this->user_id );
	    }


        if ( ! $this->settings['to_copy']['posts'] ) {
            // The new blog will not have any post
            // So we have to set the terms count to 0
            $this->reset_terms_counts();
        }

        // Delete those terms related to menus
        switch_to_blog( $this->new_blog_id );
        $wpdb->query( "DELETE FROM $wpdb->terms WHERE term_id IN (SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'nav_menu')" );
        $wpdb->query( "DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id IN (SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'nav_menu')" );
        $wpdb->query( "DELETE FROM $wpdb->term_taxonomy WHERE taxonomy = 'nav_menu'" );
        restore_current_blog();
    }

    public function copy_users() {
        global $wpdb;

        switch_to_blog( $this->template_blog_id );
        $template_users = get_users();
        restore_current_blog();

        if ( ! empty( $template_users ) ) {
            foreach( $template_users as $user ) {
                $user = apply_filters( 'blog_templates-copy-user_entry', $user, $this->template, $this->new_blog_id, $this->user_id );
                if ( $user->ID == $this->user_id ) {
                    add_user_to_blog( $this->new_blog_id, $user->ID, 'administrator' );
                }
                else {
                    add_user_to_blog( $this->new_blog_id, $user->ID, $user->roles[0] );
                }
            }
        }

        do_action( 'blog_templates-copy-users', $this->template, $this->new_blog_id, $this->user_id );
    }

    public function copy_menus() {
        global $wp_version;
        if ( version_compare( $wp_version, '3.6', '>=' ) ) {
            $this->copy_menu( $this->template_blog_id, $this->new_blog_id );
        }
        else {
            $this->old_copy_menu( $this->template_blog_id, $this->new_blog_id );
        }
        $this->set_menus_urls( $this->template_blog_id, $this->new_blog_id );
    }

    public function copy_files() {
        global $wp_filesystem, $wpdb;

        // We need to copy the attachment post type from posts table
        $this->copy_posts_table( $this->template_blog_id, 'attachment' );
        $this->copy_posts_table( $this->template_blog_id, 'attachmentmeta' );

        $new_content_url = get_bloginfo('wpurl');

        switch_to_blog( $this->template_blog_id );
        $theme_slug = get_option( 'stylesheet' );

        // Attachments URL for the template blogç
        $template_attachments = get_posts( array( 'post_type' => 'attachment' ) );
        $template_content_url = get_bloginfo('wpurl');
        //Now, go back to the new blog that was just created
        restore_current_blog();

        // Determine whether attachment URLs should be rewritten when copying files/settings.
        // Default behaviour remains "true" as in legacy versions, while providing a prefixed hook name.
        $nbtpl_change_attachments_urls = apply_filters( 'nbtpl_change_attachments_urls', true );

        if ( function_exists( 'apply_filters_deprecated' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $nbtpl_change_attachments_urls = apply_filters_deprecated( 'nbt_change_attachments_urls', array( $nbtpl_change_attachments_urls ), '3.0.3', 'nbtpl_change_attachments_urls' );
        } else {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $nbtpl_change_attachments_urls = apply_filters( 'nbt_change_attachments_urls', $nbtpl_change_attachments_urls );
        }

        $dir_to_copy = $this->_get_files_fs_path( $this->template_blog_id ); //ABSPATH . 'wp-content/blogs.dir/' . $this->template_blog_id . '/files';
        $dir_to_copy = apply_filters( 'blog_templates-copy-source_directory', $dir_to_copy, $this->template, $this->new_blog_id, $this->user_id );

        if ( defined('NBT_LEGACY_PATH_RESOLUTION') && NBT_LEGACY_PATH_RESOLUTION ) {
            switch_to_blog( $this->new_blog_id );
            $dir_to_copy_into = WP_CONTENT_DIR . '/blogs.dir/' . $this->new_blog_id . '/files/';
            restore_current_blog();
        }
        else {
            $dir_to_copy_into = $this->_get_files_fs_path( $this->new_blog_id ); //ABSPATH .'wp-content/blogs.dir/' . $blog_id . '/files';
        }
        $dir_to_copy_into = apply_filters('blog_templates-copy-target_directory', $dir_to_copy_into, $this->template, $this->new_blog_id, $this->user_id);

        if ( is_dir( $dir_to_copy ) ) {
            $result = wp_mkdir_p( $dir_to_copy_into );
            if ($result) {

                include_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
                include_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );

					// Ensure core file utilities are available.
					if ( ! function_exists( 'copy_dir' ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
					}

					// copy_dir() expects filesystem permission constants to be available.
					// Some environments (notably during front-end signup flows) may not have initialized WP_Filesystem().
					if ( ( ! defined( 'FS_CHMOD_DIR' ) || ! defined( 'FS_CHMOD_FILE' ) ) && function_exists( 'WP_Filesystem' ) ) {
						WP_Filesystem();
					}

					// If the constants are still missing, fail gracefully rather than triggering a fatal in copy_dir().
					if ( ! defined( 'FS_CHMOD_DIR' ) || ! defined( 'FS_CHMOD_FILE' ) ) {
						global $wpdb;
						/* translators: %s: WordPress filesystem bootstrap function name. */
						$error = '<div id="message" class="error"><p>' . sprintf( __( 'File System Error: Unable to initialize the WordPress filesystem (%s). Please ensure your environment allows direct file operations or that the filesystem is configured properly. (New Blog Templates - While copying files)', 'blogtemplates' ), 'WP_Filesystem()' ) . '</p></div>';
						$wpdb->query( 'ROLLBACK;' );
						restore_current_blog();
						wp_die( wp_kses_post( $error ) );
					}

                if ( is_object( $wp_filesystem ) )
                    $orig_filesystem = wp_clone( $wp_filesystem );
                else
                    $orig_filesystem = $wp_filesystem;

                $wp_filesystem = new WP_Filesystem_Direct( false );

                $skip_list = apply_filters( 'nbtpl_copy_files_skip_list', array(), $dir_to_copy );

                if ( function_exists( 'apply_filters_deprecated' ) ) {
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
                    $skip_list = apply_filters_deprecated( 'nbt_copy_files_skip_list', array( $skip_list, $dir_to_copy ), '3.0.3', 'nbtpl_copy_files_skip_list' );
                } else {
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
                    $skip_list = apply_filters( 'nbt_copy_files_skip_list', $skip_list, $dir_to_copy );
                }
						$result = copy_dir( $dir_to_copy, $dir_to_copy_into, $skip_list );

                unset( $wp_filesystem );

                if ( is_object( $orig_filesystem ) )
                    $wp_filesystem = wp_clone( $orig_filesystem );
                else
                    $wp_filesystem = $orig_filesystem;

				$sitemap_path = $dir_to_copy_into . '/sitemap.xml';
				if ( file_exists( $sitemap_path ) ) {
					wp_delete_file( $sitemap_path );
				}

                // If we set the same theme, we need to replace URLs in theme mods
                if ( $this->settings['to_copy']['settings'] ) {
                    $mods = is_array( get_theme_mods() ) ? get_theme_mods() : array();
                    if ( $nbtpl_change_attachments_urls )
                        array_walk_recursive( $mods, array( &$this, 'set_theme_mods_url' ), array( $template_content_url, $new_content_url, $this->template_blog_id, $this->new_blog_id ) );
                    update_option( "theme_mods_$theme_slug", $mods );
                }

                // We need now to change the attachments URLs
                $attachment_guids = array();
                foreach ( $template_attachments as $attachment ) {
                    $new_url = str_replace( $template_content_url, $new_content_url, dirname( $attachment->guid ) );
                    $new_url = str_replace( 'sites/' . $this->template_blog_id, 'sites/' . $this->new_blog_id, $new_url );
                    $new_url = str_replace( 'blogs.dir/' . $this->template_blog_id, 'blogs.dir/' . $this->new_blog_id, $new_url );

                    // We get an array with key = old_url and value = new_url
                    $attachment_guids[ dirname( $attachment->guid ) ] = $new_url;
                }

                if ( $nbtpl_change_attachments_urls )
                    $this->set_attachments_urls( $attachment_guids );


            } else {
                /* translators: %s: destination directory path for copied files. */
                $error = '<div id="message" class="error"><p>' . sprintf( __( 'File System Error: Unable to create directory %s. (New Blog Templates - While copying files)', 'blogtemplates' ), $dir_to_copy_into ) . '</p></div>';
                $wpdb->query( 'ROLLBACK;' );
                restore_current_blog();
                wp_die( wp_kses_post( $error ) );

            }
        }
    }

    /**
     * Copy additional tables. Tables can be only created
     * @param type $copy_empty
     * @return type
     */
    public function copy_additional_tables() {
        global $wpdb;

        // Prefixes
        $new_prefix = $wpdb->prefix;
        $template_prefix = $wpdb->get_blog_prefix( $this->template_blog_id );

        $tables_to_copy = $this->settings['additional_tables'];

        // If we have copied the settings, we'll need at least to create all the tables
        // Empty or not
        $settings_copied = $this->settings['to_copy']['settings'];
        if ( $settings_copied )
            $all_source_tables = wp_list_pluck( nbt_get_additional_tables( $this->template_blog_id ), 'prefix.name' );
        else
            $all_source_tables = $tables_to_copy;

        $all_source_tables = apply_filters( 'nbtpl_copy_additional_tables', $all_source_tables );

        if ( function_exists( 'apply_filters_deprecated' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $all_source_tables = apply_filters_deprecated( 'nbt_copy_additional_tables', array( $all_source_tables ), '3.0.3', 'nbtpl_copy_additional_tables' );
        } else {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
            $all_source_tables = apply_filters( 'nbt_copy_additional_tables', $all_source_tables );
        }

        foreach ( $all_source_tables as $table ) {
            $add = in_array( $table, $tables_to_copy );
            $table = esc_sql( $table );

            // MultiDB Hack
            if ( is_a( $wpdb, 'm_wpdb' ) )
                $tablebase = end( explode( '.', $table, 2 ) );
            else
                $tablebase = $table;

            $new_table = $new_prefix . substr( $tablebase, strlen( $template_prefix ) );

            $result = $wpdb->get_results( "SHOW TABLES LIKE '{$new_table}'", ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
            if ( ! empty( $result ) ) {
                // The table is already present in the new blog
                // Clear it
                $this->clear_table( $new_table );

                if ( $add ) {
                    // And copy the content if needed
                    $this->copy_table( $this->template['blog_id'], $new_table );
                }
            }
            else {
                // The table does not exist in the new blog
                // Let's create it
                $create_script = current( $wpdb->get_col( 'SHOW CREATE TABLE ' . $table, 1 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter

                if ( $create_script && preg_match( '/\(.*\)/s', $create_script, $match ) ) {
                    $table_body = $match[0];
                    /**
                     * Allows to filter the query of the table creation
                     *
                     * @param string $query Current query to create the table
                     * @param string $new_table The name of the new table that will be created
                     * @param string $table Source table name
                     */
                    $create_table_query = apply_filters( 'nbtpl_create_additional_table_query', "CREATE TABLE IF NOT EXISTS {$new_table} {$table_body}", $new_table, $table );

                    if ( function_exists( 'apply_filters_deprecated' ) ) {
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
                        $create_table_query = apply_filters_deprecated( 'nbt_create_additional_table_query', array( $create_table_query, $new_table, $table ), '3.0.3', 'nbtpl_create_additional_table_query' );
                    } else {
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
                        $create_table_query = apply_filters( 'nbt_create_additional_table_query', $create_table_query, $new_table, $table );
                    }
                    $wpdb->query( $create_table_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter

                    if ( $add ) {
                        // And copy the content if needed
                        if ( is_a( $wpdb, 'm_wpdb' ) ) {
                            $rows = $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
                            foreach ( $rows as $row ) {
                                $wpdb->insert( $new_table, $row );
                            }
                        } else {
                            $wpdb->query( "INSERT INTO {$new_table} SELECT * FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
                        }
                    }

                }

                if ( ! empty( $wpdb->last_error ) ) {
                    // translators: %s: error message or context string.
                    $error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %s - The template was not applied. (New Blog Templates - With CREATE TABLE query for Additional Tables)', 'blogtemplates' ), $wpdb->last_error ) . '</p></div>';
                    $wpdb->query("ROLLBACK;");
                    wp_die( wp_kses_post( $error ) );
                }
            }

        }
    }

    /**
     * Proper blog filesystem path finding.
     * @param  int $blog_id Blog ID to check
     * @return string Filesystem path
     */
    protected function _get_files_fs_path( $blog_id ) {
        if ( ! is_numeric( $blog_id ) )
            return false;

        switch_to_blog( $blog_id );
        $info = wp_upload_dir();
        restore_current_blog();

        return ! empty( $info['basedir'] ) ? $info['basedir'] : false;
    }



    function set_theme_mods_url( &$item, $key, $userdata = array() ) {
        $template_upload_url = $userdata[0];
        $new_upload_url = $userdata[1];
        $template_blog_id = $userdata[2];
        $new_blog_id = $userdata[3];

        if ( ! $template_upload_url || ! $new_upload_url )
            return;

        if ( is_string( $item ) ) {
            $item = str_replace( $template_upload_url, $new_upload_url, $item );
            $item = str_replace( 'sites/' . $template_blog_id . '/', 'sites/' . $new_blog_id . '/', $item );
            $item = str_replace( 'blogs.dir/' . $template_blog_id . '/', 'blogs.dir/' . $new_blog_id . '/', $item );
        }

    }

    /**
     * Changes the base URL for all attachments
     *
     * @since 1.6.5
     */
    function set_attachments_urls( $attachment_guids ) {
        global $wpdb;

        if ( empty( $attachment_guids ) || ! is_array( $attachment_guids ) ) {
            return;
        }

        foreach ( $attachment_guids as $old_guid => $new_guid ) {
            // Update attachment GUIDs to point to the new blog URL.
            $wpdb->query(
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                $wpdb->prepare(
                    "UPDATE {$wpdb->posts}
                        SET guid = REPLACE( guid, %s, %s )
                        WHERE post_type = 'attachment'",
                    $old_guid,
                    $new_guid
                )
            );
        }
    }



     /**
    * Copy the templated blog posts table. Bit different from the
    * previous one, it can make difference between
    * posts and pages
    *
    * @param int $templated_blog_id The ID of the blog to copy
    * @param string $type post, page, postmeta or pagemeta
    *
    * @since 1.0
    */
    function copy_posts_table( $templated_blog_id, $type, $categories = false ) {
        global $wpdb;

        switch( $type ) {
            case 'posts': $table = 'posts'; break;
            case 'postmeta': $table = 'postmeta'; break;
            case 'pages': $table = 'posts'; break;
            case 'pagemeta': $table = 'postmeta'; break;
            case 'attachment': $table = 'posts'; break;
            case 'attachmentmeta': $table = 'postmeta'; break;
        }
        if ( is_array( $categories ) ) {
            $categories = array_map( 'intval', $categories );
        }


        do_action('blog_templates-copying_table', $table, $templated_blog_id);

        //Switch to the template blog, then grab the values
        switch_to_blog($templated_blog_id);
        $query = "SELECT t1.* FROM {$wpdb->$table} t1 ";

        if ( 'posts' == $type ) {
            if ( is_array( $categories ) && (is_countable($categories)?count($categories):0) > 0 )
                $query .= " INNER JOIN $wpdb->term_relationships t2 ON t2.object_id = t1.ID ";

            $query .= "WHERE t1.post_type != 'page' && t1.post_type != 'attachment' && t1.post_type != 'nav_menu_item'";

            if ( is_array( $categories ) && (is_countable($categories)?count($categories):0) > 0 ) {
                $categories_list = '(' . implode( ',', $categories ) . ')';
                $query .= " AND t2.term_taxonomy_id IN $categories_list GROUP BY t1.ID";
            }

        }
        elseif ( 'postmeta' == $type ) {
            $query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type != 'page' && t2.post_type != 'attachment' && t2.post_type != 'nav_menu_item'";
        }
        elseif ( 'pages' == $type ) {
            $query .= "WHERE t1.post_type = 'page'";

            $pages_ids = $categories;
            if ( is_array( $pages_ids ) && (is_countable($pages_ids)?count($pages_ids):0) > 0 ) {
                $query .= " AND t1.ID IN (" . implode( ',', $pages_ids ) . ")";
            }
        }
        elseif ( 'pagemeta' == $type ) {
            $query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type = 'page'";

            $pages_ids = $categories;
            if ( is_array( $pages_ids ) && (is_countable($pages_ids)?count($pages_ids):0) > 0 ) {
                $query .= " AND t2.ID IN (" . implode( ',', $pages_ids ) . ")";
            }
        }
        elseif ( 'attachment' == $type ) {
            $query .= "WHERE t1.post_type = 'attachment'";
        }
        elseif ( 'attachmentmeta' == $type ) {
            $query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type = 'attachment'";
        }

        $templated = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter
        restore_current_blog(); //Switch back to the newly created blog

        if ( (is_countable($templated)?count($templated):0) )
            $to_remove = $this->get_fields_to_remove( $wpdb->$table, $templated[0] );

        //Now, insert the templated settings into the newly created blog
        foreach ( $templated as $row ) {
            $row = (array)$row;

            foreach ( $row as $key => $value ) {
                if ( in_array( $key, $to_remove ) )
                    unset( $row[$key] );
            }

            $process = apply_filters( 'blog_templates-process_row', $row, $table, $templated_blog_id );
            if ( ! $process )
                continue;

            $wpdb->insert( $wpdb->$table, $process );
            if ( ! empty( $wpdb->last_error ) ) {
                /* translators: 1: database error message from $wpdb->last_error, 2: database table name. */
                $error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %1$s - The template was not applied. (New Blog Templates - While copying %2$s)', 'blogtemplates' ), $wpdb->last_error, $table ) . '</p></div>';
                $wpdb->query("ROLLBACK;");

                //We've rolled it back and thrown an error, we're done here
                restore_current_blog();
                wp_die( wp_kses_post( $error ) );
            }
        }
    }

    /**
    * Added to automate comparing the two tables, and making sure no old fields that have been removed get copied to the new table
    *
    * @param mixed $new_table_name
    * @param mixed $old_table_row
    *
    * @since 1.0
    */
    function get_fields_to_remove( $new_table_name, $old_table_row ) {
        //make sure we have something to compare it to
        if ( empty( $old_table_row ) )
            return false;

        //We need the old table row to be in array format, so we can use in_array()
        $old_table_row = (array)$old_table_row;

        global $wpdb;

        //Get the new table structure
        $new_table = (array) $wpdb->get_results( "SHOW COLUMNS FROM " . esc_sql( $new_table_name ) );

        $new_fields = array();
        foreach( $new_table as $row ) {
            $new_fields[] = $row->Field;
        }

        $results = array();

        //Now, go through the columns in the old table, and check if there are any that don't show up in the new table
        foreach ( $old_table_row as $key => $value ) {
            if ( ! in_array( $key,$new_fields ) ) { //If the new table doesn't have this field
                //There's a column that isn't in the new one, make note of that
                $results[] = $key;
            }
        }

        //Return the results array, which should contain all of the fields that don't appear in the new table
        return $results;
    }

    /**
    * Deletes everything from a table
    *
    * @param string $table The name of the table to clear
    *
    * @since 1.0
    */
    public function clear_table( $table ) {
        global $wpdb;

        do_action('blog_templates-clearing_table', $table);

        //Delete the current categories
        $wpdb->query( "DELETE FROM $table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ($wpdb->last_error) { //No error. Good! Now copy over the terms from the templated blog
            /* translators: 1: database error message from $wpdb->last_error, 2: database table name. */
            $error = '<div id="message" class="error"><p>' . sprintf( __( 'Deletion Error: %1$s - The template was not applied. (New Blog Templates - While clearing %2$s)', 'blogtemplates' ), $wpdb->last_error, $table ) . '</p></div>';
            $wpdb->query("ROLLBACK;");
            restore_current_blog(); //Switch back to our current blog
            wp_die( wp_kses_post( $error ) );
        }
    }

    /**
    * Copy the templated blog table
    *
    * @param int $templated_blog_id The ID of the blog to copy from
    * @param string $dest_table The name of the table to copy to
    *
    * @since 1.0
    */
    function copy_table( $templated_blog_id, $dest_table ) {
        global $wpdb;

        do_action( 'blog_templates-copying_table', $dest_table, $templated_blog_id );

        $destination_prefix = $wpdb->prefix;

        //Switch to the template blog, then grab the values
        switch_to_blog( $templated_blog_id );
        $template_prefix = $wpdb->prefix;
        $source_table = str_replace( $destination_prefix, $template_prefix, $dest_table );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $source_table is derived from vetted table names.
        $templated = $wpdb->get_results( "SELECT * FROM {$source_table}" );
        restore_current_blog(); //Switch back to the newly created blog

        if ( (is_countable($templated)?count($templated):0) )
            $to_remove = $this->get_fields_to_remove($dest_table, $templated[0]);

        //Now, insert the templated settings into the newly created blog
        foreach ($templated as $row) {
            $row = (array)$row;

            foreach ( $row as $key => $value ) {
                if ( in_array( $key, $to_remove ) )
                    unset( $row[ $key ] );
            }

            $process = apply_filters('blog_templates-process_row', $row, $dest_table, $templated_blog_id);
            if ( ! $process )
                continue;

            $wpdb->insert( $dest_table, $process );
            if ( ! empty( $wpdb->last_error ) ) {
                 /* translators: 1: database error message from $wpdb->last_error, 2: database table name. */
                $error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %1$s - The template was not applied. (New Blog Templates - While copying %2$s)', 'blogtemplates' ), $wpdb->last_error, $table ) . '</p></div>';
                $wpdb->query("ROLLBACK;");

                //We've rolled it back and thrown an error, we're done here
                restore_current_blog();
                wp_die( wp_kses_post( $error ) );
            }
        }
    }

     /**
     * Copy the templated menu and locations
     *
     * @since 1.6.6
     *
     * @param int $templated_blog_id The ID of the blog to copy
     * @param int $new_blog_id The ID of the new blog
     *
     */
    function old_copy_menu( $templated_blog_id, $new_blog_id ) {
        global $wpdb;

        do_action( 'blog_templates-copying_menu', $templated_blog_id, $new_blog_id );

        switch_to_blog( $templated_blog_id );
        $templated_posts_table = $wpdb->posts;
        $templated_postmeta_table = $wpdb->postmeta;
        $templated_term_relationships_table = $wpdb->term_relationships;

        $menu_locations = get_nav_menu_locations();
        restore_current_blog();

        switch_to_blog( $new_blog_id );
        $new_posts_table = $wpdb->posts;
        $new_postmeta_table = $wpdb->postmeta;
        $new_term_relationships_table = $wpdb->term_relationships;

        $new_blog_locations = $menu_locations;

        restore_current_blog();

		        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Cross-blog table names are derived from $wpdb properties via switch_to_blog().
        $menu_ids = $wpdb->get_col(
            $wpdb->prepare(
                'SELECT ID FROM ' . $templated_posts_table . ' WHERE post_type = %s',
                'nav_menu_item'
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

        $menu_ids = array_values( array_filter( array_map( 'absint', (array) $menu_ids ) ) );

        if ( ! empty( $menu_ids ) ) {

            // Duplicating the menu locations.
            set_theme_mod( 'nav_menu_locations', $new_blog_locations );

            // Duplicating every menu item.
            // We cannot use nav-menu functions as we need to keep all the old IDs.
            $placeholders = implode( ',', array_fill( 0, count( $menu_ids ), '%d' ) );

            // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Cross-blog table names are derived from $wpdb properties via switch_to_blog().
            $wpdb->query(
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                $wpdb->prepare(
                    'INSERT IGNORE INTO ' . $new_posts_table
                        . ' SELECT * FROM ' . $templated_posts_table
                        . ' WHERE ID IN (' . $placeholders . ')', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                    $menu_ids
                )
            );

            $wpdb->query(
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                $wpdb->prepare(
                    'INSERT IGNORE INTO ' . $new_postmeta_table
                        . ' SELECT * FROM ' . $templated_postmeta_table
                        . ' WHERE post_id IN (' . $placeholders . ')', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                    $menu_ids
                )
            );

            $wpdb->query(
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                $wpdb->prepare(
                    'INSERT IGNORE INTO ' . $new_term_relationships_table
                        . ' SELECT * FROM ' . $templated_term_relationships_table
                        . ' WHERE object_id IN (' . $placeholders . ')', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                    $menu_ids
                )
            );
            // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
        }
    }
function copy_menu( $templated_blog_id, $new_blog_id ) {
        global $wpdb;

        do_action( 'blog_templates-copying_menu', $templated_blog_id, $new_blog_id);

        switch_to_blog( $templated_blog_id );
        $templated_posts_table = $wpdb->posts;
        $templated_postmeta_table = $wpdb->postmeta;
        $templated_terms_table = $wpdb->terms;
        $templated_term_taxonomy_table = $wpdb->term_taxonomy;
        $templated_term_relationships_table = $wpdb->term_relationships;

        $menu_locations = get_nav_menu_locations();
        restore_current_blog();

        switch_to_blog( $new_blog_id );
        $new_posts_table = $wpdb->posts;
        $new_postmeta_table = $wpdb->postmeta;
        $new_terms_table = $wpdb->terms;
        $new_term_taxonomy_table = $wpdb->term_taxonomy;
        $new_term_relationships_table = $wpdb->term_relationships;

        $new_blog_locations = $menu_locations;

        set_theme_mod( 'nav_menu_locations', $new_blog_locations );
        restore_current_blog();

        // First, the menus
        $menus_ids = implode( ',', $menu_locations );

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Cross-blog table identifiers come from $wpdb after switch_to_blog().
        $menus = $wpdb->get_results( "SELECT * FROM $templated_terms_table t
            JOIN $templated_term_taxonomy_table tt ON t.term_id = tt.term_id
            WHERE taxonomy = 'nav_menu'"
        );

        if ( ! empty( $menus ) ) {

            foreach ( $menus as $menu ) {

                // Inserting the menu
                $wpdb->query(
                    // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                    $wpdb->prepare(
                        "INSERT IGNORE INTO $new_terms_table
                        (term_id, name, slug, term_group)
                        VALUES
                        (%d, %s, %s, %d)",
                        $menu->term_id,
                        $menu->name,
                        $menu->slug,
                        $menu->term_group
                    )
                );




                // Terms taxonomies
                $term_taxonomies = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $templated_term_taxonomy_table
                        WHERE term_id = %d",
                        $menu->term_id
                    )
                );


                $terms_taxonomies_ids = array();
                foreach ( $term_taxonomies as $term_taxonomy ) {
                    $terms_taxonomies_ids[] = $term_taxonomy->term_taxonomy_id;

                    // Inserting terms taxonomies
                    $wpdb->query(
                        // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                        $wpdb->prepare(
                            "INSERT IGNORE INTO $new_term_taxonomy_table
                            (term_taxonomy_id, term_id, taxonomy, description, parent, count)
                            VALUES
                            (%d, %d, %s, %s, %d, %d)",
                            $term_taxonomy->term_taxonomy_id,
                            $term_taxonomy->term_id,
                            $term_taxonomy->taxonomy,
                            empty( $term_taxonomy->description ) ? '' : $term_taxonomy->description,
                            $term_taxonomy->parent,
                            $term_taxonomy->count
                        )
                    );
                }
// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter


                $terms_taxonomies_ids = array_values( array_filter( array_map( 'absint', $terms_taxonomies_ids ) ) );

                if ( empty( $terms_taxonomies_ids ) ) {
                    $term_relationships = array();
                } else {
                    $tt_placeholders = implode( ',', array_fill( 0, count( $terms_taxonomies_ids ), '%d' ) );

                    $term_relationships = $wpdb->get_results(
                        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                        $wpdb->prepare(
                            "SELECT * FROM $templated_term_relationships_table\n                            WHERE term_taxonomy_id IN ( $tt_placeholders )", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                            $terms_taxonomies_ids
                        )
                    );
                }



                $objects_ids = array();
                foreach ( $term_relationships as $term_relationship ) {
                    $objects_ids[] = $term_relationship->object_id;

                    // Inserting terms relationships
                    $wpdb->query(
                        // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                        $wpdb->prepare(
                            "INSERT IGNORE INTO $new_term_relationships_table
                            (object_id, term_taxonomy_id, term_order)
                            VALUES
                            (%d, %d, %d)",
                            $term_relationship->object_id,
                            $term_relationship->term_taxonomy_id,
                            $term_relationship->term_order
                        )
                    );
                }

                // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
                // We need to split the queries here due to MultiDB issues

                // Inserting the objects
                $objects_ids = array_map( 'absint', $objects_ids );
                $objects_ids = array_values( array_filter( $objects_ids ) );

                if ( empty( $objects_ids ) ) {
                    continue;
                }

                $ids_placeholders = implode( ',', array_fill( 0, count( $objects_ids ), '%d' ) );

                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and dynamic IN placeholders.
                $objects = $wpdb->get_results(
                    // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                    $wpdb->prepare(
                        "SELECT * FROM $templated_posts_table
                            WHERE ID IN ( $ids_placeholders )", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                        $objects_ids
                    ),
                    ARRAY_N
                );

                foreach ( $objects as $object ) {
                    $row_placeholders = implode( ',', array_fill( 0, count( $object ), '%s' ) );

                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and dynamic VALUES placeholders.
                    $wpdb->query(
                        // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic VALUES() placeholders.
                        $wpdb->prepare(
                            "INSERT IGNORE INTO $new_posts_table VALUES ( $row_placeholders )", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic VALUES() placeholders.
                            $object
                        )
                    );
                }

                // Inserting the objects meta
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and dynamic IN placeholders.
                $objects_meta = $wpdb->get_results(
                    // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                    $wpdb->prepare(
                        "SELECT * FROM $templated_postmeta_table
                            WHERE post_id IN ( $ids_placeholders )", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() placeholders.
                        $objects_ids
                    ),
                    ARRAY_N
                );

                foreach ( $objects_meta as $object_meta ) {
                    $row_placeholders = implode( ',', array_fill( 0, count( $object_meta ), '%s' ) );

                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and dynamic VALUES placeholders.
                    $wpdb->query(
                        // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic VALUES() placeholders.
                        $wpdb->prepare(
                            "INSERT IGNORE INTO $new_postmeta_table VALUES ( $row_placeholders )", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic VALUES() placeholders.
                            $object_meta
                        )
                    );
                }
}

        }
    }

    /**
     * Replace the manually inserted links in menus
     *
     * @return type
     */
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

    function set_menus_urls( $templated_blog_id, $blog_id ) {
        global $wpdb;

        $pattern = '/^(http|https):\/\//';

        // Get the "stripped" home URL for the template blog.
        switch_to_blog( $templated_blog_id );
        $templated_home_url = preg_replace( $pattern, '', home_url() );
        restore_current_blog();

        // Get the "stripped" home URL for the new blog.
        switch_to_blog( $blog_id );
        $new_home_url = preg_replace( $pattern, '', home_url() );

	        // Look up all custom menu item URLs.
	        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Required to rewrite menu URLs; constrained by indexed meta_key.
	        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_id, meta_value
                    FROM {$wpdb->postmeta}
                    WHERE meta_key = %s",
                '_menu_item_url'
            )
        );

        foreach ( $results as $row ) {
            $meta_value = preg_replace( $pattern, '', $row->meta_value );

            if ( strpos( $meta_value, $templated_home_url ) !== false ) {
                $new_meta_value = str_replace( $templated_home_url, $new_home_url, $row->meta_value );

                // Push the updated URL back to postmeta using the helper, so the SQL is prepared safely.
                $wpdb->update(
                    $wpdb->postmeta,
                    array( 'meta_value' => $new_meta_value ),
                    array( 'meta_id' => $row->meta_id ),
                    array( '%s' ),
                    array( '%d' )
                );
            }
        }

        restore_current_blog();
    }


    /**
     * Reset the terms counts to 0
     *
     * @param Integer $blog_id
     */
    function reset_terms_counts() {

        global $wpdb;
        $result = $wpdb->query( "UPDATE $wpdb->term_taxonomy SET count = 0" );
    }
}


// Back-compat alias for pre-3.0.3 class name (prefix too short for current WPCS).
if ( ! class_exists( 'NBT_Template_copier', false ) && class_exists( 'NBTPL_Template_Copier', false ) ) {
    class_alias( 'NBTPL_Template_Copier', 'NBT_Template_copier' );
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
