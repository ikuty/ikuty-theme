<?php
/**
 * Template part for displaying related posts based on tags
 *
 * @package original-theme
 */

// Get current post tags
$post_tags = wp_get_post_tags( get_the_ID() );

if ( ! empty( $post_tags ) ) {
    $tag_ids = array();
    foreach ( $post_tags as $tag ) {
        $tag_ids[] = $tag->term_id;
    }
    
    // Query for related posts
    $related_posts = new WP_Query( array(
        'tag__in' => $tag_ids,
        'post__not_in' => array( get_the_ID() ),
        'posts_per_page' => 3,
        'orderby' => 'rand',
        'post_status' => 'publish'
    ) );
    
    if ( $related_posts->have_posts() ) : ?>
        <section class="related-posts">
            <h3 class="related-posts-title">関連記事</h3>
            <div class="post-list-widget">
                <?php while ( $related_posts->have_posts() ) : $related_posts->the_post(); ?>
                    <article class="post-list-item">
                        <div class="post-list-thumbnail">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'square-360', array( 'loading' => 'lazy' ) ); ?>
                            </a>
                        </div>
                        <div class="post-list-content">
                            <h3 class="post-list-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            <div class="post-list-excerpt">
                                <?php
                                if ( has_excerpt() ) {
                                    the_excerpt();
                                } else {
                                    echo '<p>' . wp_filter_nohtml_kses(get_the_content()) . '</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>
        <?php
        wp_reset_postdata();
    endif;
}
?>