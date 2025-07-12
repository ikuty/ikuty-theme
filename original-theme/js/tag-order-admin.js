/**
 * Tag Order Admin JavaScript
 * Handles drag and drop functionality for tag ordering
 */

jQuery(document).ready(function($) {
    // Check if we're on the tags admin page
    if (window.location.href.indexOf('edit-tags.php') === -1 || window.location.href.indexOf('taxonomy=post_tag') === -1) {
        return;
    }

    // Add notice about drag and drop functionality
    $('.wrap h1').after(
        '<div class="tag-order-notice">' +
        '<strong>ドラッグ&ドロップ機能:</strong> タグの行をドラッグして順序を変更できます。変更は自動的に保存されます。' +
        '</div>'
    );

    // Initialize sortable functionality
    $('#the-list').sortable({
        items: 'tr',
        cursor: 'move',
        placeholder: 'ui-sortable-placeholder',
        helper: function(e, ui) {
            ui.children().each(function() {
                $(this).width($(this).width());
            });
            return ui;
        },
        start: function(e, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(e, ui) {
            updateTagOrder();
        }
    });

    // Function to update tag order via AJAX
    function updateTagOrder() {
        var tagOrders = {};
        var order = 1;

        $('#the-list tr').each(function() {
            var termId = getTermIdFromRow($(this));
            if (termId) {
                tagOrders[termId] = order;
                order++;
            }
        });

        // Show loading indicator
        $('.tag-order-notice').html('<strong>保存中...</strong>');

        $.ajax({
            url: tagOrderAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_tag_order',
                tag_orders: tagOrders,
                nonce: tagOrderAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.tag-order-notice').html(
                        '<strong>✓ 順序が保存されました</strong> - タグの順序が正常に更新されました。'
                    ).css('background', '#d4edda').css('border-color', '#c3e6cb').css('color', '#155724');

                    // Update the order column values
                    $('#the-list tr').each(function(index) {
                        $(this).find('.column-tag_order').text(index + 1);
                    });

                    // Reset notice after 3 seconds
                    setTimeout(function() {
                        $('.tag-order-notice').html(
                            '<strong>ドラッグ&ドロップ機能:</strong> タグの行をドラッグして順序を変更できます。変更は自動的に保存されます。'
                        ).css('background', '#d1ecf1').css('border-color', '#bee5eb').css('color', '#0c5460');
                    }, 3000);
                } else {
                    $('.tag-order-notice').html(
                        '<strong>エラー:</strong> 順序の保存に失敗しました。'
                    ).css('background', '#f8d7da').css('border-color', '#f5c6cb').css('color', '#721c24');
                }
            },
            error: function() {
                $('.tag-order-notice').html(
                    '<strong>エラー:</strong> 通信エラーが発生しました。'
                ).css('background', '#f8d7da').css('border-color', '#f5c6cb').css('color', '#721c24');
            }
        });
    }

    // Extract term ID from table row
    function getTermIdFromRow(row) {
        var termId = null;
        
        // Try to get from checkbox value
        var checkbox = row.find('input[type="checkbox"][name="delete_tags[]"]');
        if (checkbox.length) {
            termId = checkbox.val();
        }
        
        // Try to get from delete link
        if (!termId) {
            var deleteLink = row.find('.delete a');
            if (deleteLink.length) {
                var href = deleteLink.attr('href');
                var matches = href.match(/tag_ID=(\d+)/);
                if (matches) {
                    termId = matches[1];
                }
            }
        }
        
        // Try to get from edit link
        if (!termId) {
            var editLink = row.find('.row-title');
            if (editLink.length) {
                var href = editLink.attr('href');
                if (href) {
                    var matches = href.match(/tag_ID=(\d+)/);
                    if (matches) {
                        termId = matches[1];
                    }
                }
            }
        }
        
        return termId;
    }

    // Add visual feedback for draggable rows
    $('#the-list tr').hover(
        function() {
            $(this).css('background-color', '#f6f7f7');
        },
        function() {
            $(this).css('background-color', '');
        }
    );
});