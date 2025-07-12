<?php
/**
 * The sidebar containing the main widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package original-theme
 */

if ( ! is_active_sidebar( 'sidebar-1' ) && ! is_active_sidebar( 'sidebar-pickup' ) ) {
	return;
}
?>

<aside id="secondary" class="widget-area">
	<div class="sidebar-container">
		
		<?php if ( is_active_sidebar( 'sidebar-pickup' ) ) : ?>
			<div class="sidebar-section pickup-section">
				<?php dynamic_sidebar( 'sidebar-pickup' ); ?>
			</div>
		<?php endif; ?>
		
		<?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>
			<div class="sidebar-section main-sidebar">
				<?php dynamic_sidebar( 'sidebar-1' ); ?>
			</div>
		<?php endif; ?>
		
	</div><!-- .sidebar-container -->
</aside><!-- #secondary -->
