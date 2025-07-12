<?php
/**
 * Breadcrumb navigation for technical categories
 *
 * @package original-theme
 */

function original_theme_breadcrumbs() {
    // Don't display on homepage
    if ( is_home() || is_front_page() ) {
        return;
    }
    
    $separator = ' <span class="breadcrumb-separator">›</span> ';
    $home_title = 'ホーム';
    
    echo '<nav class="breadcrumbs" aria-label="Breadcrumb navigation">';
    echo '<div class="breadcrumb-container">';
    
    // Home link
    echo '<a href="' . home_url() . '" class="breadcrumb-home">' . $home_title . '</a>';
    
    if ( is_category() ) {
        echo $separator;
        $category = get_queried_object();
        
        // Show parent categories
        if ( $category->parent != 0 ) {
            $parent_categories = array();
            $parent = $category->parent;
            
            while ( $parent ) {
                $parent_cat = get_category( $parent );
                $parent_categories[] = $parent_cat;
                $parent = $parent_cat->parent;
            }
            
            $parent_categories = array_reverse( $parent_categories );
            
            foreach ( $parent_categories as $parent_cat ) {
                echo '<a href="' . get_category_link( $parent_cat->term_id ) . '" class="breadcrumb-category">';
                echo esc_html( $parent_cat->name );
                echo '</a>' . $separator;
            }
        }
        
        echo '<span class="breadcrumb-current">' . esc_html( $category->name ) . '</span>';
        
    } elseif ( is_tag() ) {
        echo $separator;
        echo '<span class="breadcrumb-current">タグ: ' . esc_html( single_tag_title( '', false ) ) . '</span>';
        
    } elseif ( is_single() ) {
        echo $separator;
        
        // Show categories for posts
        $categories = get_the_category();
        if ( ! empty( $categories ) ) {
            $category = $categories[0]; // Use first category
            
            // Show parent categories
            if ( $category->parent != 0 ) {
                $parent_categories = array();
                $parent = $category->parent;
                
                while ( $parent ) {
                    $parent_cat = get_category( $parent );
                    $parent_categories[] = $parent_cat;
                    $parent = $parent_cat->parent;
                }
                
                $parent_categories = array_reverse( $parent_categories );
                
                foreach ( $parent_categories as $parent_cat ) {
                    echo '<a href="' . get_category_link( $parent_cat->term_id ) . '" class="breadcrumb-category">';
                    echo esc_html( $parent_cat->name );
                    echo '</a>' . $separator;
                }
            }
            
            echo '<a href="' . get_category_link( $category->term_id ) . '" class="breadcrumb-category">';
            echo esc_html( $category->name );
            echo '</a>' . $separator;
        }
        
        echo '<span class="breadcrumb-current">' . get_the_title() . '</span>';
        
    } elseif ( is_page() ) {
        echo $separator;
        
        // Show parent pages
        if ( $post = get_post() ) {
            if ( $post->post_parent ) {
                $parent_pages = array();
                $parent_id = $post->post_parent;
                
                while ( $parent_id ) {
                    $parent_page = get_post( $parent_id );
                    $parent_pages[] = $parent_page;
                    $parent_id = $parent_page->post_parent;
                }
                
                $parent_pages = array_reverse( $parent_pages );
                
                foreach ( $parent_pages as $parent_page ) {
                    echo '<a href="' . get_permalink( $parent_page->ID ) . '" class="breadcrumb-page">';
                    echo esc_html( $parent_page->post_title );
                    echo '</a>' . $separator;
                }
            }
        }
        
        echo '<span class="breadcrumb-current">' . get_the_title() . '</span>';
        
    } elseif ( is_search() ) {
        echo $separator;
        echo '<span class="breadcrumb-current">検索結果: "' . esc_html( get_search_query() ) . '"</span>';
        
    } elseif ( is_archive() ) {
        echo $separator;
        echo '<span class="breadcrumb-current">' . get_the_archive_title() . '</span>';
    }
    
    echo '</div>';
    echo '</nav>';
}
?>