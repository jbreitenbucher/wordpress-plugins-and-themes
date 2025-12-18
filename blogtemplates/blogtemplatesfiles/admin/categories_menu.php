<?php
#[\AllowDynamicProperties]
class blog_templates_categories_menu {

    var $menu_slug = 'blog_templates_categories';

    var $page_id;

    var $updated_message = '';

    var $current_category;

    var $errors = false;

    function __construct() {


        // Admin notices and data processing
        add_action( 'admin_init', array($this, 'validate_form' ) );

        $this->current_category = array( 'name' => '', 'description' => '' );
	}

	/**
     * Adds the options subpanel
     *
     * @since 1.2.1
     */
    function network_admin_page() {
        $this->page_id = add_submenu_page( 'blog_templates_main', __( 'Template categories', 'blogtemplates' ), __( 'Template categories', 'blogtemplates' ), 'manage_network', $this->menu_slug, array($this,'render_page'));
    }

    public function render_page() {

    	if ( ! empty( $this->errors ) ) {
    		?>
				<div class="error"><p><?php echo wp_kses_post( $this->errors ); ?></p></div>
    		<?php
    	}
    	elseif ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    		?>
				<div class="updated">
					<p><?php esc_html_e( 'Changes have been applied', 'blogtemplates' ); ?></p>
				</div>
    		<?php
    	}
    	?>
			<div class="wrap">
				
				<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

								<?php
				$action   = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$cat_id   = isset( $_GET['category'] ) ? absint( wp_unslash( $_GET['category'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( 'edit' === $action && $cat_id ) :
				?>


					<?php
						$model = nbt_get_model();
						$category = $model->get_template_category( $cat_id );

						if ( ! $category )
							wp_die( esc_html__( 'The category does not exist', 'blogtemplates' ) );

					?>
					<form id="categories-table-form" action="" method="post">
						<table class="form-table">
							<?php
								ob_start();
							?>
								<input type="text" name="cat_name" class="large-text" value="<?php echo esc_attr( $category['name'] ); ?>">
							<?php
								$this->render_row( __( 'Category name', 'blogtemplates' ), ob_get_clean() );
							?>

							<?php
								ob_start();
							?>
								<textarea name="cat_description" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $category['description'] ); ?></textarea>
							<?php
								$this->render_row( __( 'Category description', 'blogtemplates' ), ob_get_clean() );
							?>
						</table>
						<input type="hidden" name="cat_id" value="<?php echo esc_attr( $cat_id ); ?>">
						<?php wp_nonce_field( 'edit-nbt-category', '_wpnonce' ); ?>
						<?php submit_button( null, 'primary', 'submit-edit-nbt-category' ); ?>
					</form>

				<?php else: ?>
					<?php
						$cats_table = new blog_templates_categories_table();
						$cats_table->prepare_items();
				    ?>
			    	<br class="clear">
					<div id="col-container">
						<div id="col-right">
							<div class="col-wrap">
								<div class="form-wrap">
									<form id="categories-table-form" action="" method="post">
										<?php $cats_table->display(); ?>
									</form>
								</div>
							</div>
						</div>
						<div id="col-left">
							<div class="col-wrap">
								<div class="form-wrap">
									<h3><?php esc_html_e( 'Add new category', 'blogtemplates' ); ?></h3>
									<form id="categories-table-form" action="" method="post">
										<?php wp_nonce_field( 'add-nbt-category' ); ?>
										<div class="form-field">
											<label for="cat_name"><?php esc_html_e( 'Category Name', 'blogtemplates' ); ?>
												<input name="cat_name" id="cat_name" type="text" value="<?php echo esc_attr( $this->current_category['name'] ); ?>" size="40" aria-required="true">
											</label>
										</div>
										<div class="form-field">
											<label for="cat_description"><?php esc_html_e( 'Category Description', 'blogtemplates' ); ?>
												<textarea name="cat_description" rows="5" cols="40"><?php echo esc_textarea( $this->current_category['description'] ); ?></textarea>
											</label>
										</div>
										<?php submit_button( __( 'Add New Category', 'blogtemplates' ), 'primary', 'submit-nbt-new-category' ); ?>
									</form>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>

			</div>
    	<?php
    }

	public function validate_form() {
		if ( isset( $_POST['submit-edit-nbt-category'] ) ) {
			$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'edit-nbt-category' ) ) {
				wp_die( esc_html__( 'Security check error', 'blogtemplates' ) );
			}

			if ( isset( $_POST['cat_name'], $_POST['cat_id'] ) && ! empty( $_POST['cat_name'] ) ) {
				$model = nbt_get_model();

				$description = isset( $_POST['cat_description'] ) ? wp_kses_post( wp_unslash( $_POST['cat_description'] ) ) : '';

				$name = sanitize_text_field( wp_unslash( $_POST['cat_name'] ) );
				$cat_id = isset( $_POST['cat_id'] ) ? absint( wp_unslash( $_POST['cat_id'] ) ) : 0;

				if ( $cat_id > 0 ) {
					$model->update_template_category( $cat_id, $name, $description );
				}

				$link = remove_query_arg( array( 'action', 'category' ) );
				$link = add_query_arg( 'updated', 'true', $link );
				wp_safe_redirect( $link );
				exit;
			}
			else {
				$this->errors = esc_html__( 'Name cannot be empty', 'blogtemplates' );
			}
		}

		if ( isset( $_POST['submit-nbt-new-category'] ) ) {
    $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'add-nbt-category' ) ) {
        wp_die( esc_html__( 'Security check error', 'blogtemplates' ) );
    }

    $model = nbt_get_model();

    $description = isset( $_POST['cat_description'] ) ? wp_kses_post( wp_unslash( $_POST['cat_description'] ) ) : '';

    $name = isset( $_POST['cat_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cat_name'] ) ) : '';

    if ( ! empty( $name ) ) {
        $model->add_template_category( $name, $description );
        $link = remove_query_arg( array( 'action', 'category' ) );
        $link = add_query_arg( 'updated', 'true', $link );
        wp_safe_redirect( $link );
        exit;
    } else {
        $this->errors = esc_html__( 'Name cannot be empty', 'blogtemplates' );
    }
}
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

}