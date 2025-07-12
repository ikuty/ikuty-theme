<?php
/**
 * Category order functionality
 *
 * @package original-theme
 */

// Add custom field to category add form
add_action( 'category_add_form_fields', 'add_category_order_field' );
function add_category_order_field( $taxonomy ) {
    ?>
    <div class="form-field term-order-wrap">
        <label for="category-order"><?php _e( 'カテゴリの順番', 'original-theme' ); ?></label>
        <input type="number" id="category-order" name="category_order" value="" min="0" step="1" />
        <p class="description"><?php _e( 'カテゴリの表示順番を指定してください（数値が小さいほど上位に表示されます）', 'original-theme' ); ?></p>
    </div>
    <?php
}

// Add custom field to category edit form
add_action( 'category_edit_form_fields', 'edit_category_order_field' );
function edit_category_order_field( $term ) {
    $category_order = get_term_meta( $term->term_id, 'category_order', true );
    ?>
    <tr class="form-field term-order-wrap">
        <th scope="row">
            <label for="category-order"><?php _e( 'カテゴリの順番', 'original-theme' ); ?></label>
        </th>
        <td>
            <input type="number" id="category-order" name="category_order" value="<?php echo esc_attr( $category_order ); ?>" min="0" step="1" />
            <p class="description"><?php _e( 'カテゴリの表示順番を指定してください（数値が小さいほど上位に表示されます）', 'original-theme' ); ?></p>
        </td>
    </tr>
    <?php
}

// Save category order when creating new category
add_action( 'created_category', 'save_category_order' );
function save_category_order( $term_id ) {
    if ( isset( $_POST['category_order'] ) && '' !== $_POST['category_order'] ) {
        $category_order = absint( $_POST['category_order'] );
        update_term_meta( $term_id, 'category_order', $category_order );
    }
}

// Save category order when editing category
add_action( 'edited_category', 'save_category_order' );

// Add custom column to categories list table
add_filter( 'manage_edit-category_columns', 'add_category_order_column' );
function add_category_order_column( $columns ) {
    $columns['category_order'] = __( '順番', 'original-theme' );
    return $columns;
}

// Display category order in custom column
add_action( 'manage_category_custom_column', 'display_category_order_column', 10, 3 );
function display_category_order_column( $content, $column_name, $term_id ) {
    if ( 'category_order' === $column_name ) {
        $category_order = get_term_meta( $term_id, 'category_order', true );
        $content = $category_order ? $category_order : '—';
    }
    return $content;
}

// Make catgory columns unsortable
add_filter( 'manage_edit-category_sortable_columns', 'make_category_order_column_sortable' );
function make_category_order_column_sortable( $columns ) {
    unset($columns['name']);
    unset($columns['description']);
    unset($columns['slug']);
    unset($columns['posts']);
    unset($columns['links']);
    return $columns;
}

// Handle sorting by category order with hierarchy support
add_action( 'parse_term_query', 'sort_categories_by_order' );
function sort_categories_by_order( $query ) {
    if ( ! is_admin() ) {
        return;
    }
    
    $orderby = $query->query_vars['orderby'];
    if ( 'category_order' === $orderby ) {
        // Remove default ordering to handle custom hierarchical sorting
        unset( $query->query_vars['orderby'] );
        unset( $query->query_vars['order'] );
        
        // Add filter to sort results after query
        add_filter( 'get_terms', 'hierarchical_category_sort', 10, 3 );
    }
}

// Custom hierarchical sorting function
function hierarchical_category_sort( $terms, $taxonomies, $args ) {
    // Only apply to category taxonomy in admin when sorting by category_order
    if ( ! is_admin() || ! in_array( 'category', $taxonomies ) || 
         ! isset( $_GET['orderby'] ) || $_GET['orderby'] !== 'category_order' ) {
        return $terms;
    }
    
    // Remove this filter to prevent infinite loops
    remove_filter( 'get_terms', 'hierarchical_category_sort', 10 );
    
    // Build hierarchy tree
    $hierarchy = build_category_hierarchy( $terms );
    
    // Flatten the sorted hierarchy
    $sorted_terms = flatten_sorted_hierarchy( $hierarchy );
    
    return $sorted_terms;
}

// Build category hierarchy with order information
function build_category_hierarchy( $terms ) {
    $hierarchy = array();
    $term_lookup = array();
    
    // Create lookup array and get order meta for all terms
    foreach ( $terms as $term ) {
        $term->order = get_term_meta( $term->term_id, 'category_order', true );
        $term->order = $term->order ? (int) $term->order : 999999; // Default high value for unordered
        $term_lookup[$term->term_id] = $term;
        $term->children = array();
    }
    
    // Build parent-child relationships
    foreach ( $terms as $term ) {
        if ( $term->parent == 0 ) {
            // Root level category
            $hierarchy[] = $term;
        } else {
            // Child category - add to parent's children array
            if ( isset( $term_lookup[$term->parent] ) ) {
                $term_lookup[$term->parent]->children[] = $term;
            }
        }
    }
    
    // Sort each level by order
    usort( $hierarchy, 'compare_category_order' );
    
    foreach ( $hierarchy as $parent ) {
        sort_category_children_recursive( $parent );
    }
    
    return $hierarchy;
}

// Recursively sort children by their order
function sort_category_children_recursive( $category ) {
    if ( ! empty( $category->children ) ) {
        usort( $category->children, 'compare_category_order' );
        
        foreach ( $category->children as $child ) {
            sort_category_children_recursive( $child );
        }
    }
}

// Compare function for category ordering
function compare_category_order( $a, $b ) {
    if ( $a->order == $b->order ) {
        return strcasecmp( $a->name, $b->name ); // Secondary sort by name
    }
    return $a->order < $b->order ? -1 : 1;
}

// Flatten the sorted hierarchy back to a flat array
function flatten_sorted_hierarchy( $hierarchy ) {
    $flattened = array();
    
    foreach ( $hierarchy as $parent ) {
        $flattened[] = $parent;
        flatten_children_recursive( $parent, $flattened );
    }
    
    return $flattened;
}

// Recursively flatten children
function flatten_children_recursive( $category, &$flattened ) {
    if ( ! empty( $category->children ) ) {
        foreach ( $category->children as $child ) {
            $flattened[] = $child;
            flatten_children_recursive( $child, $flattened );
        }
    }
}

// Set default sort order for categories list table
function default_category_order_sort ($query_var_defaults, $taxonomies) {
    if ( is_admin() && isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] === 'category' ) {
        // Only set default if no orderby is specified
        if ( ! isset( $_GET['orderby'] ) ) {
           $query_var_defaults['meta_key'] = 'category_order';
           $query_var_defaults['orderby'] = 'meta_value_num';
           $query_var_defaults['order'] = 'ASC';
        }
    }
    return $query_var_defaults;
}
add_filter('get_terms_defaults','default_category_order_sort',10,3);

// Get categories ordered by custom order field
function get_ordered_categories( $args = array() ) {
    $defaults = array(
        'taxonomy' => 'category',
        'hide_empty' => true,
        'meta_key' => 'category_order',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'category_order',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => 'category_order',
                'compare' => 'NOT EXISTS'
            )
        )
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    // Get categories with order set
    $ordered_categories = get_terms( array_merge( $args, array(
        'meta_query' => array(
            array(
                'key' => 'category_order',
                'compare' => 'EXISTS'
            )
        )
    ) ) );
    
    // Get categories without order set
    $unordered_categories = get_terms( array_merge( $args, array(
        'meta_query' => array(
            array(
                'key' => 'category_order',
                'compare' => 'NOT EXISTS'
            )
        ),
        'orderby' => 'name',
        'order' => 'ASC'
    ) ) );
    
    // Merge ordered categories first, then unordered categories
    return array_merge( $ordered_categories, $unordered_categories );
}

// Build category hierarchy for footer display
function build_footer_category_hierarchy( $categories ) {
    $hierarchy = array();
    $category_lookup = array();
    
    // Create lookup array and get order meta for all categories
    foreach ( $categories as $category ) {
        $category->order = get_term_meta( $category->term_id, 'category_order', true );
        $category->order = $category->order ? (int) $category->order : 999999;
        $category_lookup[$category->term_id] = $category;
        $category->children = array();
    }
    
    // Build parent-child relationships
    foreach ( $categories as $category ) {
        if ( $category->parent == 0 ) {
            // Root level category
            $hierarchy[] = $category;
        } else {
            // Child category - add to parent's children array
            if ( isset( $category_lookup[$category->parent] ) ) {
                $category_lookup[$category->parent]->children[] = $category;
            }
        }
    }
    
    // Sort each level by order
    usort( $hierarchy, 'compare_category_order' );
    
    foreach ( $hierarchy as $parent ) {
        sort_category_children_recursive( $parent );
    }
    
    // Calculate total post counts including descendants
    foreach ( $hierarchy as $parent ) {
        calculate_category_total_posts( $parent );
    }
    
    return $hierarchy;
}

// Calculate total post count for a category including all its descendants
function calculate_category_total_posts( $category ) {
    // Start with the category's own post count
    $total_posts = $category->count;
    
    // Add post counts from all child categories recursively
    if ( ! empty( $category->children ) ) {
        foreach ( $category->children as $child ) {
            $total_posts += calculate_category_total_posts( $child );
        }
    }
    
    // Store the total count
    $category->total_count = $total_posts;
    
    return $total_posts;
}

// Enqueue scripts for drag and drop functionality
add_action( 'admin_enqueue_scripts', 'enqueue_category_order_scripts' );
function enqueue_category_order_scripts( $hook ) {
    // Only load on edit-tags.php page for categories
    if ( 'edit-tags.php' !== $hook || ! isset( $_GET['taxonomy'] ) || 'category' !== $_GET['taxonomy'] ) {
        return;
    }
    
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_script( 
        'category-order-admin', 
        get_template_directory_uri() . '/js/category-order-admin.js', 
        array( 'jquery', 'jquery-ui-sortable' ), 
        _S_VERSION, 
        true 
    );
    
    wp_localize_script( 'category-order-admin', 'categoryOrderAjax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'category_order_nonce' )
    ) );
}

// AJAX handler for updating category order
add_action( 'wp_ajax_update_category_order', 'handle_category_order_update' );
function handle_category_order_update() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'category_order_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    
    // Check user permissions
    if ( ! current_user_can( 'manage_categories' ) ) {
        wp_die( 'Insufficient permissions' );
    }
    
    $category_orders = $_POST['category_orders'];
    
    if ( is_array( $category_orders ) ) {
        foreach ( $category_orders as $term_id => $order ) {
            $term_id = intval( $term_id );
            $order = intval( $order );
            
            if ( $term_id > 0 ) {
                update_term_meta( $term_id, 'category_order', $order );
            }
        }
        
        wp_send_json_success( 'Category order updated successfully' );
    } else {
        wp_send_json_error( 'Invalid data format' );
    }
}

// Add CSS for admin styling
add_action( 'admin_head', 'category_order_admin_css' );
function category_order_admin_css() {
    $screen = get_current_screen();
    if ( $screen && 'edit-category' === $screen->id ) {
        ?>
        <style>
        .term-order-wrap input[type="number"] {
            width: 100px;
        }
        .column-category_order {
            width: 80px;
            text-align: center;
        }
        .wp-list-table.categories .ui-sortable-helper {
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .wp-list-table.categories .ui-sortable-placeholder {
            background: #f0f6fc;
            height: 40px;
        }
        .wp-list-table.categories tbody tr {
            cursor: move;
        }
        .wp-list-table.categories tbody tr:hover {
            background: #f6f7f7;
        }
        .wp-list-table.categories tbody tr.cannot-move {
            cursor: not-allowed;
            opacity: 0.6;
        }
        .wp-list-table.categories tbody tr.child-category {
            position: relative;
        }
        .wp-list-table.categories tbody tr.child-category::before {
            content: "└ ";
            color: #666;
            font-weight: normal;
        }
        .category-order-notice {
            margin: 15px 0;
            padding: 10px;
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            color: #0c5460;
        }
        .hierarchy-warning {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        </style>
        <?php
    }
}
?>