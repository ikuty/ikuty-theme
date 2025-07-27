/**
 * Category Order Admin JavaScript
 * Handles drag and drop functionality for category ordering with hierarchy constraints
 */

jQuery(document).ready(function($) {
    // Check if we're on the categories admin page
    if (window.location.href.indexOf('edit-tags.php') === -1 || window.location.href.indexOf('taxonomy=category') === -1) {
        return;
    }

    // Add notice about drag and drop functionality
    $('.wrap h1').after(
        '<div class="category-order-notice">' +
        '<strong>ドラッグ&ドロップ機能:</strong> カテゴリの行をドラッグして順序を変更できます。' +
        '子カテゴリは親カテゴリより前に移動できません。変更は自動的に保存されます。' +
        '</div>'
    );

    // Build category hierarchy information
    var categoryHierarchy = buildCategoryHierarchy();

    // Initialize sortable functionality with hierarchy constraints
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
        beforeStop: function(e, ui) {
            // Check if the move is valid before allowing it
            if (!isValidMove(ui.item, ui.placeholder)) {
                $('.category-order-notice').html(
                    '<strong class="hierarchy-warning">⚠️ 無効な移動:</strong> 子カテゴリを親カテゴリより前に移動することはできません。'
                ).addClass('hierarchy-warning');
                
                // Reset notice after 5 seconds
                setTimeout(function() {
                    $('.category-order-notice').html(
                        '<strong>ドラッグ&ドロップ機能:</strong> カテゴリの行をドラッグして順序を変更できます。' +
                        '子カテゴリは親カテゴリより前に移動できません。変更は自動的に保存されます。'
                    ).removeClass('hierarchy-warning');
                }, 5000);
                
                // Cancel the sortable operation
                $(this).sortable('cancel');
                return false;
            }
        },
        update: function(e, ui) {
            updateCategoryOrder();
        }
    });

    // Function to build category hierarchy information
    function buildCategoryHierarchy() {
        var hierarchy = {};
        
        $('#the-list tr').each(function() {
            var termId = getTermIdFromRow($(this));
            var parentId = getParentIdFromRow($(this));
            
            if (termId) {
                hierarchy[termId] = {
                    parent: parentId,
                    children: [],
                    element: $(this)
                };
            }
        });

        // Build children arrays
        for (var termId in hierarchy) {
            var parentId = hierarchy[termId].parent;
            if (parentId && hierarchy[parentId]) {
                hierarchy[parentId].children.push(termId);
            }
        }

        return hierarchy;
    }

    // Function to check if a move is valid (respects hierarchy)
    function isValidMove(item, placeholder) {
        var movingTermId = getTermIdFromRow(item);
        var movingCategory = categoryHierarchy[movingTermId];
        
        if (!movingCategory || !movingCategory.parent) {
            // Root category can move anywhere
            return true;
        }

        // Find the parent category's position
        var parentElement = categoryHierarchy[movingCategory.parent].element;
        var placeholderPosition = placeholder.index();
        var parentPosition = parentElement.index();

        // Child category cannot be moved before its parent
        return placeholderPosition > parentPosition;
    }

    // Function to update category order via AJAX
    function updateCategoryOrder() {
        var categoryOrders = {};
        var order = 1;

        $('#the-list tr').each(function() {
            var termId = getTermIdFromRow($(this));
            if (termId) {
                categoryOrders[termId] = order;
                order++;
            }
        });

        // Show loading indicator
        $('.category-order-notice').html('<strong>保存中...</strong>');

        $.ajax({
            url: categoryOrderAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_category_order',
                category_orders: categoryOrders,
                nonce: categoryOrderAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.category-order-notice').html(
                        '<strong>✓ 順序が保存されました</strong> - カテゴリの順序が正常に更新されました。'
                    ).css('background', '#d4edda').css('border-color', '#c3e6cb').css('color', '#155724');

                    // Update the order column values
                    $('#the-list tr').each(function(index) {
                        $(this).find('.column-category_order').text(index + 1);
                    });

                    // Reset notice after 3 seconds
                    setTimeout(function() {
                        $('.category-order-notice').html(
                            '<strong>ドラッグ&ドロップ機能:</strong> カテゴリの行をドラッグして順序を変更できます。' +
                            '子カテゴリは親カテゴリより前に移動できません。変更は自動的に保存されます。'
                        ).css('background', '#d1ecf1').css('border-color', '#bee5eb').css('color', '#0c5460');
                    }, 3000);
                } else {
                    $('.category-order-notice').html(
                        '<strong>エラー:</strong> 順序の保存に失敗しました。'
                    ).css('background', '#f8d7da').css('border-color', '#f5c6cb').css('color', '#721c24');
                }
            },
            error: function() {
                $('.category-order-notice').html(
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

    // Extract parent ID from table row (from the category name with hierarchy indicators)
    function getParentIdFromRow(row) {
        var parentId = null;
        
        // Look for parent information in the row's data or class
        // WordPress adds hierarchy indicators to category names
        var nameCell = row.find('.column-name .row-title');
        if (nameCell.length) {
            var nameText = nameCell.text();
            // If the name starts with "—" it's a child category
            if (nameText.indexOf('—') === 0) {
                // Find the parent by looking at previous rows
                var currentRow = row;
                var parentFound = false;
                
                while (!parentFound && currentRow.prev().length > 0) {
                    currentRow = currentRow.prev();
                    var prevNameText = currentRow.find('.column-name .row-title').text();
                    
                    // Parent category doesn't have "—" prefix or has fewer "—" characters
                    var currentDashes = (nameText.match(/^—+/) || [''])[0].length;
                    var prevDashes = (prevNameText.match(/^—+/) || [''])[0].length;
                    
                    if (prevDashes < currentDashes) {
                        parentId = getTermIdFromRow(currentRow);
                        parentFound = true;
                    }
                }
            }
        }
        
        return parentId;
    }

    // Add visual indicators for child categories
    $('#the-list tr').each(function() {
        var nameCell = $(this).find('.column-name .row-title');
        if (nameCell.length && nameCell.text().indexOf('—') === 0) {
            $(this).addClass('child-category');
        }
    });

    // Add visual feedback for draggable rows
    $('#the-list tr').hover(
        function() {
            if (!$(this).hasClass('ui-sortable-helper')) {
                $(this).css('background-color', '#f6f7f7');
            }
        },
        function() {
            if (!$(this).hasClass('ui-sortable-helper')) {
                $(this).css('background-color', '');
            }
        }
    );
});