<?php
/**
 * Template part for displaying posts in grid layout
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package original-theme
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'grid-item' ); ?>>
	<div class="grid-item-inner">
		<div class="grid-item-thumbnail">
			<a href="<?php echo esc_url( get_permalink() ); ?>" aria-hidden="true" tabindex="-1">
				<?php
				the_post_thumbnail( 'grid-thumbnail', array(
					'alt' => the_title_attribute( array(
						'echo' => false,
					) ),
					'loading' => 'lazy',
				) );
				?>
			</a>
		</div><!-- .grid-item-thumbnail -->

		<div class="grid-item-content">
			<header class="entry-header">
				<?php
				if ( is_singular() ) :
					the_title( '<h1 class="entry-title">', '</h1>' );
				else :
					the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
				endif;
				?>
			</header><!-- .entry-header -->

			<div class="entry-summary">
				<?php
				if ( has_excerpt() ) {
					the_excerpt();
				} else {
					echo '<p>' . wp_filter_nohtml_kses(get_the_content()) . '</p>';
				}
				?>
			</div><!-- .entry-summary -->

		</div><!-- .grid-item-content -->
	</div><!-- .grid-item-inner -->
</article><!-- #post-<?php the_ID(); ?> -->