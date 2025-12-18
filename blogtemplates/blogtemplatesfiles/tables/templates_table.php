<?php

if( ! class_exists( 'WP_List_Table' ) )
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );#[\AllowDynamicProperties]



class NBT_Templates_Table extends WP_List_Table {

    var $localization_domain = 'blogtemplates';

    function __construct(){
        parent::__construct(
            array(
                'singular'  => 'template',
                'plural'    => 'templates',
            )
        );
    }


    function column_default( $item, $column_name ) {
        $content = '';
        switch($column_name){
            default: {
                if ( isset( $item[ $column_name ] ) )
                    $content = $item[ $column_name ];
            }
        }

        return apply_filters( 'blog_templates-templates_table_custom_column', $content, $item, $column_name );
    }

    function column_name( $item ) {
        global $pagenow;

        $url = $pagenow;
        $url = add_query_arg(
            array(
                'page' => 'blog_templates_main',
                't' => $item['ID']
            ),
            $pagenow
        );

        $url_delete = add_query_arg(
            array(
                'page' => 'blog_templates_main',
                'd' => $item['ID']
            ),
            $pagenow
        );
        $url_delete = wp_nonce_url( $url_delete, 'blog_templates-delete_template' );

        $url_default = add_query_arg(
            array(
                'page' => 'blog_templates_main',
                'default' => $item['ID']
            ),
            $pagenow
        );
        $url_default = wp_nonce_url( $url_default, 'blog_templates-make_default' );

        $url_remove_default = add_query_arg(
            array(
                'page' => 'blog_templates_main',
                'remove_default' => $item['ID']
            ),
            $pagenow
        );
        $url_remove_default = wp_nonce_url( $url_remove_default, 'blog_templates-remove_default' );

        $actions = array(
        /* translators: %s: URL for the edit template link. */
            'edit'      => sprintf( __( '<a href="%s">Edit</a>', 'blogtemplates' ), $url ),
        /* translators: %s: URL for the delete template link. */
            'delete'    => sprintf( __( '<a href="%s">Delete</a>', 'blogtemplates' ), $url_delete ),
        );

        if ( $item['is_default'] ) {
        /* translators: %s: URL for the remove default template link. */
            $actions['remove_default'] = sprintf( __( '<a href="%s">Remove default</a>', 'blogtemplates' ), $url_remove_default );
            $default = ' <strong>' . __( '(Default)', 'blogtemplates' ) . '</strong>';
        }
        else {
        /* translators: %s: URL for the make default template link. */
            $actions['make_default'] = sprintf( __( '<a href="%s">Make default</a>', 'blogtemplates' ), $url_default );
            $default = '';
        }

        return '<a href="' . $url . '">' . $item['name'] . '</a>' . $default . $this->row_actions( $actions );
    }

    function column_blog( $item ) {
        switch_to_blog( $item['blog_id'] );
        $name = get_bloginfo( 'name' );
        $url = admin_url();
        restore_current_blog();
        return $name . ' <a href="' . $url . '">' . __( 'Go to Dashboard', 'blogtemplates' ) . '</a>';
    }

    function column_screenshot( $item ) {

        if ( empty( $item['options']['screenshot'] ) )
            $img = nbt_get_default_screenshot_url($item['blog_id']);
        else
            $img = $item['options']['screenshot'];

        return '<img style="max-width:25%;" src="' . $img . '"/>';
    }

    function get_columns(){
        $columns = array(
            'name'          => __( 'Template Name', 'blogtemplates' ),
            'blog'          => __( 'Blog', 'blogtemplates' ),
            'screenshot'    => __( 'Screenshot', 'blogtemplates' ),
        );
        return apply_filters( 'blog_templates-templates_table_columns', $columns );
    }

    function get_sortable_columns(){
        $sortable_columns = array(
            'name'          => array( 'name', false ),
            'blog'          => array( 'blog_id', false ),
        );
        return $sortable_columns;
    }

    function prepare_items() {
        $per_page = 30;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $model = nbt_get_model();
        $this->items = $model->get_templates();

        $current_page = $this->get_pagenum();
        $total_items = count( $this->items );

        $this->items = array_slice( $this->items, ( ( $current_page - 1 ) * $per_page ), $per_page );

        $new_data = array();
        foreach ( $this->items as $row ) {
            $new_row = $row;
            $categories_tmp = $model->get_template_categories( $row['ID'] );

            $categories = array();
            foreach ( $categories_tmp as $row ) {
                $categories[] = $row['name'];
            }

            $new_row['categories'] = implode( ', ', $categories );

            $new_data[] = $new_row;
        }
        $this->items = $new_data;

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil( $total_items / $per_page )
            )
        );
    }

}