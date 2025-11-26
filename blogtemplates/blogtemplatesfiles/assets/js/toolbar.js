jQuery(document).ready(function($) {
    // Listen for changes on the category dropdown in the toolbar.
    $('#nbt-toolbar').on('change', '.nbt-template-category-select', function() {
        var $select  = $(this);
        var cat_id   = $select.val();
        var $toolbar = $('#nbt-toolbar');
        var type     = $toolbar.data('toolbar-type') || '';

        // The container that holds all the template tiles.
        var $content = $('#blog_template-selection .blog_template-option');

        var data = {
            category_id: cat_id,
            action: 'nbt_filter_categories',
            nbt_nonce: nbt_toolbar_js.nbt_nonce,
            type: type
        };

        $.ajax({
            url: nbt_toolbar_js.ajaxurl,
            data: data,
            type: 'POST',
            beforeSend: function() {
                $content.html(
                    '<div id="toolbar-loader">' +
                        '<img src="' + nbt_toolbar_js.imagesurl + 'ajax-loader.gif" alt="" />' +
                    '</div>'
                );
            }
        })
        .done(function(response) {
            // Expecting wp_send_json_success( array( 'html' => $html ) )
            if (response && response.success && response.data && response.data.html) {
                $content.html(response.data.html);
            } else if (response && response.html) {
                // Fallback if someone ever changes the PHP to send raw html
                $content.html(response.html);
            } else {
                console.error('Unexpected response from nbt_filter_categories:', response);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error in nbt_filter_categories:', status, error);
        });
    });
});