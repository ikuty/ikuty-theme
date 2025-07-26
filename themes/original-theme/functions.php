<?php
/**
 * original-theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package original-theme
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function original_theme_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on original-theme, use a find and replace
		* to change 'original-theme' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'original-theme', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );
	
	// Add custom image sizes for optimization
	add_image_size( 'grid-thumbnail', 400, 225, true ); // 16:9 for grid items
	add_image_size( 'pickup-thumbnail', 120, 120, true ); // Square for pickup widgets
	add_image_size( 'related-thumbnail', 300, 169, true ); // 16:9 for related posts
	add_image_size( 'square-360', 360, 360, true ); // 360x360 square hard crop

	// This theme uses wp_nav_menu() in multiple locations.
	register_nav_menus(
		array(
			'menu-1'     => esc_html__( 'Primary', 'original-theme' ),
			'footer-menu' => esc_html__( 'Footer Menu', 'original-theme' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'original_theme_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );
	
	// Add theme support for responsive embeds
	add_theme_support( 'responsive-embeds' );
	
	// Add theme support for editor styles
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style-editor.css' );
	
	// Add theme support for align wide and full
	add_theme_support( 'align-wide' );
	
	// Add theme support for block editor color palette
	add_theme_support( 'editor-color-palette', array(
		array(
			'name'  => __( 'Primary Blue', 'original-theme' ),
			'slug'  => 'primary-blue',
			'color' => '#3498db',
		),
		array(
			'name'  => __( 'Dark Blue Gray', 'original-theme' ),
			'slug'  => 'dark-blue-gray',
			'color' => '#2c3e50',
		),
		array(
			'name'  => __( 'Light Gray', 'original-theme' ),
			'slug'  => 'light-gray',
			'color' => '#7f8c8d',
		),
		array(
			'name'  => __( 'Background Gray', 'original-theme' ),
			'slug'  => 'background-gray',
			'color' => '#fafbfc',
		),
	) );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'original_theme_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function original_theme_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'original_theme_content_width', 640 );
}
add_action( 'after_setup_theme', 'original_theme_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function original_theme_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Main Sidebar', 'original-theme' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'original-theme' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
	
	register_sidebar(
		array(
			'name'          => esc_html__( 'Pickup Section', 'original-theme' ),
			'id'            => 'sidebar-pickup',
			'description'   => esc_html__( 'Featured content sections like Daily Learning, Deep Learning, etc.', 'original-theme' ),
			'before_widget' => '<section id="%1$s" class="widget pickup-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="pickup-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'original_theme_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function original_theme_scripts() {
	wp_enqueue_style( 'original-theme-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'original-theme-style', 'rtl', 'replace' );

	// Conditionally load Prism.js only on single posts with code blocks
	if ( is_single() || is_page() ) {
		// Check if content has code blocks
		global $post;
		if ( $post && ( strpos( $post->post_content, '<pre' ) !== false || strpos( $post->post_content, '<code' ) !== false ) ) {
			wp_enqueue_style( 'prism-css', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css', array(), '1.29.0' );
			wp_enqueue_script( 'prism-js', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js', array(), '1.29.0', true );
			wp_enqueue_script( 'prism-autoloader', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js', array('prism-js'), '1.29.0', true );
			wp_enqueue_script( 'prism-copy', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/copy-to-clipboard/prism-copy-to-clipboard.min.js', array('prism-js'), '1.29.0', true );
			wp_enqueue_script( 'prism-line-numbers', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js', array('prism-js'), '1.29.0', true );
			wp_enqueue_style( 'prism-line-numbers-css', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css', array('prism-css'), '1.29.0' );
		}
	}

	wp_enqueue_script( 'original-theme-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );
	
	// Only load tech features on single posts
	if ( is_single() ) {
		wp_enqueue_script( 'original-theme-tech', get_template_directory_uri() . '/js/tech-features.js', array('jquery'), _S_VERSION, true );
		wp_enqueue_script( 'original-theme-floating-banner', get_template_directory_uri() . '/js/floating-banner.js', array('jquery'), _S_VERSION, true );
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'original_theme_scripts' );

/**
 * アイキャッチを設定しない場合、デフォルトのアイキャッチ画像を使用する。
 */
function set_default_thumbnail_image ( $html ) {
  if ( "" === $html ) {
    $html = '<img src="' . get_template_directory_uri() . '/img/default.png" alt="default eye-catch image." />';
  }
  return $html;
}
add_filter( 'post_thumbnail_html', 'set_default_thumbnail_image' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Custom widgets.
 */
require get_template_directory() . '/inc/widgets.php';

/**
 * Breadcrumb navigation.
 */
require get_template_directory() . '/inc/breadcrumbs.php';

/**
 * SEO and structured data.
 */
require get_template_directory() . '/inc/seo.php';

/**
 * Tag order functionality.
 */
require get_template_directory() . '/inc/tag-order.php';

/**
 * Category order functionality.
 */
require get_template_directory() . '/inc/category-order.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

/**
 * arst_toc shortcode
 * Extract and list all text content of specified HTML tags from the current post
 */
function arst_toc_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'tag' => ''
	), $atts );
	
	if ( empty( $atts['tag'] ) ) {
		return '<p>タグが指定されていません。</p>';
	}
	
	global $post;
	if ( ! $post ) {
		return '<p>投稿が見つかりません。</p>';
	}
	
	// Get the post content
	$content = $post->post_content;
	
	// Remove shortcodes to avoid infinite loops
	$content = strip_shortcodes( $content );
	
	// Parse HTML and extract specified tags with full tag structure
	$tag_name = sanitize_text_field( $atts['tag'] );
	$pattern = '/<' . preg_quote( $tag_name, '/' ) . '([^>]*?)>(.*?)<\/' . preg_quote( $tag_name, '/' ) . '>/is';
	
	preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );
	
	if ( empty( $matches ) ) {
		return '<p>「' . esc_html( $tag_name ) . '」タグが見つかりませんでした。</p>';
	}
	
	$output = '<div class="arst_toc">';
	$output .= '<p>【目次】</p><hr />';
	$output .= '<ul class="arst-toc-list">';
	
	foreach ( $matches as $index => $match ) {
		$full_tag = $match[0]; // Full tag
		$attributes = $match[1]; // Tag attributes
		$text_content = $match[2]; // Tag content
		
		// Strip HTML tags and get clean text
		$clean_text = wp_strip_all_tags( $text_content );
		$clean_text = trim( $clean_text );
		
		if ( ! empty( $clean_text ) ) {
			// Generate unique ID for this heading
			$heading_id = 'arst-toc-' . sanitize_title( $clean_text ) . '-' . $index;
			
			$output .= '<li class="arst-toc-item">';
			$output .= '<a href="#' . $heading_id . '" class="arst-toc-link">';
			$output .= '<span>∨</span>';
			$output .= esc_html( $clean_text );
			$output .= '</a>';
			$output .= '</li>';
		}
	}
	
	$output .= '</ul><hr />';
	$output .= '</div>';
	
	// Add IDs to the actual tags in content using a filter
	add_filter( 'the_content', function( $content_to_filter ) use ( $matches, $tag_name ) {
		foreach ( $matches as $index => $match ) {
			$full_tag = $match[0];
			$attributes = $match[1];
			$text_content = $match[2];
			
			$clean_text = wp_strip_all_tags( $text_content );
			$clean_text = trim( $clean_text );
			
			if ( ! empty( $clean_text ) ) {
				$heading_id = 'arst-toc-' . sanitize_title( $clean_text ) . '-' . $index;
				
				// Check if ID already exists in attributes
				if ( strpos( $attributes, 'id=' ) === false ) {
					// Add ID to the tag
					$new_tag = '<' . $tag_name . $attributes . ' id="' . $heading_id . '">' . $text_content . '</' . $tag_name . '>';
					$content_to_filter = str_replace( $full_tag, $new_tag, $content_to_filter );
				}
			}
		}
		return $content_to_filter;
	}, 20 );
	
	return $output;
}
add_shortcode( 'arst_toc', 'arst_toc_shortcode' );

/**
 * all_topics shortcode
 * Display all posts in descending order by date with pagination
 */
function all_topics_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'count' => 10
	), $atts );

	$page_url = get_permalink();
	
	// Get current page number
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	if ( isset( $_GET['topics_page'] ) ) {
		$paged = max( 1, intval( $_GET['topics_page'] ) );
	}
	
	// Query for latest posts
	$latest_posts = new WP_Query( array(
		'posts_per_page' => intval( $atts['count'] ),
		'paged' => $paged,
		'orderby' => 'date',
		'order' => 'DESC',
		'post_status' => 'publish',
		'post_type' => 'post'
	) );
	
	if ( ! $latest_posts->have_posts() ) {
		return '<p>投稿が見つかりませんでした。</p>';
	}
	
	$output = '<section class="all-topics">';
	$output .= '<div class="post-list-widget">';
	
	while ( $latest_posts->have_posts() ) : $latest_posts->the_post();
		$output .= '<article class="post-list-item">';
		$output .= '<div class="post-list-thumbnail">';
		$output .= '<a href="' . esc_url( get_permalink() ) . '">';
		
		// Get thumbnail
		if ( has_post_thumbnail() ) {
			$output .= get_the_post_thumbnail( null, 'square-360', array( 'loading' => 'lazy' ) );
		} else {
			$output .= '<img src="' . get_template_directory_uri() . '/img/default.png" alt="default eye-catch image." loading="lazy" />';
		}
		
		$output .= '</a>';
		$output .= '</div>';
		$output .= '<div class="post-list-content">';
		$output .= '<h3 class="post-list-title">';
		$output .= '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
		$output .= '</h3>';
		$output .= '<div class="post-list-excerpt">';
		
		// Get excerpt
		if ( has_excerpt() ) {
			$output .= '<p>' . esc_html( get_the_excerpt() ) . '</p>';
		} else {
			$output .= '<p>' . wp_filter_nohtml_kses( get_the_content() ) . '</p>';
		}
		
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</article>';
	endwhile;
	
	$output .= '</div>';
	$output .= '<hr class="all-topics-separator" />';
	
	// Add pagination
	if ( $latest_posts->max_num_pages > 1 ) {
		$current_page = max( 1, $paged );
		$big = 999999999;
		$base_url = $page_url;
		
		$pagination = paginate_links( array(
			'base' => add_query_arg( 'topics_page', '%#%', $base_url ),
			'format' => '',
			'current' => $current_page,
			'total' => $latest_posts->max_num_pages,
			'prev_text' => '前へ',
			'next_text' => '次へ',
			'type' => 'array',
			'show_all' => false,
			'end_size' => 2,
			'mid_size' => 2,
		) );
		
		if ( $pagination ) {
			$output .= '<nav class="pagination-wrapper">';
			$output .= '<div class="pagination">';
			foreach ( $pagination as $link ) {
				$output .= '<span class="page-item">' . $link . '</span>';
			}
			$output .= '</div>';
			$output .= '</nav>';
		}
	}
	
	$output .= '</section>';
	
	wp_reset_postdata();
	
	return $output;
}
add_shortcode( 'all_topics', 'all_topics_shortcode' );

/**
 * Post View Counter using custom pvs table
 * Track and display post view counts
 */

// Create pvs table on theme activation
function create_pvs_table() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'pvs';
	
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		post_id bigint(20) NOT NULL,
		view_count bigint(20) DEFAULT 0,
		last_viewed datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY post_id (post_id),
		KEY view_count (view_count)
	) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

// Hook to create table when theme is activated
add_action( 'after_switch_theme', 'create_pvs_table' );

// Record post views
function record_post_views( $post_id ) {
	if ( ! is_single() ) return;
	if ( empty( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}
	
	// Don't count views for admin users
	if ( current_user_can( 'manage_options' ) ) return;
	
	// Don't count multiple views from the same session
	$session_key = 'viewed_post_' . $post_id;
	if ( isset( $_SESSION[ $session_key ] ) ) return;
	
	// Start session if not already started
	if ( ! session_id() ) {
		session_start();
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'pvs';
	
	// Insert or update view count
	$wpdb->query( $wpdb->prepare( "
		INSERT INTO $table_name (post_id, view_count, last_viewed) 
		VALUES (%d, 1, NOW()) 
		ON DUPLICATE KEY UPDATE 
		view_count = view_count + 1, 
		last_viewed = NOW()
	", $post_id ) );
	
	// Mark as viewed in this session
	$_SESSION[ $session_key ] = true;
}

// Hook to record views when single post is loaded
function track_post_views() {
	if ( is_single() ) {
		record_post_views( get_the_ID() );
	}
}
add_action( 'wp_head', 'track_post_views' );

// Get post views count from pvs table
function get_post_views( $post_id = null ) {
	if ( empty( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'pvs';
	
	$count = $wpdb->get_var( $wpdb->prepare( 
		"SELECT view_count FROM $table_name WHERE post_id = %d", 
		$post_id 
	) );
	
	return empty( $count ) ? 0 : intval( $count );
}

// Display post views
function display_post_views( $post_id = null, $singular = 'view', $plural = 'views' ) {
	$count = get_post_views( $post_id );
	$text = ( $count == 1 ) ? $singular : $plural;
	return $count . ' ' . $text;
}

// Get popular posts from pvs table
function get_popular_posts( $limit = 5 ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'pvs';
	
	$results = $wpdb->get_results( $wpdb->prepare( "
		SELECT p.post_id, p.view_count 
		FROM $table_name p 
		INNER JOIN {$wpdb->posts} posts ON p.post_id = posts.ID 
		WHERE posts.post_status = 'publish' 
		ORDER BY p.view_count DESC 
		LIMIT %d
	", $limit ) );
	
	return $results;
}

// Add views column to admin posts list
function add_views_column( $columns ) {
	$columns['post_views'] = 'Views';
	return $columns;
}
add_filter( 'manage_posts_columns', 'add_views_column' );

// Display views count in admin column
function display_views_column( $column, $post_id ) {
	if ( $column === 'post_views' ) {
		echo get_post_views( $post_id );
	}
}
add_action( 'manage_posts_custom_column', 'display_views_column', 10, 2 );

// Make views column sortable
function make_views_column_sortable( $columns ) {
	$columns['post_views'] = 'post_views';
	return $columns;
}
add_filter( 'manage_edit-post_sortable_columns', 'make_views_column_sortable' );

// Handle sorting by views using pvs table
function sort_posts_by_views( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	
	$orderby = $query->get( 'orderby' );
	if ( 'post_views' === $orderby ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pvs';
		
		add_filter( 'posts_join', function( $join ) use ( $wpdb, $table_name ) {
			return $join . " LEFT JOIN $table_name pvs ON {$wpdb->posts}.ID = pvs.post_id";
		} );
		
		add_filter( 'posts_orderby', function( $orderby ) use ( $query ) {
			$order = $query->get( 'order' ) ?: 'DESC';
			return "COALESCE(pvs.view_count, 0) $order";
		} );
	}
}
add_action( 'pre_get_posts', 'sort_posts_by_views' );

/**
 * popular_topics shortcode
 * Display posts ordered by view count (most popular first)
 */
function popular_topics_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'count' => 10
	), $atts );
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'pvs';
	
	// Get popular post IDs from pvs table
	$popular_post_ids = $wpdb->get_col( $wpdb->prepare( "
		SELECT p.post_id 
		FROM $table_name p 
		INNER JOIN {$wpdb->posts} posts ON p.post_id = posts.ID 
		WHERE posts.post_status = 'publish' 
		AND posts.post_type = 'post'
		ORDER BY p.view_count DESC 
		LIMIT %d
	", intval( $atts['count'] ) ) );
	
	if ( empty( $popular_post_ids ) ) {
		return '<p>人気投稿が見つかりませんでした。</p>';
	}
	
	// Query for posts using the popular post IDs
	$popular_posts = new WP_Query( array(
		'post__in' => $popular_post_ids,
		'orderby' => 'post__in',
		'posts_per_page' => intval( $atts['count'] ),
		'post_status' => 'publish',
		'post_type' => 'post'
	) );
	
	if ( ! $popular_posts->have_posts() ) {
		return '<p>人気投稿が見つかりませんでした。</p>';
	}
	
	$output = '<section class="popular-topics">';
	$output .= '<div class="post-list-widget">';
	
	while ( $popular_posts->have_posts() ) : $popular_posts->the_post();
		$output .= '<article class="post-list-item">';
		$output .= '<div class="post-list-thumbnail popular-label">';
		$output .= '<a href="' . esc_url( get_permalink() ) . '">';
		
		// Get thumbnail
		if ( has_post_thumbnail() ) {
			$output .= get_the_post_thumbnail( null, 'square-360', array( 'loading' => 'lazy' ) );
		} else {
			$output .= '<img src="' . get_template_directory_uri() . '/img/default.png" alt="default eye-catch image." loading="lazy" />';
		}
		
		$output .= '</a>';
		$output .= '</div>';
		$output .= '<div class="post-list-content">';
		$output .= '<h3 class="post-list-title">';
		$output .= '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
		$output .= '</h3>';
		$output .= '<div class="post-list-excerpt">';
		
		// Get excerpt
		if ( has_excerpt() ) {
			$output .= '<p>' . esc_html( get_the_excerpt() ) . '</p>';
		} else {
			$output .= '<p>' . wp_filter_nohtml_kses( get_the_content() ) . '</p>';
		}
		
		// Add view count display
		$view_count = get_post_views( get_the_ID() );
		$output .= '<div class="post-view-count">';
		$output .= '<span class="view-count-label">閲覧数: </span>';
		$output .= '<span class="view-count-number">' . number_format( $view_count ) . '</span>';
		$output .= '</div>';
		
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</article>';
	endwhile;
	
	$output .= '</div>';
	$output .= '<hr class="popular-topics-separator" />';
	$output .= '</section>';
	
	wp_reset_postdata();
	
	return $output;
}
add_shortcode( 'popular_topics', 'popular_topics_shortcode' );

/**
 * Add OGP and social media meta tags to post pages
 */
function add_ogp_meta_tags() {
	if ( is_single() ) {
		global $post;
		
		// Basic OGP tags
		$title = get_the_title();
		$description = get_the_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 55 );
		$url = get_permalink();
		$site_name = get_bloginfo( 'name' );
		
		// Get featured image
		$image = '';
		if ( has_post_thumbnail() ) {
			$image = get_the_post_thumbnail_url( null, 'large' );
		} else {
			$image = get_template_directory_uri() . '/img/default.png';
		}
		
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";
		echo '<meta property="og:type" content="article" />' . "\n";
		echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
		echo '<meta property="og:locale" content="ja_JP" />' . "\n";
		
		// Twitter Card tags
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:site" content="@tw_ikuty" />' . "\n";
		echo '<meta name="twitter:creator" content="@tw_ikuty" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
		
		// Facebook specific tags
		echo '<meta property="fb:app_id" content="" />' . "\n";
		echo '<meta property="article:author" content="' . esc_attr( get_the_author() ) . '" />' . "\n";
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '" />' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '" />' . "\n";
		
		// Hatena Bookmark tags
		echo '<meta name="hatena:bookmark" content="nocomment" />' . "\n";
		
		// BlueSky (uses standard OGP tags)
		// Additional meta tags for better social sharing
		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		
		// Post tags for article:tag
		$tags = get_the_tags();
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . '" />' . "\n";
			}
		}
	}
}
add_action( 'wp_head', 'add_ogp_meta_tags' );

/**
 * clink shortcode
 * Create a custom link card with image, title, and excerpt
 */
function clink_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'implicit' => '',
		'imgurl' => '',
		'url' => '',
		'title' => '',
		'excerpt' => ''
	), $atts );
	
	$output = "";
	if ( empty( $atts['implicit'])) {
		// Validate required parameters
		if ( empty( $atts['url'] ) ) {
			return '<p>urlが指定されていません。</p>';
		}
		// Set default values
		$url = esc_url( $atts['url'] );
		$post_id = url_to_postid($url);
		$post = get_post($post_id);

		// T.B.A.
	} else {
		// Validate required parameters
		if ( empty( $atts['imgurl'] ) ) {
			return '<p>imgurlが指定されていません。</p>';
		}
		if ( empty( $atts['url'] ) ) {
			return '<p>urlが指定されていません。</p>';
		}
		if ( empty( $atts['title'] ) ) {
			return '<p>titleが指定されていません。</p>';
		}
		if ( empty( $atts['excerpt'] ) ) {
			return '<p>titleが指定されていません。</p>';
		}

		// Set default values
		$url = esc_url( $atts['url'] );
		$imgurl = esc_url( $atts['imgurl'] );
		$title = ! empty( $atts['title'] ) ? esc_html( $atts['title'] ) : 'リンク';
		$excerpt = ! empty( $atts['excerpt'] ) ? esc_html( $atts['excerpt'] ) : '';
		$implicit = sanitize_text_field( $atts['implicit'] );
		
		// Process implicit parameter (true/false)
		$is_implicit = filter_var( $implicit, FILTER_VALIDATE_BOOLEAN );
		
		// Build the output HTML
		$container_class = $is_implicit ? 'clink-container clink-implicit' : 'clink-container';
		$output = '<div class="' . $container_class . '">';
		$output .= '<div class="clink-image">';
		$output .= '<a class="clink-image-link" href="' . $url . '">';
		$output .= '<img src="' . $imgurl . '" alt="' . $title . '" loading="lazy" />';
		$output .= '</a>';
		$output .= '</div>';
		$output .= '<div class="clink-content">';
		$output .= '<a class="clink-title" href="'. $url .'">' . $title . '</a>';
		if ( ! empty( $excerpt ) ) {
			$output .= '<p class="clink-excerpt">' . $excerpt . '</p>';
		}
		$output .= '</div>';
		$output .= '</div>';
	}

	return $output;
}
add_shortcode( 'clink', 'clink_shortcode' );
