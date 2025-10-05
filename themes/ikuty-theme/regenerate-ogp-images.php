<?php
/**
 * Regenerate OGP images for all posts
 * Run this file via: docker-compose exec wordpress php /var/www/html/wp-content/themes/ikuty-theme/regenerate-ogp-images.php
 */

// Load WordPress
require_once '/var/www/html/wp-load.php';

echo "Starting OGP image regeneration...\n\n";

// Get all published posts
$posts = get_posts( array(
	'post_type' => 'post',
	'post_status' => 'publish',
	'posts_per_page' => -1,
) );

if ( empty( $posts ) ) {
	echo "No posts found.\n";
	exit;
}

$count = 0;
$total = count( $posts );

foreach ( $posts as $post ) {
	echo sprintf( "Processing post #%d: %s\n", $post->ID, $post->post_title );

	// Delete old OGP image if exists
	$old_ogp_id = get_post_meta( $post->ID, '_ogp_image_id', true );
	if ( $old_ogp_id ) {
		wp_delete_attachment( $old_ogp_id, true );
		delete_post_meta( $post->ID, '_ogp_image_id' );
		echo "  - Deleted old OGP image #$old_ogp_id\n";
	}

	// Generate new OGP image
	$result = ikuty_theme_generate_ogp_image( $post->ID );

	if ( $result ) {
		$count++;
		echo sprintf( "  ✓ Generated attachment #%d\n", $result );
	} else {
		echo sprintf( "  ✗ Failed to generate for post #%d\n", $post->ID );
	}

	echo "\n";
}

echo sprintf( "\nCompleted! Generated OGP images for %d out of %d posts.\n", $count, $total );
