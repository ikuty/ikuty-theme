<?php
/**
 * Custom widgets for ikuty.com-style theme
 *
 * @package original-theme
 */

/**
 * Pickup Section Widget
 */
class Original_Theme_Pickup_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'original_theme_pickup',
			esc_html__( 'Pickup Section', 'original-theme' ),
			array( 'description' => esc_html__( 'Display featured posts in a pickup section.', 'original-theme' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$category = ! empty( $instance['category'] ) ? $instance['category'] : '';
		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
		}

		$query_args = array(
			'posts_per_page' => $number,
			'post_status' => 'publish',
			'ignore_sticky_posts' => true,
		);

		if ( $category ) {
			$query_args['cat'] = $category;
		}

		$pickup_query = new WP_Query( $query_args );

		if ( $pickup_query->have_posts() ) : ?>
			<div class="pickup-posts">
				<?php while ( $pickup_query->have_posts() ) : $pickup_query->the_post(); ?>
					<article class="pickup-item">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="pickup-thumbnail">
								<a href="<?php the_permalink(); ?>">
									<?php the_post_thumbnail( 'pickup-thumbnail', array( 'loading' => 'lazy' ) ); ?>
								</a>
							</div>
						<?php endif; ?>
						<div class="pickup-content">
							<h4 class="pickup-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h4>
							<div class="pickup-meta">
								<?php echo get_the_date(); ?>
							</div>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
		<?php
		endif;

		wp_reset_postdata();
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$category = ! empty( $instance['category'] ) ? $instance['category'] : '';
		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'original-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php esc_attr_e( 'Category:', 'original-theme' ); ?></label>
			<?php wp_dropdown_categories( array(
				'name' => $this->get_field_name( 'category' ),
				'id' => $this->get_field_id( 'category' ),
				'class' => 'widefat',
				'selected' => $category,
				'show_option_all' => esc_html__( 'All Categories', 'original-theme' ),
			) ); ?>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_attr_e( 'Number of posts to show:', 'original-theme' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $number ); ?>" size="3">
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['category'] = ( ! empty( $new_instance['category'] ) ) ? absint( $new_instance['category'] ) : '';
		$instance['number'] = ( ! empty( $new_instance['number'] ) ) ? absint( $new_instance['number'] ) : 5;

		return $instance;
	}
}

/**
 * Enhanced Category Widget
 */
class Original_Theme_Category_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'original_theme_categories',
			esc_html__( 'Technical Categories', 'original-theme' ),
			array( 'description' => esc_html__( 'Display categories with hierarchical structure and post counts.', 'original-theme' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Categories', 'original-theme' );
		$show_count = ! empty( $instance['show_count'] ) ? $instance['show_count'] : false;
		$hierarchical = ! empty( $instance['hierarchical'] ) ? $instance['hierarchical'] : true;

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
		}

		// Get categories with custom order
		$categories = get_ordered_categories( array(
			'hide_empty' => false,
		) );

		if ( $categories ) {
			echo '<ul class="technical-categories">';
			
			if ( $hierarchical ) {
				// Build hierarchical structure with custom order
				$hierarchy = build_footer_category_hierarchy( $categories );
				foreach ( $hierarchy as $category ) {
					display_category_hierarchy_item( $category, $show_count );
				}
			} else {
				// Simple flat list with custom order
				foreach ( $categories as $category ) {
					$category_link = get_category_link( $category->term_id );
					$count_display = $show_count ? ' (' . $category->count . ')' : '';
					echo '<li><a href="' . esc_url( $category_link ) . '">' . esc_html( $category->name ) . $count_display . '</a></li>';
				}
			}
			
			echo '</ul>';
		}

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Categories', 'original-theme' );
		$show_count = ! empty( $instance['show_count'] ) ? $instance['show_count'] : false;
		$hierarchical = ! empty( $instance['hierarchical'] ) ? $instance['hierarchical'] : true;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'original-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_count ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>">
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"><?php esc_attr_e( 'Show post counts', 'original-theme' ); ?></label>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $hierarchical ); ?> id="<?php echo esc_attr( $this->get_field_id( 'hierarchical' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hierarchical' ) ); ?>">
			<label for="<?php echo esc_attr( $this->get_field_id( 'hierarchical' ) ); ?>"><?php esc_attr_e( 'Show hierarchy', 'original-theme' ); ?></label>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['show_count'] = ! empty( $new_instance['show_count'] ) ? 1 : 0;
		$instance['hierarchical'] = ! empty( $new_instance['hierarchical'] ) ? 1 : 0;

		return $instance;
	}
}

/**
 * Popular Posts Widget
 */
class Original_Theme_Popular_Posts_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'original_theme_popular_posts',
			esc_html__( 'Popular Posts', 'original-theme' ),
			array( 'description' => esc_html__( 'Display popular posts based on comment count.', 'original-theme' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Popular Posts', 'original-theme' );
		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
		}

		$popular_posts = new WP_Query( array(
			'posts_per_page' => $number,
			'post_status' => 'publish',
			'orderby' => 'comment_count',
			'order' => 'DESC',
			'ignore_sticky_posts' => true,
		) );

		if ( $popular_posts->have_posts() ) : ?>
			<div class="popular-posts">
				<?php while ( $popular_posts->have_posts() ) : $popular_posts->the_post(); ?>
					<article class="popular-item">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="popular-thumbnail">
								<a href="<?php the_permalink(); ?>">
									<?php the_post_thumbnail( 'pickup-thumbnail', array( 'loading' => 'lazy' ) ); ?>
								</a>
							</div>
						<?php endif; ?>
						<div class="popular-content">
							<h4 class="popular-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h4>
							<div class="popular-meta">
								<span class="popular-date"><?php echo get_the_date(); ?></span>
								<span class="popular-comments"><?php comments_number( '0', '1', '%' ); ?> comments</span>
							</div>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
		<?php
		endif;

		wp_reset_postdata();
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Popular Posts', 'original-theme' );
		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'original-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_attr_e( 'Number of posts to show:', 'original-theme' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $number ); ?>" size="3">
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['number'] = ( ! empty( $new_instance['number'] ) ) ? absint( $new_instance['number'] ) : 5;

		return $instance;
	}
}

/**
 * Post List Widget
 */
class Original_Theme_Empty_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'original_theme_empty',
			esc_html__( 'Post List Widget', 'original-theme' ),
			array( 'description' => esc_html__( 'Display latest posts with thumbnails.', 'original-theme' ) )
		);
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		
		$query_args = array(
			'posts_per_page' => 10,
			'post_status' => 'publish',
			'orderby' => 'date',
			'order' => 'DESC',
			'ignore_sticky_posts' => true,
		);

		$posts_query = new WP_Query( $query_args );

		if ( $posts_query->have_posts() ) : ?>
			<div class="post-list-widget">
				<?php while ( $posts_query->have_posts() ) : $posts_query->the_post(); ?>
					<article class="post-list-item">
						<div class="post-list-thumbnail">
							<a href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail( 'square-360', array( 'loading' => 'lazy' ) ); ?>
							</a>
						</div>
						<div class="post-list-content">
							<h3 class="post-list-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>
							<div class="post-list-excerpt">
								<?php
								if ( has_excerpt() ) {
									the_excerpt();
								} else {
									echo '<p>' . wp_filter_nohtml_kses(get_the_content()) . '</p>';
									//echo '<p>' . wp_trim_words( get_the_content(), 30, '...' ) . '</p>';
								}
								?>
							</div>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
		<?php
		endif;

		wp_reset_postdata();
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		?>
		<p><?php esc_html_e( 'This widget displays the latest 10 posts with thumbnails.', 'original-theme' ); ?></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array();
	}
}

/**
 * Display category hierarchy item for widget
 */
function display_category_hierarchy_item( $category, $show_count = false ) {
	$category_link = get_category_link( $category->term_id );
	$count_display = $show_count ? ' (' . $category->count . ')' : '';
	
	echo '<li>';
	echo '<a href="' . esc_url( $category_link ) . '">' . esc_html( $category->name ) . $count_display . '</a>';
	
	if ( ! empty( $category->children ) ) {
		echo '<ul class="children">';
		foreach ( $category->children as $child ) {
			display_category_hierarchy_item( $child, $show_count );
		}
		echo '</ul>';
	}
	
	echo '</li>';
}

/**
 * Register widgets
 */
function original_theme_register_widgets() {
	register_widget( 'Original_Theme_Pickup_Widget' );
	register_widget( 'Original_Theme_Category_Widget' );
	register_widget( 'Original_Theme_Popular_Posts_Widget' );
	register_widget( 'Original_Theme_Empty_Widget' );
}
add_action( 'widgets_init', 'original_theme_register_widgets' );