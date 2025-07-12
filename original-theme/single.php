<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package original-theme
 */

get_header();
?>

	<!-- Floating Banner for Single Posts -->
	<div class="floating-banner">
		<div class="floating-banner-item">
			<a href="https://twitter.com/intent/tweet?text=<?php echo urlencode(get_the_title()); ?>&url=<?php echo urlencode(get_permalink()); ?>&via=tw_ikuty" target="_blank" rel="noopener noreferrer">
				<div class="banner-placeholder">
					<img src="<?php echo get_template_directory_uri(); ?>/img/x.png" alt="X share" />
				</div>
			</a>
		</div>
		<div class="floating-banner-item">
			<a href="https://bsky.app/intent/compose?text=<?php echo urlencode(get_the_title()); echo urlencode(get_permalink());?>" target="_blank" rel="noopener noreferrer">
				<div class="banner-placeholder">
					<img src="<?php echo get_template_directory_uri(); ?>/img/bsky.png" alt="Bluesky share" />
				</div>
			</a>
		</div>
		<div class="floating-banner-item">
			<a href="https://www.facebook.com/share.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" rel="noopener noreferrer">
				<div class="banner-placeholder">
					<img src="<?php echo get_template_directory_uri(); ?>/img/fb.png" alt="Facebook share" />
				</div>
			</a>
		</div>
		<div class="floating-banner-item">
			<a href="http://b.hatena.ne.jp/add?mode=confirm&url=<?php echo urlencode(get_permalink()); ?>&title=<?php echo urlencode(get_permalink()); ?>" target="_blank" rel="noopener noreferrer">
				<div class="banner-placeholder">
					<img src="<?php echo get_template_directory_uri(); ?>/img/hatena.png" alt="Hatena bookmark share" />
				</div>
			</a>
		</div>
	</div>

	<div class="container">
		<div class="content-wrapper">
			<div class="content-area">
				<main id="primary" class="site-main">

					<?php
					while ( have_posts() ) :
						the_post();

						get_template_part( 'template-parts/content', get_post_type() );

						// Related posts
						get_template_part( 'template-parts/related-posts' );

						the_post_navigation(
							array(
								'prev_text' => '<span class="nav-subtitle">' . esc_html__( 'Previous:', 'original-theme' ) . '</span> <span class="nav-title">%title</span>',
								'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next:', 'original-theme' ) . '</span> <span class="nav-title">%title</span>',
							)
						);


					endwhile; // End of the loop.
					?>

				</main><!-- #main -->
			</div><!-- .content-area -->
			
			<?php get_sidebar(); ?>
			
		</div><!-- .content-wrapper -->
	</div><!-- .container -->

<?php
get_footer();
