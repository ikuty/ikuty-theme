<?php
/**
 * SEO and structured data functionality
 *
 * @package original-theme
 */

/**
 * Add meta tags to head
 */
function original_theme_add_meta_tags() {
    if ( is_single() || is_page() ) {
        global $post;
        
        // Meta description
        $description = '';
        if ( has_excerpt() ) {
            $description = wp_trim_words( strip_tags( get_the_excerpt() ), 30, '' );
        } else {
            $description = wp_trim_words( strip_tags( get_the_content() ), 30, '' );
        }
        
        if ( $description ) {
            echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
        }
        
        // Open Graph tags
        echo '<meta property="og:title" content="' . esc_attr( get_the_title() ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
        
        if ( has_post_thumbnail() ) {
            $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
            if ( $thumbnail ) {
                echo '<meta property="og:image" content="' . esc_url( $thumbnail[0] ) . '">' . "\n";
                echo '<meta property="og:image:width" content="' . esc_attr( $thumbnail[1] ) . '">' . "\n";
                echo '<meta property="og:image:height" content="' . esc_attr( $thumbnail[2] ) . '">' . "\n";
            }
        }
        
        // Twitter Card tags
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( get_the_title() ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
        
        if ( has_post_thumbnail() ) {
            $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
            if ( $thumbnail ) {
                echo '<meta name="twitter:image" content="' . esc_url( $thumbnail[0] ) . '">' . "\n";
            }
        }
        
        // Article specific tags
        if ( is_single() ) {
            echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
            echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
            
            // Article author
            echo '<meta property="article:author" content="' . esc_attr( get_the_author() ) . '">' . "\n";
            
            // Article tags
            $tags = get_the_tags();
            if ( $tags ) {
                foreach ( $tags as $tag ) {
                    echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . '">' . "\n";
                }
            }
            
            // Article section (category)
            $categories = get_the_category();
            if ( $categories ) {
                echo '<meta property="article:section" content="' . esc_attr( $categories[0]->name ) . '">' . "\n";
            }
        }
        
    } elseif ( is_home() || is_front_page() ) {
        $description = get_bloginfo( 'description' );
        if ( $description ) {
            echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
        }
        
        echo '<meta property="og:title" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( home_url() ) . '">' . "\n";
        
    } elseif ( is_category() || is_tag() || is_archive() ) {
        $description = strip_tags( term_description() );
        if ( ! $description ) {
            $description = sprintf( '%s の記事一覧', single_term_title( '', false ) );
        }
        
        echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( single_term_title( '', false ) ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
    }
}
add_action( 'wp_head', 'original_theme_add_meta_tags' );

/**
 * Add JSON-LD structured data
 */
function original_theme_add_structured_data() {
    if ( is_single() ) {
        global $post;
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'description' => wp_trim_words( strip_tags( get_the_content() ), 30, '' ),
            'datePublished' => get_the_date( 'c' ),
            'dateModified' => get_the_modified_date( 'c' ),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author(),
                'url' => get_author_posts_url( get_the_author_meta( 'ID' ) )
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo( 'name' ),
                'url' => home_url()
            ),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink()
            )
        );
        
        // Add featured image
        if ( has_post_thumbnail() ) {
            $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
            if ( $thumbnail ) {
                $schema['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $thumbnail[0],
                    'width' => $thumbnail[1],
                    'height' => $thumbnail[2]
                );
            }
        }
        
        // Add categories as keywords
        $categories = get_the_category();
        if ( $categories ) {
            $keywords = array();
            foreach ( $categories as $category ) {
                $keywords[] = $category->name;
            }
            $schema['keywords'] = implode( ', ', $keywords );
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        
    } elseif ( is_home() || is_front_page() ) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo( 'name' ),
            'description' => get_bloginfo( 'description' ),
            'url' => home_url(),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo( 'name' ),
                'url' => home_url()
            )
        );
        
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }
}
add_action( 'wp_head', 'original_theme_add_structured_data' );

/**
 * Calculate estimated reading time
 */
function original_theme_get_reading_time( $content = '' ) {
    if ( empty( $content ) ) {
        $content = get_the_content();
    }
    
    $word_count = str_word_count( strip_tags( $content ) );
    $reading_time = ceil( $word_count / 200 ); // 200 words per minute
    
    return max( 1, $reading_time ); // Minimum 1 minute
}

/**
 * Add canonical URL
 */
function original_theme_add_canonical() {
    if ( is_single() || is_page() ) {
        echo '<link rel="canonical" href="' . esc_url( get_permalink() ) . '">' . "\n";
    } elseif ( is_category() || is_tag() || is_archive() ) {
        echo '<link rel="canonical" href="' . esc_url( get_term_link( get_queried_object() ) ) . '">' . "\n";
    } elseif ( is_home() || is_front_page() ) {
        echo '<link rel="canonical" href="' . esc_url( home_url() ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'original_theme_add_canonical' );

/**
 * Add robots meta tag
 */
function original_theme_add_robots_meta() {
    if ( is_search() || is_404() ) {
        echo '<meta name="robots" content="noindex, nofollow">' . "\n";
    } elseif ( is_archive() && ! is_category() && ! is_tag() ) {
        echo '<meta name="robots" content="noindex, follow">' . "\n";
    }
}
add_action( 'wp_head', 'original_theme_add_robots_meta' );
?>