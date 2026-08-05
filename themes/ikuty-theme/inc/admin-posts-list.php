<?php
/**
 * Make the admin Posts list table denser and give the title column
 * back the space that custom columns (views, etc.) squeeze out of it.
 *
 * @package original-theme
 */

function original_theme_admin_posts_list_css() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-post' !== $screen->id ) {
		return;
	}
	?>
	<style>
	/* table-layout:auto lets the title column claim whatever space the
	   narrow, short-content columns below don't need. */
	.wp-list-table.posts {
		table-layout: auto;
	}
	.wp-list-table.posts .check-column {
		width: 2em;
	}
	.wp-list-table.posts .column-author,
	.wp-list-table.posts .column-date,
	.wp-list-table.posts .column-post_views {
		width: 8%;
		white-space: nowrap;
	}
	.wp-list-table.posts .column-comments {
		width: 3em;
	}

	/* Maximize information density: shrink font size, line height,
	   margins and padding as far as they'll reasonably go. */
	.wp-list-table.posts th,
	.wp-list-table.posts td {
		font-size: 11px !important;
		line-height: 1.3 !important;
		padding: 3px 6px !important;
	}
	.wp-list-table.posts thead th,
	.wp-list-table.posts tfoot th {
		padding: 4px 6px !important;
	}
	.wp-list-table.posts .row-actions {
		font-size: 11px;
		padding-top: 0;
		margin: 0;
	}
	.wp-list-table.posts p {
		margin: 0;
	}
	</style>
	<?php
}
add_action( 'admin_head-edit.php', 'original_theme_admin_posts_list_css' );
