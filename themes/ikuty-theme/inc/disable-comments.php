<?php
/**
 * Disable comments site-wide.
 *
 * @package original-theme
 */

/**
 * Remove comment and trackback support from all post types.
 */
function original_theme_disable_comments_post_types_support() {
	$post_types = get_post_types();
	foreach ( $post_types as $post_type ) {
		if ( post_type_supports( $post_type, 'comments' ) ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}
}
add_action( 'admin_init', 'original_theme_disable_comments_post_types_support' );

/**
 * Force comments and pings closed on the front end, regardless of
 * per-post settings.
 */
function original_theme_disable_comments_status() {
	return false;
}
add_filter( 'comments_open', 'original_theme_disable_comments_status', 20, 2 );
add_filter( 'pings_open', 'original_theme_disable_comments_status', 20, 2 );

/**
 * Hide any existing comments (including spam already stored in the
 * database) instead of rendering them.
 */
function original_theme_disable_comments_hide_existing( $comments ) {
	return array();
}
add_filter( 'comments_array', 'original_theme_disable_comments_hide_existing', 10, 2 );

/**
 * Remove the "Comments" admin menu item.
 */
function original_theme_disable_comments_admin_menu() {
	remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'original_theme_disable_comments_admin_menu' );

/**
 * Redirect anyone who navigates directly to edit-comments.php.
 */
function original_theme_disable_comments_admin_menu_redirect() {
	global $pagenow;
	if ( 'edit-comments.php' === $pagenow ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}
add_action( 'admin_init', 'original_theme_disable_comments_admin_menu_redirect' );

/**
 * Remove the "Recent Comments" dashboard widget.
 */
function original_theme_disable_comments_dashboard() {
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
}
add_action( 'admin_init', 'original_theme_disable_comments_dashboard' );

/**
 * Remove the comment bubble from the admin bar.
 */
function original_theme_disable_comments_admin_bar() {
	if ( is_admin_bar_showing() ) {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
	}
}
add_action( 'init', 'original_theme_disable_comments_admin_bar' );
