<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package original-theme
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'original-theme' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="header-container">
			<div class="site-branding">
				<?php
				if ( is_front_page() && is_home() ) :
					?>
					<h1 class="site-title">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<img src="<?php echo get_template_directory_uri(); ?>/img/ikutycom.png" alt="<?php bloginfo( 'name' ); ?>" class="site-logo">
						</a>
					</h1>
					<?php
				else :
					?>
					<div class="site-title">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<img src="<?php echo get_template_directory_uri(); ?>/img/ikutycom.png" alt="<?php bloginfo( 'name' ); ?>" class="site-logo">
						</a>
					</div>
					<?php
				endif;
				?>
			</div><!-- .site-branding -->

			<nav id="site-navigation" class="main-navigation">
				<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
					<span class="menu-toggle-text"><?php esc_html_e( 'Menu', 'original-theme' ); ?></span>
					<span class="menu-toggle-icon">
						<span></span>
						<span></span>
						<span></span>
					</span>
				</button>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-1',
						'menu_id'        => 'primary-menu',
						'container'      => false,
						'menu_class'     => 'primary-menu-list',
					)
				);
				?>
			</nav><!-- #site-navigation -->
		</div><!-- .header-container -->
	</header><!-- #masthead -->
	
	<?php
	// Display breadcrumbs on all pages except home
	if ( ! is_home() && ! is_front_page() && function_exists( 'original_theme_breadcrumbs' ) ) {
		original_theme_breadcrumbs();
	}
	?>
