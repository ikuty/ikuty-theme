<?php
/**
 * SEO related functionality
 *
 * meta description, canonical, Open Graph and JSON-LD are output by the
 * Slim SEO plugin. Do not output them here, otherwise they are duplicated.
 *
 * @package original-theme
 */

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
