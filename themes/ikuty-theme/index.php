<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package original-theme
 */

get_header();
?>

	<div class="container">
		<div class="content-wrapper">
			<div class="content-area">
				<main id="primary" class="site-main-top">

				<?php
				if ( have_posts() ) :

					if ( is_home() && ! is_front_page() ) :
						?>
						<header class="page-header">
							<h1 class="page-title"><?php single_post_title(); ?></h1>
						</header>
						<?php
					endif;

					?>

					<div class="posts-container">
						<?php
							// Get tags for top page display (only those with show_on_top flag)
							$tags = get_top_page_tags( array(
								'hide_empty' => true
							) );
							
							foreach ( $tags as $tag ) {
								$tagged_posts = new WP_Query( array(
									'tag' => $tag->slug,
									'posts_per_page' => 3,
									'post_status' => 'publish'
								) );
								
								if ( $tagged_posts->have_posts() ) :
									// Display tag name as section header
									echo '<div class="tag-section">';
									echo '<div class="tag-section-header">';
									echo '<p class="subject">' . esc_html( $tag->name ) . '</p>';
									echo '<p class="link">' . '<a href="' . esc_url( get_tag_link( $tag->term_id ) ) . '" class="tag-more-link">もっと見る</a>' . '</p>';
									echo '</div>';
									echo '<div class="posts-grid">';
									
									$num_posts = 0;
									while ( $tagged_posts->have_posts() ) : $tagged_posts->the_post();
										get_template_part( 'template-parts/content', 'grid' );
										$num_posts++;
									endwhile;
									
									// Fill empty spaces in row if less than 3 posts
									for ( $i = $num_posts; $i < 3; $i++ ) {
										echo '<article class="grid-item grid-item-empty"></article>';
									}
									
									echo '</div><!-- .posts-grid -->';
									echo '</div><!-- .tag-section -->';
									wp_reset_postdata();
								endif;
							}
						?>
					</div><!-- .posts-container -->
					<?php

				else :

					get_template_part( 'template-parts/content', 'none' );

				endif;
				?>

			</main><!-- #main -->
			</div><!-- .content-area -->
			
			<?php get_sidebar(); ?>
			
		</div><!-- .content-wrapper -->
	</div><!-- .container -->

<?php
get_footer();
