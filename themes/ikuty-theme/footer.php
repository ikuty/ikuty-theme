<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package original-theme
 */

?>

	<footer id="colophon" class="site-footer">
		<div class="footer-container">
			<div class="footer-categories">
				<?php
				// Get categories ordered by custom order field
				$categories = get_ordered_categories( array(
					'hide_empty' => false
				) );
				
				// Build category hierarchy
				$category_hierarchy = build_footer_category_hierarchy( $categories );
				
				// Display category trees
				if ( ! empty( $category_hierarchy ) ) :
				?>
					<div class="category-trees">
						<?php foreach ( $category_hierarchy as $parent_category ) : ?>
							<div class="category-tree">
								<ul class="category-list">
									<li class="footer-category-item parent-category">
										<a href="<?php echo esc_url( get_category_link( $parent_category->term_id ) ); ?>" class="category-link">
											<?php echo esc_html( $parent_category->name ); ?>
											<span class="category-count">(<?php echo esc_html( $parent_category->total_count ); ?>)</span>
										</a>
									</li>
									<?php if ( ! empty( $parent_category->children ) ) : ?>
										<?php foreach ( $parent_category->children as $child_category ) : ?>
											<li class="footer-category-item child-category">
												<a href="<?php echo esc_url( get_category_link( $child_category->term_id ) ); ?>" class="category-link">
													<?php echo esc_html( $child_category->name ); ?>
													<span class="category-count">(<?php echo esc_html( $child_category->total_count ); ?>)</span>
												</a>
											</li>
											<?php if ( ! empty( $child_category->children ) ) : ?>
												<?php foreach ( $child_category->children as $grandchild_category ) : ?>
													<li class="footer-category-item grandchild-category">
														<a href="<?php echo esc_url( get_category_link( $grandchild_category->term_id ) ); ?>" class="category-link">
															<?php echo esc_html( $grandchild_category->name ); ?>
															<span class="category-count">(<?php echo esc_html( $grandchild_category->count ); ?>)</span>
														</a>
													</li>
												<?php endforeach; ?>
											<?php endif; ?>
										<?php endforeach; ?>
									<?php endif; ?>
								</ul>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div><!-- .footer-categories -->
			
			<div class="footer-info">
				<div class="site-info">
					<p>&copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
				</div><!-- .site-info -->
				
				<?php if ( has_nav_menu( 'footer-menu' ) ) : ?>
					<nav class="footer-navigation">
						<?php
						wp_nav_menu(
							array(
								'theme_location' => 'footer-menu',
								'menu_id'        => 'footer-menu',
								'container'      => false,
								'menu_class'     => 'footer-menu-list',
								'depth'          => 1,
							)
						);
						?>
					</nav>
				<?php endif; ?>
			</div><!-- .footer-info -->
		</div><!-- .footer-container -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
