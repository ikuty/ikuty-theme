<?php
/**
 * REST APIのアクセス制限
 *
 * nginx(infra/nginx/sites-enabled/virtual.conf)は /wp-json/ を遮断しているが、
 * WordPress本体は rest_route をクエリ文字列だけでなくPOSTの本文からも読む
 * (WP::parse_request が $_GET と $_POST の両方を見る)ため、
 * `/?rest_route=/...` や、本文で rest_route を指定した `POST /` でREST APIに到達できてしまう。
 * nginxはPOSTの本文を見られないため、WordPress側で、ログインしていないREST要求を拒否する。
 *
 * @package original-theme
 */

/**
 * ログインしていないREST要求を401で拒否する。
 *
 * 優先度を101にして、本体の rest_cookie_check_errors(優先度100)より後で判定する。
 * Cookie認証でnonceが無い場合、本体はそこでユーザーを未認証(ID 0)に戻したうえで true を返す
 * (「認証エラーではなく、未認証のまま通す」という意味)。そのため、$result が true かどうかではなく、
 * その時点で is_user_logged_in() かどうかを見る。
 *
 * @param WP_Error|null|true $result これまでの認証の結果。
 * @return WP_Error|null|true
 */
function ikuty_theme_require_login_for_rest_api( $result ) {
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_login_required',
			'REST API requires authentication.',
			array( 'status' => 401 )
		);
	}

	return $result;
}
add_filter( 'rest_authentication_errors', 'ikuty_theme_require_login_for_rest_api', 101 );
