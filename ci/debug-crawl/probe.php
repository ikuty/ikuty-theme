<?php
/**
 * Helper for ci/debug-crawl/crawl.py. Run with `wp eval-file probe.php <command> [args]`.
 *
 * Commands:
 *   cookie            Print an administrator auth cookie (no password) as JSON.
 *   cleanup <uid> <t> Destroy the session token issued by `cookie`.
 *   ids               Print sample post/page/term IDs and the current term orders as JSON.
 *   snapshot          Save the page-view table (pvs) and print a digest of the data this run may touch.
 *   restore           Restore the pvs table and remove auto-drafts created by the crawl.
 *   digest            Print the digest only.
 *   hooks             Exercise OGP image generation, login-history hooks and shortcodes, then undo the changes.
 *   selftest-install  Place a mu-plugin that triggers a known deprecation (strftime) when ?crawl_selftest=1 is requested.
 *   selftest-remove   Remove that mu-plugin.
 *
 * All of this only works against the local database.
 */

// 日本語のコメント: 本番のDBに対して実行しない。ローカルの開発用コンテナ専用。
if ( ! defined( 'WP_CLI' ) ) {
	exit( 1 );
}

// eval-file は関数の中で実行されるため、ここで定義した変数は、関数の中から global では参照できない。定数と関数で持つ
$cmd = isset( $args[0] ) ? $args[0] : '';
define( 'CRAWL_DEBUG_DIR', WP_CONTENT_DIR . '/debug' );
define( 'CRAWL_SNAPSHOT', CRAWL_DEBUG_DIR . '/crawl-snapshot.json' );
define( 'CRAWL_SELFTEST_FILE', WP_CONTENT_DIR . '/mu-plugins/crawl-selftest.php' );

function crawl_pvs_table() {
	return $GLOBALS['wpdb']->prefix . 'pvs';
}

function crawl_out( $data ) {
	echo 'JSON ' . wp_json_encode( $data ) . "\n";
}

function crawl_digest() {
	global $wpdb;
	$pvs_table = crawl_pvs_table();
	$terms = $wpdb->get_results(
		"SELECT term_id, meta_key, meta_value FROM {$wpdb->termmeta} WHERE meta_key IN ('tag_order','category_order') ORDER BY term_id, meta_key",
		ARRAY_A
	);
	return array(
		'login_logs'  => md5( serialize( get_option( 'ikuty_theme_login_logs', array() ) ) ),
		'term_orders' => md5( serialize( $terms ) ),
		'attachments' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='attachment'" ),
		'ogp_meta'    => md5( serialize( $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key='_ogp_image_id' ORDER BY post_id", ARRAY_A ) ) ),
		'posts'       => md5( serialize( $wpdb->get_results( "SELECT ID, post_modified_gmt, post_status FROM {$wpdb->posts} WHERE post_type IN ('post','page') AND post_status <> 'auto-draft' ORDER BY ID", ARRAY_A ) ) ),
		'pvs'         => md5( serialize( $wpdb->get_results( "SELECT post_id, view_count FROM {$pvs_table} ORDER BY post_id", ARRAY_A ) ) ),
	);
}

switch ( $cmd ) {
	case 'cookie':
		$user = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
		if ( ! $user ) {
			WP_CLI::error( 'No administrator found.' );
		}
		$user  = $user[0];
		$exp   = time() + 3600;
		$token = WP_Session_Tokens::get_instance( $user->ID )->create( $exp );
		crawl_out(
			array(
				'user_id' => $user->ID,
				'token'   => $token,
				'cookie'  => AUTH_COOKIE . '=' . wp_generate_auth_cookie( $user->ID, $exp, 'auth', $token ) . ';'
					. LOGGED_IN_COOKIE . '=' . wp_generate_auth_cookie( $user->ID, $exp, 'logged_in', $token ),
			)
		);
		break;

	case 'cleanup':
		WP_Session_Tokens::get_instance( (int) $args[1] )->destroy( $args[2] );
		crawl_out( array( 'ok' => true ) );
		break;

	case 'ids':
		$q = function ( $type ) {
			return array_map( 'intval', get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => 12, 'fields' => 'ids' ) ) );
		};
		$orders = array( 'tag' => array(), 'category' => array() );
		foreach ( array( 'tag' => 'post_tag', 'category' => 'category' ) as $key => $tax ) {
			foreach ( get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'ids' ) ) as $tid ) {
				$v = get_term_meta( $tid, $key . '_order', true );
				if ( '' !== $v ) {
					$orders[ $key ][ $tid ] = (int) $v;
				}
			}
		}
		$posts = $q( 'post' );
		crawl_out(
			array(
				'posts'      => $posts,
				'pages'      => $q( 'page' ),
				'categories' => array_map( 'intval', get_terms( array( 'taxonomy' => 'category', 'fields' => 'ids', 'number' => 8 ) ) ),
				'tags'       => array_map( 'intval', get_terms( array( 'taxonomy' => 'post_tag', 'fields' => 'ids', 'number' => 6 ) ) ),
				'orders'     => $orders,
				'permalink'  => $posts ? get_permalink( $posts[0] ) : '',
			)
		);
		break;

	case 'snapshot':
		wp_mkdir_p( CRAWL_DEBUG_DIR );
		$wpdb = $GLOBALS['wpdb'];
		file_put_contents(
			CRAWL_SNAPSHOT,
			wp_json_encode(
				array(
					'pvs'    => $wpdb->get_results( 'SELECT * FROM ' . crawl_pvs_table() . ' ORDER BY id', ARRAY_A ),
					'max_id' => (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->posts}" ),
				)
			)
		);
		crawl_out( crawl_digest() );
		break;

	case 'restore':
		$wpdb = $GLOBALS['wpdb'];
		$snap = json_decode( (string) file_get_contents( CRAWL_SNAPSHOT ), true );
		if ( ! is_array( $snap ) || ! is_array( $snap['pvs'] ) ) {
			WP_CLI::error( 'No snapshot.' );
		}
		// ページ閲覧数(巡回で増える)を戻す
		$wpdb->query( 'DELETE FROM ' . crawl_pvs_table() );
		foreach ( $snap['pvs'] as $row ) {
			$wpdb->insert( crawl_pvs_table(), $row );
		}
		// 管理画面の「新規投稿」を開くと作られる自動下書きを消す
		$drafts = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_status='auto-draft' AND ID > %d", $snap['max_id'] ) );
		foreach ( $drafts as $d ) {
			wp_delete_post( (int) $d, true );
		}
		crawl_out( array( 'restored_pvs_rows' => count( $snap['pvs'] ), 'removed_auto_drafts' => count( $drafts ) ) );
		break;

	case 'digest':
		crawl_out( crawl_digest() );
		break;

	case 'hooks':
		global $wpdb;
		$result = array();

		// 1. OGP image generation (creates an attachment and post meta; both are undone below)
		$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( $posts ) {
			$pid        = $posts[0];
			$old_meta   = get_post_meta( $pid, '_ogp_image_id', true );
			$new_id     = ikuty_theme_generate_ogp_image( $pid );
			$result['ogp_generated'] = (bool) $new_id;
			$created = (int) get_post_meta( $pid, '_ogp_image_id', true );
			if ( $created && (int) $old_meta !== $created ) {
				wp_delete_attachment( $created, true );
			}
			if ( '' === $old_meta ) {
				delete_post_meta( $pid, '_ogp_image_id' );
			} else {
				update_post_meta( $pid, '_ogp_image_id', $old_meta );
			}
		}

		// 2. Login-history hooks (the option is restored afterwards)
		$old_logs = get_option( 'ikuty_theme_login_logs', null );
		$admin    = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
		do_action( 'wp_login', $admin->user_login, $admin );
		do_action( 'wp_login_failed', 'crawl-no-such-user' );
		$result['login_logs_added'] = count( (array) get_option( 'ikuty_theme_login_logs', array() ) ) - count( (array) $old_logs );
		if ( null === $old_logs ) {
			delete_option( 'ikuty_theme_login_logs' );
		} else {
			update_option( 'ikuty_theme_login_logs', $old_logs );
		}

		// 3. Shortcodes
		$url                        = $posts ? get_permalink( $posts[0] ) : home_url( '/' );
		$result['clink_len']        = strlen( do_shortcode( '[clink url="' . $url . '" title="t" excerpt="e"]' ) );
		$result['clink_default_len'] = strlen( do_shortcode( '[clink url="' . $url . '" title="t"]' ) );
		$result['popular_len']      = strlen( do_shortcode( '[popular_topics count="5"]' ) );
		crawl_out( $result );
		break;

	case 'selftest-install':
		// Web側のログ出力の確認用。?crawl_selftest=1 のときだけ、既知の非推奨(strftime)を呼ぶ mu-plugin を置く。巡回の後で削除する
		wp_mkdir_p( dirname( CRAWL_SELFTEST_FILE ) );
		file_put_contents( CRAWL_SELFTEST_FILE, "<?php\nif ( isset( \$_GET['crawl_selftest'] ) ) {\n\tstrftime( '%Y' );\n}\n" );
		crawl_out( array( 'installed' => file_exists( CRAWL_SELFTEST_FILE ) ) );
		break;

	case 'selftest-remove':
		if ( file_exists( CRAWL_SELFTEST_FILE ) ) {
			unlink( CRAWL_SELFTEST_FILE );
		}
		crawl_out( array( 'removed' => ! file_exists( CRAWL_SELFTEST_FILE ) ) );
		break;

	default:
		WP_CLI::error( 'Unknown command: ' . $cmd );
}
