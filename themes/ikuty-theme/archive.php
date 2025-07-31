<?php
/**
 * The template for displaying archive pages
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
				<main id="primary" class="site-main">

					<?php if ( have_posts() ) : ?>

						<div class="post-list-widget">
							<header class="page-header">
								<?php
								if ( is_tag() ) {
									echo '<h1 class="page-title">「' . single_tag_title( '', false ) . '」一覧</h1>';
								} else {
									the_archive_title( '<h1 class="page-title">', '</h1>' );
								}
								the_archive_description( '<div class="archive-description">', '</div>' );
								?>
							</header><!-- .page-header -->
							
							<?php
							// Display tags list with custom order (only top page display tags)
							$tags = get_tags( array(
								'hide_empty' => true,
							) );
							
							if ( ! empty( $tags ) ) :
							?>
								<div class="tags-list-section">
									<div class="tags-container">
										<?php foreach ( $tags as $tag ) : ?>
											<a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="tag-item">
												<?php echo esc_html( $tag->name ); ?>
												<span class="tag-count">(<?php echo esc_html( $tag->count ); ?>)</span>
											</a>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
							
							<?php
							/* Start the Loop */
							while ( have_posts() ) :
								the_post();
								?>
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
								<?php
							endwhile;
							?>

							<?php
							// Custom pagination
							$big = 999999999;
							$pagination = paginate_links( array(
								'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
								'format' => '?paged=%#%',
								'current' => max( 1, get_query_var('paged') ),
								'total' => $wp_query->max_num_pages,
								'prev_text' => '前へ',
								'next_text' => '次へ',
								'type' => 'array',
								'show_all' => false,
								'end_size' => 2,
								'mid_size' => 2,
							) );
							
							if ( $pagination ) :
							?>
								<nav class="pagination-wrapper">
									<div class="pagination">
										<?php foreach ( $pagination as $link ) : ?>
											<span class="page-item"><?php echo $link; ?></span>
										<?php endforeach; ?>
									</div>
								</nav>
							<?php endif; ?>
						</div>

					<?php else : ?>

						<?php get_template_part( 'template-parts/content', 'none' ); ?>

					<?php endif;
					?>

				</main><!-- #main -->
			</div><!-- .content-area -->
			
			<?php get_sidebar(); ?>
			
		</div><!-- .content-wrapper -->
	</div><!-- .container -->

<?php
get_footer();
