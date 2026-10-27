<?php
/**
 * カテゴリ・タグの並び順を保存するAJAXハンドラの共通処理
 *
 * @package original-theme
 */

/**
 * 並び順の保存リクエストを検証し、ターム(カテゴリ・タグ)のメタに保存して、JSONで応答する。
 *
 * 不正なリクエスト(nonceや並び順のキーが無い、値が配列など)でも、PHPの警告を出さずに、
 * HTTPステータス付きのJSONエラーで応答する。
 *
 * @param string $taxonomy        対象のタクソノミー(category または post_tag)。
 * @param string $nonce_action    nonceのアクション名。
 * @param string $field           POSTで受け取る並び順の配列のキー(term_id => order)。
 * @param string $meta_key        並び順を保存するタームメタのキー。
 * @param string $success_message 成功時のメッセージ。
 */
function ikuty_theme_handle_term_order_ajax( $taxonomy, $nonce_action, $field, $meta_key, $success_message ) {
	// nonceが無い、または誤っている場合も、警告を出さずに失敗として扱う(第3引数 false で、dieしない)
	if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
		wp_send_json_error( 'Security check failed', 403 );
	}

	if ( ! current_user_can( 'manage_categories' ) ) {
		wp_send_json_error( 'Insufficient permissions', 403 );
	}

	$orders = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : null;
	if ( ! is_array( $orders ) ) {
		wp_send_json_error( 'Invalid data format', 400 );
	}

	foreach ( $orders as $term_id => $order ) {
		$term_id = (int) $term_id;

		// 値が配列の場合などは無視する。別のタクソノミーのタームには書き込まない
		if ( $term_id <= 0 || ! is_scalar( $order ) || ! term_exists( $term_id, $taxonomy ) ) {
			continue;
		}

		update_term_meta( $term_id, $meta_key, (int) $order );
	}

	wp_send_json_success( $success_message );
}
