<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package original-theme
 */

get_header();
?>

	<div class="container">
		<div class="content-wrapper">
			<div class="content-area">
				<main id="primary" class="site-main">

					<section class="error-404 not-found">
						<header class="page-header">
							<h1 class="page-title">お探しのページは見つかりませんでした</h1>
						</header><!-- .page-header -->

						<div class="page-content">
							<p>申し訳ございません。お探しのページは移動、または削除された可能性がございます。
もしくはご指定のURLが間違っていたかもしれません。
ページ右上のプルダウンメニューにある検索ボックスにキーワードを入力いただくか、
ページ下部にありますサイトマップにて該当のページをお探しください。</p>
							
							<div class="error-404-button">
								<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="home-button">
									トップページに戻る
								</a>
							</div>
						</div><!-- .page-content -->
						<div class="page-footer">
						</div>
					</section><!-- .error-404 -->

				</main><!-- #main -->
			</div><!-- .content-area -->
			
			<?php get_sidebar(); ?>
			
		</div><!-- .content-wrapper -->
	</div><!-- .container -->

<?php
get_footer();
