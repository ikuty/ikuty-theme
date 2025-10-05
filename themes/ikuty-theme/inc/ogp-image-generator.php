<?php
/**
 * OGP Image Generator
 *
 * Automatically generates OGP images with post titles on base image
 *
 * @package ikuty-theme
 */

/**
 * Generate OGP image for a post
 *
 * @param int $post_id Post ID
 * @return int|false Attachment ID on success, false on failure
 */
function ikuty_theme_generate_ogp_image( $post_id ) {
	// Get post data
	$post = get_post( $post_id );
	if ( ! $post || $post->post_status !== 'publish' || $post->post_type !== 'post' ) {
		return false;
	}

	$title = get_the_title( $post_id );
	if ( empty( $title ) ) {
		return false;
	}

	// File paths
	$base_image_path = get_template_directory() . '/img/ogpbase.png';
	$font_path = get_template_directory() . '/img/fonts/MintMono-Regular.ttf';

	// Verify files exist
	if ( ! file_exists( $base_image_path ) || ! file_exists( $font_path ) ) {
		error_log( 'OGP Image Generator: Base image or font file not found' );
		return false;
	}

	// Load base image
	$image = imagecreatefrompng( $base_image_path );
	if ( ! $image ) {
		error_log( 'OGP Image Generator: Failed to load base image' );
		return false;
	}

	// Get image dimensions
	$img_width = imagesx( $image );
	$img_height = imagesy( $image );

	// Text settings
	$font_size = 60;
	$text_color = imagecolorallocate( $image, 0, 0, 0 ); // Black
	$margin_horizontal = 192; // 192px margin on each side
	$max_width = $img_width - ( $margin_horizontal * 2 );
	$line_height = $font_size * 1.5;

	// Word wrap title to fit within max width (supports Japanese)
	$lines = array();
	$current_line = '';
	$chars = preg_split( '//u', $title, -1, PREG_SPLIT_NO_EMPTY );

	foreach ( $chars as $char ) {
		$test_line = $current_line . $char;
		$bbox = imagettfbbox( $font_size, 0, $font_path, $test_line );
		$text_width = $bbox[2] - $bbox[0];

		if ( $text_width > $max_width && $current_line !== '' ) {
			$lines[] = $current_line;
			$current_line = $char;
		} else {
			$current_line = $test_line;
		}
	}
	if ( $current_line !== '' ) {
		$lines[] = $current_line;
	}

	// Limit to 4 lines max
	if ( count( $lines ) > 4 ) {
		$lines = array_slice( $lines, 0, 4 );
		$lines[3] = rtrim( $lines[3], '.' ) . '...';
	}

	// Calculate total text height
	$total_height = count( $lines ) * $line_height;

	// Starting Y position (center vertically)
	$start_y = ( $img_height - $total_height ) / 2 + $font_size;

	// Draw each line with left margin
	foreach ( $lines as $index => $line ) {
		$x = $margin_horizontal;
		$y = $start_y + ( $index * $line_height );

		imagettftext( $image, $font_size, 0, $x, $y, $text_color, $font_path, $line );
	}

	// Draw @ikuty.com at bottom left
	$site_name = '@ikuty.com';
	$site_font_size = 38;
	$margin_bottom = 100;
	$site_x = $margin_horizontal;
	$site_y = $img_height - $margin_bottom;
	imagettftext( $image, $site_font_size, 0, $site_x, $site_y, $text_color, $font_path, $site_name );

	// Create temporary file
	$upload_dir = wp_upload_dir();
	$temp_file = $upload_dir['path'] . '/ogp-' . $post_id . '-' . time() . '.png';

	// Save image
	$saved = imagepng( $image, $temp_file, 9 );
	imagedestroy( $image );

	if ( ! $saved ) {
		error_log( 'OGP Image Generator: Failed to save generated image' );
		return false;
	}

	// Prepare for media library upload
	$file_array = array(
		'name' => 'ogp-' . sanitize_title( $title ) . '.png',
		'tmp_name' => $temp_file,
	);

	// Upload to media library
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	$attachment_id = media_handle_sideload( $file_array, $post_id, 'OGP Image: ' . $title );

	// Clean up temp file if it still exists
	if ( file_exists( $temp_file ) ) {
		@unlink( $temp_file );
	}

	if ( is_wp_error( $attachment_id ) ) {
		error_log( 'OGP Image Generator: Failed to upload to media library - ' . $attachment_id->get_error_message() );
		return false;
	}

	// Save attachment ID to post meta
	update_post_meta( $post_id, '_ogp_image_id', $attachment_id );

	return $attachment_id;
}

/**
 * Auto-generate OGP image on post save
 *
 * @param int $post_id Post ID
 * @param WP_Post $post Post object
 * @param bool $update Whether this is an existing post being updated
 */
function ikuty_theme_auto_generate_ogp_image( $post_id, $post, $update ) {
	// Check if it's an autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Skip revisions
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Check post type
	if ( get_post_type( $post_id ) !== 'post' ) {
		return;
	}

	// Only process published posts
	if ( $post->post_status !== 'publish' ) {
		return;
	}

	// Check user permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Delete old OGP image if exists
	$existing_ogp_id = get_post_meta( $post_id, '_ogp_image_id', true );
	if ( $existing_ogp_id && wp_attachment_is_image( $existing_ogp_id ) ) {
		wp_delete_attachment( $existing_ogp_id, true );
		delete_post_meta( $post_id, '_ogp_image_id' );
	}

	// Generate OGP image
	ikuty_theme_generate_ogp_image( $post_id );
}
add_action( 'save_post', 'ikuty_theme_auto_generate_ogp_image', 10, 3 );

/**
 * WP-CLI Commands for OGP Image Generation
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * OGP Image generator commands
	 */
	class OGP_Image_CLI_Command {

		/**
		 * Generate OGP images for all posts without them
		 *
		 * ## EXAMPLES
		 *
		 *     wp ogp-image generate-all
		 *
		 * @when after_wp_load
		 */
		public function generate_all( $args, $assoc_args ) {
			$posts = get_posts( array(
				'post_type' => 'post',
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' => '_ogp_image_id',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key' => '_ogp_image_id',
						'value' => '',
						'compare' => '=',
					),
				),
			) );

			if ( empty( $posts ) ) {
				WP_CLI::success( 'No posts need OGP image generation.' );
				return;
			}

			$count = 0;
			$total = count( $posts );

			foreach ( $posts as $post ) {
				WP_CLI::log( sprintf( 'Generating OGP image for post #%d: %s', $post->ID, $post->post_title ) );

				$result = ikuty_theme_generate_ogp_image( $post->ID );

				if ( $result ) {
					$count++;
					WP_CLI::log( sprintf( '  ✓ Generated attachment #%d', $result ) );
				} else {
					WP_CLI::warning( sprintf( '  ✗ Failed to generate for post #%d', $post->ID ) );
				}
			}

			WP_CLI::success( sprintf( 'Generated OGP images for %d out of %d posts.', $count, $total ) );
		}

		/**
		 * Regenerate OGP images for all posts
		 *
		 * ## EXAMPLES
		 *
		 *     wp ogp-image regenerate-all
		 *
		 * @when after_wp_load
		 */
		public function regenerate_all( $args, $assoc_args ) {
			$posts = get_posts( array(
				'post_type' => 'post',
				'post_status' => 'publish',
				'posts_per_page' => -1,
			) );

			if ( empty( $posts ) ) {
				WP_CLI::success( 'No posts found.' );
				return;
			}

			$count = 0;
			$total = count( $posts );

			foreach ( $posts as $post ) {
				WP_CLI::log( sprintf( 'Regenerating OGP image for post #%d: %s', $post->ID, $post->post_title ) );

				// Delete old OGP image if exists
				$old_ogp_id = get_post_meta( $post->ID, '_ogp_image_id', true );
				if ( $old_ogp_id ) {
					wp_delete_attachment( $old_ogp_id, true );
					delete_post_meta( $post->ID, '_ogp_image_id' );
				}

				$result = ikuty_theme_generate_ogp_image( $post->ID );

				if ( $result ) {
					$count++;
					WP_CLI::log( sprintf( '  ✓ Generated attachment #%d', $result ) );
				} else {
					WP_CLI::warning( sprintf( '  ✗ Failed to regenerate for post #%d', $post->ID ) );
				}
			}

			WP_CLI::success( sprintf( 'Regenerated OGP images for %d out of %d posts.', $count, $total ) );
		}
	}

	WP_CLI::add_command( 'ogp-image', 'OGP_Image_CLI_Command' );
}
