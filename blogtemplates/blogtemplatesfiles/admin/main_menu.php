<?php
#[\AllowDynamicProperties]
class blog_templates_main_menu {

    /**
    * @var array $options Stores the options for this plugin
    *
    * @since 1.0
    */ 
    
    var $menu_slug = 'blog_templates_main';

    var $page_id;

    var $updated_message = '';


	function __construct() {
		global $wp_version;

        // Admin notices and data processing
        add_action( 'admin_init', array( $this, 'admin_options_page_posted' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'add_javascript' ) );
	}



    public function add_javascript($hook) {

    	if ( get_current_screen()->id == $this->page_id . '-network' ) {
    		wp_enqueue_script( 'nbt-templates-js', NBTPL_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-templates.js', array( 'jquery' ), NBTPL_PLUGIN_VERSION, true );
wp_enqueue_style( 'nbt-settings-css', NBTPL_PLUGIN_URL . 'blogtemplatesfiles/assets/css/settings.css', array(), NBTPL_PLUGIN_VERSION );
wp_enqueue_script( 'jquery-ui-autocomplete' );

    		wp_enqueue_style( 'nbt-jquery-ui-styles', NBTPL_PLUGIN_URL . 'blogtemplatesfiles/assets/css/jquery-ui.css', array(), NBTPL_PLUGIN_VERSION );
$params = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			);
			wp_localize_script( 'nbt-templates-js', 'export_to_text_js', $params );
    	}
    }

	/**
     * Adds the options subpanel
     *
     * @since 1.2.1
     */
     
    function network_admin_page() {
        $this->page_id = add_menu_page( __( 'Blog Templates', 'blogtemplates' ), __( 'Blog Templates', 'blogtemplates' ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'), 'div' );
    }

    /**
     * Adds the options subpanel
     *
     * @since 1.0
     */ 
     
    function pre_3_1_network_admin_page() {
        add_menu_page( __( 'Templates', 'blogtemplates' ), __( 'Templates', 'blogtemplates' ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'filter_plugin_actions' ) );
    }

    private function get_post_categories_list( $template ) {
    	ob_start();
    	?>
    		<ul id="nbt-post-categories-checklist">
				<li id="all-categories"><label class="selectit"><input class="all-selector" value="all-categories" type="checkbox" <?php checked( in_array( 'all-categories', $template['post_category'] ) ); ?> name="post_category[]" id="in-all-categories"> <strong><?php esc_html_e( 'All categories', 'blogtemplates' ); ?></strong></label></li>
				<?php
					switch_to_blog( $template['blog_id'] );
					wp_terms_checklist( 0, array( 'selected_cats' => $template['post_category'], 'checked_ontop' => 0 ) );
					restore_current_blog();
				?>
	 		</ul>
    	<?php
    	return ob_get_clean();
    }

    private function get_pages_list( $template ) {
		switch_to_blog( $template['blog_id'] );
		$pages = get_pages();
		restore_current_blog();

		$selected_pages     = array();
		$all_pages_selected = false;

		if ( isset( $template['pages_ids'] ) ) {
			$selected_pages     = (array) $template['pages_ids'];
			$all_pages_selected = in_array( 'all-pages', $selected_pages, true );
		}

		ob_start();
			?>
			<ul id="nbt-pages-checklist">
				<li id="all-nbt-pages"><label class="selectit"><input class="all-selector" value="all-pages" type="checkbox" <?php checked( $all_pages_selected ); ?> name="pages_ids[]" id="in-all-nbt-pages"> <strong><?php esc_html_e( 'All pages', 'blogtemplates' ); ?></strong></label></li>
				<?php foreach ( $pages as $page ) : ?>
					<li id="page-<?php echo esc_attr( $page->ID ); ?>">
						<label class="selectit">
							<input type="checkbox" name="pages_ids[]" id="in-page-<?php echo esc_attr( $page->ID ); ?>" value="<?php echo esc_attr( $page->ID ); ?>" <?php checked( $all_pages_selected || in_array( $page->ID, $selected_pages, true ) ); ?> /> <?php echo esc_html( $page->post_title ); ?>
						</label>
					</li>
				<?php endforeach; ?>
		 		</ul>
	 		<?php
		return ob_get_clean();
	}


    /**
     * Adds settings/options page
     *
     * @since 1.0
     */ 
     
    function admin_options_page() {

	    $t = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

	    global $pagenow;
	    $url = $pagenow . '?page=' . $this->menu_slug;
			?>

			<div class="wrap">
				
			    <form method="post" id="options" enctype="multipart/form-data">
			        <?php wp_nonce_field('blog_templates-update-options', '_nbtnonce');

			        if ( ! is_numeric( $t ) ) { ?>
			            <h2><?php esc_html_e( 'Blog Templates', 'blogtemplates' ); ?></h2>
			            <?php
			                $templates_table = new NBT_Templates_Table();
			                $templates_table->prepare_items();
			                $templates_table->display();
			            ?>

			            <h2><?php esc_html_e( 'Create New Blog Template', 'blogtemplates' ); ?></h2>
			            <p><?php esc_html_e('Create a blog template based on the blog of your choice! This allows you (and other admins) to copy all of the selected blog\'s settings and allow you to create other blogs that are almost exact copies of that blog. (Blog name, URL, etc will change, so it\'s not a 100% copy)','blogtemplates'); ?></p>
			            <p><?php esc_html_e('Simply fill out the form below and click "Create Blog Template!" to generate the template for later use!','blogtemplates'); ?></p>
			            <table class="form-table">
			                <?php ob_start(); ?>
			                    <input name="template_name" type="text" id="template_name" class="regular-text"/>
			                <?php $this->render_row( __( 'Template Name:', 'blogtemplates' ), ob_get_clean() ); ?>

			                <?php ob_start(); ?>
			                    <input name="copy_blog_id" type="text" id="copy_blog_id" size="10" placeholder="<?php esc_attr_e( 'Blog ID', 'blogtemplates' ); ?>"/>
			                    <div class="ui-widget">
				                    <label for="search_for_blog"> <?php esc_html_e( 'Or search by blog path', 'blogtemplates' ); ?>
										<input type="text" id="search_for_blog" class="medium-text">
										<span class="description"><?php esc_html_e( 'For example, if the blog you are searching has an URL like http://ablog.mydomain.com, you can type "ablog"', 'blogtemplates' ); ?></span>
				                    </label>
				                </div>
			                <?php $this->render_row( __( 'Blog ID:', 'blogtemplates' ), ob_get_clean() ); ?>

			                <?php ob_start(); ?>
			                    <textarea class="large-text" name="template_description" type="text" id="template_description" cols="45" rows="5"></textarea>
			                <?php $this->render_row( __( 'Template Description:', 'blogtemplates' ), ob_get_clean() ); ?>

			                <?php
			                	ob_start();
			                    echo '<strong>' . esc_html__( 'After you add this template, a set of options will show up on the edit screen.', 'blogtemplates' ) . '</strong>';
			                ?>
			                <?php $this->render_row( __( 'More options', 'blogtemplates' ), ob_get_clean() ); ?>

			            </table>
			            <p><?php esc_html_e('Please note that this will turn the blog you selected into a template blog. Any changes you make to this blog will change the template, as well! We recommend creating specific "Template Blogs" for this purpose, so you don\'t accidentally add new settings, content, or users that you don\'t want in your template.','blogtemplates'); ?></p>
			            <p>
			            	<?php
			            	/* translators: 1: opening link tag to create a new blog, 2: closing link tag. */
			            	printf( esc_html__( 'This means that if you would like to make a template from an existing blog, you need to %1$screate the new blog%2$s first.', 'blogtemplates' ), '<a href="' . esc_url( is_network_admin() ? network_admin_url( 'site-new.php' ) : admin_url( 'wpmu-blogs.php' ) ) . '">', '</a>' );
			            	?>
			            </p>
			            <?php submit_button( __( 'Create Blog Template!', 'blogtemplates' ), 'primary', 'save_new_template' ); ?>


			        <?php
			            } else {
			            	$model = nbt_get_model();
			                $template = $model->get_template( $t );
			        ?>

			            <h2><?php esc_html_e( 'Edit Blog Template', 'blogtemplates' ); ?></h2>
			            <p><a href="<?php echo esc_url( $url ); ?>">&laquo; <?php esc_html_e( 'Back to Blog Templates', 'blogtemplates' ); ?></a></p>
			            <input type="hidden" name="template_id" value="<?php echo esc_attr( $t ); ?>" />
			            <div id="nbtpoststuff">
			            	<div id="post-body" class="metabox-holder columns-2">
			            		<div id="post-body-content">
					            	<table class="form-table">
						               	 <?php ob_start(); ?>
						                    <input name="template_name" type="text" id="template_name" class="regular-text" value="<?php echo esc_attr( $template['name'] ); ?>"/>
						                <?php $this->render_row( __( 'Template Name:', 'blogtemplates' ), ob_get_clean() ); ?>

						                <?php ob_start(); ?>
						                    <textarea class="widefat" name="template_description" id="template_description" cols="45" rows="5"><?php echo esc_textarea( $template['description'] );?></textarea>
						                <?php $this->render_row( __( 'Template Description', 'blogtemplates' ), ob_get_clean() ); ?>

						                <?php
						                    ob_start();
						                    $options_to_copy = array(
						                        'settings' => array(
						                        	'title' => __( 'Wordpress Settings, Current Theme, and Active Plugins', 'blogtemplates' ),
						                        	'content' => false
						                        ),
						                        'posts'    => array(
						                        	'title' => __( 'Posts', 'blogtemplates' ),
						                        	'content' => $this->get_post_categories_list( $template )
						                        ),
						                        'pages'    => array(
						                        	'title' => __( 'Pages', 'blogtemplates' ),
						                        	'content' => $this->get_pages_list( $template )
						                        ),
						                        'terms'    => array(
						                        	'title' => __( 'Categories, Tags, and Links', 'blogtemplates' ),
						                        	'content' => false
						                        ),
						                        'users'    => array(
						                        	'title' => __( 'Users', 'blogtemplates' ),
						                        	'content' => false
						                        ),
						                        'menus'    => array(
						                        	'title' => __( 'Menus', 'blogtemplates' ),
						                        	'content' => false
						                        ),
						                        'files'    => array(
						                        	'title' => __( 'Files', 'blogtemplates' ),
						                        	'content' => false
						                        )
						                    );

						                    foreach ( $options_to_copy as $key => $value ) : ?>
						                            <div id="nbt-<?php echo esc_attr( $key ); ?>-to-copy" class="postbox">
														<h3 class="hndle">
															<label><input type="checkbox" name="to_copy[]" id="nbt-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $template['to_copy'] ) ); ?>> <?php echo esc_html( $value['title'] ); ?></label><br/>
														</h3>
														<?php if ( $value['content'] ): ?>
															<div class="inside">
								<?php
								// The contents for these postboxes (pages/posts/etc.) are generated by this plugin in admin-only code.
								// Individual dynamic values are escaped at the point of generation.
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo $value['content'];
								?>
															</div>
														<?php endif; ?>
													</div>

						                  	<?php endforeach; ?>
						                <?php $this->render_row( __( 'What To Copy To New Blog?', 'blogtemplates' ), ob_get_clean() ); ?>


						                <?php if ( is_plugin_active( 'sitewide-privacy-options/sitewide-privacy-options.php' ) ): ?>
						                    <?php ob_start(); ?>
						                        <input type='checkbox' name='copy_status' id='nbt-copy-status' <?php checked( ! empty( $template['copy_status'] ) ); ?>>
						                        <label for='nbt-copy-status'><?php esc_html_e( 'Check if you want also to copy the blog status (Public or not)', 'blogtemplates' ); ?></label>
						                    <?php $this->render_row( __( 'Copy Status?', 'blogtemplates' ), ob_get_clean() ); ?>
						                <?php endif; ?>

						                <?php
						$nbtpl_activate_block_posts_feature = apply_filters( 'nbtpl_activate_block_posts_feature', true );

						if ( function_exists( 'apply_filters_deprecated' ) ) {
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
							$nbtpl_activate_block_posts_feature = apply_filters_deprecated( 'nbt_activate_block_posts_feature', array( $nbtpl_activate_block_posts_feature ), '3.0.3', 'nbtpl_activate_block_posts_feature' );
						} else {
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Back-compat for legacy integrations.
							$nbtpl_activate_block_posts_feature = apply_filters( 'nbt_activate_block_posts_feature', $nbtpl_activate_block_posts_feature );
						}

						if ( $nbtpl_activate_block_posts_feature ) :
					?>
							                <?php ob_start(); ?>
						                        <input type='checkbox' name='block_posts_pages' id='nbt-block-posts-pages' <?php checked( $template['block_posts_pages'] ); ?>>
						                        <label for='nbt-block-posts-pages'><?php esc_html_e( 'If selected, pages and posts included in the template will not be allowed to be edited (even for the blog administrator). Only Super Admins will be able to edit the text of copied posts/pages.', 'blogtemplates' ); ?></label>
						                    <?php $this->render_row( __( 'Lock Posts/Pages', 'blogtemplates' ), ob_get_clean() ); ?>
						                <?php endif; ?>

					                    <?php ob_start(); ?>
							            	<label>
							            		<input type="checkbox" name="update_dates" <?php checked( ! empty( $template['update_dates'] ) ); ?>>
							            		<?php esc_html_e( 'If selected, the dates of the posts/pages will be updated to the date when the blog is created', 'blogtemplates' ); ?>
							            	</label>
					                	<?php $this->render_row( __( 'Update dates', 'blogtemplates' ), ob_get_clean() ); ?>

					                    <?php
					                    	ob_start();

					                    	if ( empty( $template['screenshot'] ) )
					                    		$img = nbt_get_default_screenshot_url($template['blog_id']);
					                    	else
					                    		$img = $template['screenshot'];

										?>
											<img src="<?php echo esc_url( $img ); ?>" style="max-width:100%;"/><br/>
											<p>
												<label for="screenshot">
													<?php esc_html_e( 'Upload new screenshot', 'blogtemplates' ); ?>
													<input type="file" name="screenshot">
												</label>
												<?php submit_button( __( 'Reset screenshot', 'blogtemplates' ), 'secondary', 'reset-screenshot', true ); ?>
											</p>
					                    <?php $this->render_row( __( 'Screenshot', 'blogtemplates' ), ob_get_clean() ); ?>
									</table>

						            <br/><br/>
						            <h2><?php esc_html_e( 'Advanced Options', 'blogtemplates' ); ?></h2>

							       	<table class="form-table">

						                <?php ob_start(); ?>

						                <?php global $wpdb; ?>
								<?php /* translators: 1: database table prefix for this WordPress installation. */ ?>
								<p><?php printf( esc_html__( 'The tables listed below use the database prefix "%1$s". Only tables with that prefix are listed here.', 'blogtemplates' ), esc_html( $wpdb->prefix ) ); ?></p><br/>

						                <?php

						                $additional_tables = nbt_get_additional_tables( $template['blog_id'] );

						                if ( ! empty( $additional_tables ) ) {
						                	foreach ( $additional_tables as $table ) {
						                		$table_name = $table['name'];
						                		$value = $table['prefix.name'];
						                		$checked = isset( $template['additional_tables'] ) && is_array( $template['additional_tables'] ) && in_array( $value, $template['additional_tables'] );
						                		?>
						                			<input type='checkbox' name='additional_template_tables[]' <?php checked( $checked ); ?> id="nbt-<?php echo esc_attr( $value ); ?>" value="<?php echo esc_attr( $value ); ?>">
						                			<label for="nbt-<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $table_name ); ?></label><br/>
						                		<?php
						                	}

						                }
						                else {
						                	?>
						                		<p><?php esc_html_e( 'There are no additional tables to display for this blog', 'blogtemplates' ); ?></p>
						                	<?php
						                }


						                $this->render_row( __( 'Additional Tables', 'blogtemplates' ), ob_get_clean() ); ?>


					            	</table>

					            </div>

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
						            <?php
						            	$model = nbt_get_model();
						            	$categories = $model->get_templates_categories();
						            	$template_categories_tmp = $model->get_template_categories( $t );

						            	$template_categories = array();
						            	foreach ( $template_categories_tmp as $row ) {
						            		$template_categories[] = absint( $row['ID'] );
						            	}
						            ?>
						            <div id="postbox-container-1" class="postbox-container">
										<div id="side-sortables" class="meta-box-sortables ui-sortable">
											<div id="categorydiv" class="postbox ">
												<div class="handlediv" title=""><br></div><h3 class="hndle"><span><?php esc_html_e( 'Template categories', 'blogtemplates' ); ?></span></h3>
												<div class="inside">
													<div id="taxonomy-category" class="categorydiv">
														<div id="category-all" class="tabs-panel">
															<ul id="templatecategorychecklist" class="categorychecklist form-no-clear">
																<?php foreach ( $categories as $category ): ?>
																	<li id="template-cat-<?php echo esc_attr( $category['ID'] ); ?>"><label class="selectit"><input value="<?php echo esc_attr( $category['ID'] ); ?>" <?php checked( in_array( $category['ID'], $template_categories ) ); ?> type="checkbox" name="template_category[]"> <?php echo esc_html( $category['name'] ); ?></label></li>
																<?php endforeach; ?>
															</ul>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								<?php endif; ?>
							</div>
				        </div>
		            </div>
		            <div class="clear"></div>
		            <?php submit_button( __( 'Save template', 'blogtemplates' ), 'primary', 'save_updated_template' ); ?>
		        <?php } ?>


		    </form>
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

            $t = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

            $save_template = ( ! empty( $_POST['reset-screenshot'] ) || ! empty( $_POST['save_updated_template'] ) );
            if( $save_template ) {

                if ( ! isset( $_POST['_nbtnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_nbtnonce'] ) ), 'blog_templates-update-options' ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    die( esc_html__( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', 'blogtemplates' ) );


				$post_data = wp_unslash( $_POST );

				$template_name = '';
				if ( isset( $post_data['template_name'] ) ) {
					$template_name = sanitize_text_field( $post_data['template_name'] );
				}

				$template_description = '';
				if ( isset( $post_data['template_description'] ) ) {
					$template_description = preg_replace(
						'~<\s*script[^>]*>.*?<\s*/\s*script\s*>~is',
						'',
						$post_data['template_description']
					);
					$template_description = wp_kses_post( $template_description );
				}

				$to_copy = array();
				if ( isset( $post_data['to_copy'] ) && is_array( $post_data['to_copy'] ) ) {
					$to_copy = array_map( 'sanitize_text_field', $post_data['to_copy'] );
				}

				$additional_tables = array();
				if ( isset( $post_data['additional_template_tables'] ) && is_array( $post_data['additional_template_tables'] ) ) {
					$additional_tables = array_map( 'sanitize_text_field', $post_data['additional_template_tables'] );
				}
				$args = array(
					'name'              => $template_name,
					'description'       => $template_description,
					'to_copy'           => $to_copy,
					'additional_tables' => $additional_tables,
					'copy_status'       => isset( $post_data['copy_status'] ) ? true : false,
					'block_posts_pages' => isset( $post_data['block_posts_pages'] ) ? true : false,
					'update_dates'      => isset( $post_data['update_dates'] ) ? true : false,
				);

				$uploaded_file = array();
				if ( isset( $_FILES['screenshot'] ) && is_array( $_FILES['screenshot'] ) ) {
					$uploaded_file = wp_unslash( $_FILES['screenshot'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				}

				if ( ! empty( $uploaded_file['tmp_name'] ) ) {
					if ( isset( $uploaded_file['name'] ) ) {
						$uploaded_file['name'] = sanitize_file_name( $uploaded_file['name'] );
					}

					$wp_filetype = wp_check_filetype_and_ext(
						isset( $uploaded_file['tmp_name'] ) ? $uploaded_file['tmp_name'] : '',
						isset( $uploaded_file['name'] ) ? $uploaded_file['name'] : '',
						false
					);

					if ( ! empty( $wp_filetype['type'] ) && ! wp_match_mime_types( 'image', $wp_filetype['type'] ) ) {
						wp_die( esc_html__( 'The uploaded file is not a valid image. Please try again.', 'blogtemplates' ) );
					}

					$movefile = wp_handle_upload( $uploaded_file, array( 'test_form' => false ) );

					if ( $movefile && ! isset( $movefile['error'] ) && ! empty( $movefile['url'] ) ) {
						$args['screenshot'] = esc_url_raw( $movefile['url'] );
					}
				} else {
					$template_id = isset( $post_data['template_id'] ) ? absint( $post_data['template_id'] ) : 0;
					if ( $template_id > 0 ) {
						$template = $model->get_template( $template_id );
						$args['screenshot'] = ! empty( $template['screenshot'] ) ? esc_url_raw( $template['screenshot'] ) : false;
					} else {
						$args['screenshot'] = false;
					}
				}

                if ( ! empty( $_POST['reset-screenshot'] ) ) {
                    $template_id = 0;
                    if ( isset( $post_data['template_id'] ) ) {
                        $template_id = absint( $post_data['template_id'] );
                    }
                    if ( $template_id > 0 ) {
                        $template = $model->get_template( $template_id );
                        if ( ! empty( $template['screenshot'] ) ) {

						$upload_dir 		= wp_upload_dir();

						//Remove the WP-Content part form the URL
						$screenshot_url 	= $template['screenshot'];
						$content_url 		= $upload_dir['baseurl'];
						$screenshot_path 	= str_replace($content_url, "", $screenshot_url);

						//Get the url pieces
						$screenshot_path_array 	= explode( "/", $screenshot_path );
						$screenshot_file 		= $upload_dir['basedir'];

						$total_screenshot_fragments = (is_countable($screenshot_path_array)?count($screenshot_path_array):0);
						for( $i=0; $i<$total_screenshot_fragments; $i++ ){
							if( isset( $screenshot_path_array[$i+1] ) ){
								$screenshot_file .= DIRECTORY_SEPARATOR.$screenshot_path_array[$i+1];
							}
						}

						//If the file exists, we remove it
						if( !is_dir( $screenshot_file ) && file_exists( $screenshot_file ) ){
							wp_delete_file( $screenshot_file );
						}
					}

					}
            		$args['screenshot'] = false;
            	}

            	// POST CATEGORIES
                $post_category = array( 'all-categories' );
                if ( isset( $post_data['post_category'] ) && is_array( $post_data['post_category'] ) ) {
					$categories = $post_data['post_category'];

					if ( in_array( 'all-categories', $categories, true ) ) {
						$post_category = array( 'all-categories' );
					} else {

						$post_category = array();
						foreach ( $categories as $category ) {
							if ( ! is_numeric( $category ) ) {
								continue;
							}

							$post_category[] = absint( $category );
						}
					}
				}
                $args['post_category'] = $post_category;

                // PAGES IDs
                $pages_ids = array( 'all-pages' );

                if ( isset( $post_data['pages_ids'] ) && is_array( $post_data['pages_ids'] ) ) {
					if ( in_array( 'all-pages', $post_data['pages_ids'], true ) ) {
						$pages_ids = array( 'all-pages' );
					} else {
						$pages_ids = array();
						foreach ( $post_data['pages_ids'] as $page_id ) {
							if ( ! is_numeric( $page_id ) ) {
								continue;
							}

							$pages_ids[] = absint( $page_id );
						}
					}
				}
                $args['pages_ids'] = $pages_ids;


                // TEMPLATE CATEGORY
                if ( ! isset( $post_data['template_category'] ) || ! is_array( $post_data['template_category'] ) ) {
					$template_category = array( $model->get_default_category_id() );
				} else {
					$categories = $post_data['template_category'];

					$template_category = array();
					foreach ( $categories as $category ) {
						if ( ! is_numeric( $category ) ) {
							continue;
						}

						$template_category[] = absint( $category );
					}
				}

                $model->update_template_categories( $t, $template_category );

                $model->update_template( $t, $args );

                $this->updated_message =  __( 'Your changes were successfully saved!', 'blogtemplates' );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );

			} elseif ( ! empty( $_POST['save_new_template'] ) ) {
				if ( ! isset( $_POST['_nbtnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_nbtnonce'] ) ), 'blog_templates-update-options' ) ) {
					wp_die( esc_html__( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', 'blogtemplates' ) );
				}

				$post_data = wp_unslash( $_POST );

				$raw_blog_id = isset( $post_data['copy_blog_id'] ) ? $post_data['copy_blog_id'] : '';
				$blog_id     = absint( $raw_blog_id );

				if ( ! $blog_id || ! get_blog_details( array( 'blog_id' => $blog_id ) ) ) {
					wp_die( esc_html__( 'Whoops! The blog ID you posted could not be found. Please go back and try again. (Generated by New Blog Templates)', 'blogtemplates' ) );
				}

				if ( is_main_site( $blog_id ) ) {
					wp_die( esc_html__( 'Whoops! The blog ID you posted refers to the main site. Please go back and try again. (Generated by New Blog Templates)', 'blogtemplates' ) );
				}

				if ( ! empty( $post_data['template_name'] ) ) {
					$name = sanitize_text_field( $post_data['template_name'] );
				} else {
					$name = __( 'A template', 'blogtemplates' );
				}

				$description = '';
				if ( ! empty( $post_data['template_description'] ) ) {
					$description = preg_replace(
						'~<\s*script[^>]*>.*?<\s*/\s*script\s*>~is',
						'',
						$post_data['template_description']
					);
					$description = wp_kses_post( $description );
				}

				$settings = array(
					'to_copy'           => array(),
					'post_category'     => array( 'all-categories' ),
					'copy_status'       => false,
					'block_posts_pages' => false,
					'pages_ids'         => array( 'all-pages' ),
					'update_dates'      => false,
				);

				$template_id = $model->add_template( $blog_id, $name, $description, $settings );

				$to_url = add_query_arg(
					array(
						'page' => $this->menu_slug,
						't'    => $template_id,
					),
					network_admin_url( 'admin.php' )
				);
				wp_safe_redirect( $to_url );
				exit;

            } elseif( isset( $_GET['remove_default'] ) ) {

                $remove_default_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
                if ( ! $remove_default_nonce || ! wp_verify_nonce( $remove_default_nonce, 'blog_templates-remove_default' ) )
                    wp_die( esc_html__( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', 'blogtemplates' ) );

               	$model->remove_default_template();
               	$settings = nbt_get_settings();

               	$settings['default'] = '';
               	nbt_update_settings( $settings );

                $this->updated_message = __( 'The default template was successfully turned off.', 'blogtemplates' );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );

            } elseif ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) {

                $default_id = isset( $_GET['default'] ) ? absint( wp_unslash( $_GET['default'] ) ) : 0;
                $make_default_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
                if ( ! $default_id || ! $make_default_nonce || ! wp_verify_nonce( $make_default_nonce, 'blog_templates-make_default' ) )
                    wp_die( esc_html__( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', 'blogtemplates' ) );

				$default_updated = $model->set_default_template( $default_id );

				if ( $default_updated ) {
					$settings = nbt_get_settings();
					$settings['default'] = $default_id;
	               	nbt_update_settings( $settings );
	            }

                $this->updated_message =  __( 'The default template was successfully updated.', 'blogtemplates' );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );

            } elseif ( isset( $_GET['d'] ) && is_numeric( $_GET['d'] ) ) {

                if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'blog_templates-delete_template' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    wp_die( esc_html__( 'Whoops! There was a problem with your request and your data may not have been successfully posted. Please go back and try again. (Generated by New Blog Templates)', 'blogtemplates' ) );
                }

                $delete_id = absint( wp_unslash( $_GET['d'] ) );

                $model->delete_template( $delete_id );

                $this->updated_message =  __( 'Success! The template was successfully deleted.', 'blogtemplates' );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );
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