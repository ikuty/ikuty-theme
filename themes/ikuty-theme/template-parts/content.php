<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package original-theme
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php
		// Display categories above title for single posts
		if ( is_singular() && 'post' === get_post_type() ) :
			$categories = get_the_category();
			if ( ! empty( $categories ) ) :
				?>
				<div class="entry-categories">
					<?php foreach ( $categories as $category ) : ?>
						<span class="entry-category">
							<?php echo esc_html( $category->name ); ?>
						</span>
					<?php endforeach; ?>
				</div>
				<?php
			endif;
		endif;
		
		if ( is_singular() ) :
			the_title( '<h1 class="entry-title">', '</h1>' );
		else :
			the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		endif;

		if ( 'post' === get_post_type() ) :
			?>
			<div class="entry-meta">
				<div class="entry-meta-left">
					<?php original_theme_posted_on(); ?>
				</div>
				<div class="entry-meta-right">
					<?php
					// Display categories
					$categories_list = get_the_category_list( ', ' );
					if ( $categories_list ) {
						echo '<span class="cat-links">カテゴリ: ' . $categories_list . '</span>';
					}
					
					// Display tags
					$tags_list = get_the_tag_list( '', ', ' );
					if ( $tags_list ) {
						echo '<span class="tags-links">タグ: ' . $tags_list . '</span>';
					}
					?>
				</div>
			</div><!-- .entry-meta -->
		<?php endif; ?>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php
		the_content(
			sprintf(
				wp_kses(
					/* translators: %s: Name of current post. Only visible to screen readers */
					__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'original-theme' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				),
				wp_kses_post( get_the_title() )
			)
		);

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'original-theme' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
	</footer><!-- .entry-footer -->
</article><!-- #post-<?php the_ID(); ?> -->
