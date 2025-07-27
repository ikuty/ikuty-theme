<?php
/**
 * Tag order functionality
 *
 * @package original-theme
 */

// Add custom field to tag add form
add_action( 'post_tag_add_form_fields', 'add_tag_order_field' );
function add_tag_order_field( $taxonomy ) {
    ?>
    <div class="form-field term-order-wrap">
        <label for="tag-order"><?php _e( 'タグの順番', 'original-theme' ); ?></label>
        <input type="number" id="tag-order" name="tag_order" value="" min="0" step="1" />
        <p class="description"><?php _e( 'タグの表示順番を指定してください（数値が小さいほど上位に表示されます）', 'original-theme' ); ?></p>
    </div>
    <div class="form-field term-show-on-top-wrap">
        <label for="tag-show-on-top">
            <input type="checkbox" id="tag-show-on-top" name="tag_show_on_top" value="1" checked="checked" />
            <?php _e( 'トップ画面表示', 'original-theme' ); ?>
        </label>
        <p class="description"><?php _e( 'チェックするとトップ画面のタグ一覧に表示されます', 'original-theme' ); ?></p>
    </div>
    <?php
}

// Add custom field to tag edit form
add_action( 'post_tag_edit_form_fields', 'edit_tag_order_field' );
function edit_tag_order_field( $term ) {
    $tag_order = get_term_meta( $term->term_id, 'tag_order', true );
    $tag_show_on_top = get_term_meta( $term->term_id, 'tag_show_on_top', true );
    // Default to true for existing tags if not set
    if ( '' === $tag_show_on_top ) {
        $tag_show_on_top = '1';
    }
    ?>
    <tr class="form-field term-order-wrap">
        <th scope="row">
            <label for="tag-order"><?php _e( 'タグの順番', 'original-theme' ); ?></label>
        </th>
        <td>
            <input type="number" id="tag-order" name="tag_order" value="<?php echo esc_attr( $tag_order ); ?>" min="0" step="1" />
            <p class="description"><?php _e( 'タグの表示順番を指定してください（数値が小さいほど上位に表示されます）', 'original-theme' ); ?></p>
        </td>
    </tr>
    <tr class="form-field term-show-on-top-wrap">
        <th scope="row">
            <label for="tag-show-on-top"><?php _e( 'トップ画面表示', 'original-theme' ); ?></label>
        </th>
        <td>
            <input type="checkbox" id="tag-show-on-top" name="tag_show_on_top" value="1" <?php checked( $tag_show_on_top, '1' ); ?> />
            <p class="description"><?php _e( 'チェックするとトップ画面のタグ一覧に表示されます', 'original-theme' ); ?></p>
        </td>
    </tr>
    <?php
}

// Save tag order and show on top when creating new tag
add_action( 'created_post_tag', 'save_tag_fields' );
function save_tag_fields( $term_id ) {
    if ( isset( $_POST['tag_order'] ) && '' !== $_POST['tag_order'] ) {
        $tag_order = absint( $_POST['tag_order'] );
        update_term_meta( $term_id, 'tag_order', $tag_order );
    }
    
    // Save show on top flag (default to 1 if checked, 0 if not)
    $tag_show_on_top = isset( $_POST['tag_show_on_top'] ) ? '1' : '0';
    update_term_meta( $term_id, 'tag_show_on_top', $tag_show_on_top );
}

// Save tag order and show on top when editing tag
add_action( 'edited_post_tag', 'save_tag_fields' );

// Add custom columns to tags list table
add_filter( 'manage_edit-post_tag_columns', 'add_tag_custom_columns' );
function add_tag_custom_columns( $columns ) {
    $columns['tag_order'] = __( '順番', 'original-theme' );
    $columns['tag_show_on_top'] = __( 'トップ画面表示', 'original-theme' );
    return $columns;
}

// Display tag custom columns
add_action( 'manage_post_tag_custom_column', 'display_tag_custom_columns', 10, 3 );
function display_tag_custom_columns( $content, $column_name, $term_id ) {
    if ( 'tag_order' === $column_name ) {
        $tag_order = get_term_meta( $term_id, 'tag_order', true );
        $content = $tag_order ? $tag_order : '—';
    }
    
    if ( 'tag_show_on_top' === $column_name ) {
        $tag_show_on_top = get_term_meta( $term_id, 'tag_show_on_top', true );
        // Default to true for existing tags if not set
        if ( '' === $tag_show_on_top ) {
            $tag_show_on_top = '1';
        }
        $content = '1' === $tag_show_on_top ? '✓' : '—';
    }
    
    return $content;
}

// Make tag columns sortable
add_filter( 'manage_edit-post_tag_sortable_columns', 'make_tag_columns_sortable' );
function make_tag_columns_sortable( $columns ) {
    $columns['name'] = 'name';
    $columns['tag_order'] = ['tag_order', true];
    $columns['tag_show_on_top'] = ['tag_show_on_top', true];
    return $columns;
}

// Handle sorting by tag fields
add_action( 'parse_term_query', 'sort_tags_by_custom_fields' );
function sort_tags_by_custom_fields( $query ) {
    if ( ! is_admin() ) {
        return;
    }
    
    $orderby = $query->query_vars['orderby'];
    if ( 'tag_order' === $orderby ) {
        $query->query_vars['meta_key'] = 'tag_order';
        $query->query_vars['orderby'] = 'meta_value_num';
    } elseif ( 'tag_show_on_top' === $orderby ) {
        $query->query_vars['meta_key'] = 'tag_show_on_top';
        $query->query_vars['orderby'] = 'meta_value';
    }
}

// Set default sort order for tags list table
function default_tag_order_sort ($query_var_defaults, $taxonomies) {
    if ( is_admin() && isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] === 'post_tag' ) {
        // Only set default if no orderby is specified
        if ( ! isset( $_GET['orderby'] ) ) {
           $query_var_defaults['orderby'] = 'tag_order';
        }
    }
    return $query_var_defaults;
}
add_filter('get_terms_defaults','default_tag_order_sort',10,2);

// Get tags ordered by custom order field
function get_ordered_tags( $args = array() ) {
    $defaults = array(
        'taxonomy' => 'post_tag',
        'hide_empty' => true,
        'meta_key' => 'tag_order',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'tag_order',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => 'tag_order',
                'compare' => 'NOT EXISTS'
            )
        )
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    // Get tags with order set
    $ordered_tags = get_terms( array_merge( $args, array(
        'meta_query' => array(
            array(
                'key' => 'tag_order',
                'compare' => 'EXISTS'
            )
        )
    ) ) );
    
    // Get tags without order set
    $unordered_tags = get_terms( array_merge( $args, array(
        'meta_query' => array(
            array(
                'key' => 'tag_order',
                'compare' => 'NOT EXISTS'
            )
        ),
        'orderby' => 'name',
        'order' => 'ASC'
    ) ) );
    
    // Merge ordered tags first, then unordered tags
    return array_merge( $ordered_tags, $unordered_tags );
}

// Get tags for top page display (only those with show_on_top flag set to true)
function get_top_page_tags( $args = array() ) {
    $defaults = array(
        'taxonomy' => 'post_tag',
        'hide_empty' => true,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array(
                    'key' => 'tag_show_on_top',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => 'tag_show_on_top',
                    'compare' => 'NOT EXISTS'
                )
            )
        )
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    // Get all tags matching the criteria first
    $all_tags = get_terms( $args );
    
    if ( empty( $all_tags ) ) {
        return array();
    }
    
    // Separate tags with and without order
    $ordered_tags = array();
    $unordered_tags = array();
    
    foreach ( $all_tags as $tag ) {
        $tag_order = get_term_meta( $tag->term_id, 'tag_order', true );
        $tag_show_on_top = get_term_meta( $tag->term_id, 'tag_show_on_top', true );
        
        // Default to true if not set (for existing tags)
        if ( '' === $tag_show_on_top ) {
            $tag_show_on_top = '1';
        }
        
        // Only include tags marked for top page display
        if ( '1' === $tag_show_on_top ) {
            if ( $tag_order ) {
                $tag->order = (int) $tag_order;
                $ordered_tags[] = $tag;
            } else {
                $unordered_tags[] = $tag;
            }
        }
    }
    
    // Sort ordered tags by their order value
    usort( $ordered_tags, function( $a, $b ) {
        return $a->order - $b->order;
    } );
    
    // Sort unordered tags by name
    usort( $unordered_tags, function( $a, $b ) {
        return strcasecmp( $a->name, $b->name );
    } );
    
    // Merge ordered tags first, then unordered tags
    return array_merge( $ordered_tags, $unordered_tags );
}

// Enqueue scripts for drag and drop functionality
add_action( 'admin_enqueue_scripts', 'enqueue_tag_order_scripts' );
function enqueue_tag_order_scripts( $hook ) {
    // Only load on tags.php page
    if ( 'edit-tags.php' !== $hook || ! isset( $_GET['taxonomy'] ) || 'post_tag' !== $_GET['taxonomy'] ) {
        return;
    }
    
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_script( 
        'tag-order-admin', 
        get_template_directory_uri() . '/js/tag-order-admin.js', 
        array( 'jquery', 'jquery-ui-sortable' ), 
        _S_VERSION, 
        true 
    );
    
    wp_localize_script( 'tag-order-admin', 'tagOrderAjax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'tag_order_nonce' )
    ) );
}

// AJAX handler for updating tag order
add_action( 'wp_ajax_update_tag_order', 'handle_tag_order_update' );
function handle_tag_order_update() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'tag_order_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    
    // Check user permissions
    if ( ! current_user_can( 'manage_categories' ) ) {
        wp_die( 'Insufficient permissions' );
    }
    
    $tag_orders = $_POST['tag_orders'];
    
    if ( is_array( $tag_orders ) ) {
        foreach ( $tag_orders as $term_id => $order ) {
            $term_id = intval( $term_id );
            $order = intval( $order );
            
            if ( $term_id > 0 ) {
                update_term_meta( $term_id, 'tag_order', $order );
            }
        }
        
        wp_send_json_success( 'Tag order updated successfully' );
    } else {
        wp_send_json_error( 'Invalid data format' );
    }
}

// Add CSS for admin styling
add_action( 'admin_head', 'tag_order_admin_css' );
function tag_order_admin_css() {
    $screen = get_current_screen();
    if ( $screen && 'edit-post_tag' === $screen->id ) {
        ?>
        <style>
        .term-order-wrap input[type="number"] {
            width: 100px;
        }
        .column-tag_order {
            width: 80px;
            text-align: center;
        }
        .column-tag_show_on_top {
            width: 100px;
            text-align: center;
        }
        .wp-list-table.tags .ui-sortable-helper {
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .wp-list-table.tags .ui-sortable-placeholder {
            background: #f0f6fc;
            height: 40px;
        }
        .wp-list-table.tags tbody tr {
            cursor: move;
        }
        .wp-list-table.tags tbody tr:hover {
            background: #f6f7f7;
        }
        .tag-order-notice {
            margin: 15px 0;
            padding: 10px;
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            color: #0c5460;
        }
        </style>
        <?php
    }
}

// Set default show_on_top value for existing tags that don't have this meta
add_action( 'init', 'set_default_show_on_top_for_existing_tags' );
function set_default_show_on_top_for_existing_tags() {
    // Only run this once by checking if we've already processed existing tags
    $processed = get_option( 'original_theme_tags_show_on_top_processed', false );
    if ( $processed ) {
        return;
    }
    
    // Get all existing tags
    $all_tags = get_terms( array(
        'taxonomy' => 'post_tag',
        'hide_empty' => false,
    ) );
    
    if ( ! empty( $all_tags ) ) {
        foreach ( $all_tags as $tag ) {
            $show_on_top = get_term_meta( $tag->term_id, 'tag_show_on_top', true );
            
            // If no value is set, default to true
            if ( '' === $show_on_top ) {
                update_term_meta( $tag->term_id, 'tag_show_on_top', '1' );
            }
        }
    }
    
    // Mark as processed so we don't run this again
    update_option( 'original_theme_tags_show_on_top_processed', true );
}
?>