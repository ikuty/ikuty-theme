# ローカルのデバッグ巡回

本番では `WP_DEBUG` が無効のため、`E_DEPRECATED` や `E_NOTICE` がログに出ません。静的解析(`ci/README.md`)では見つからない、実行時の非推奨や警告を洗い出すために、ローカルで `WP_DEBUG_LOG` を有効にし、フロントと管理画面を巡回します。PHPを更新するたびに再実行してください。

ローカルのDBとコンテナだけを使います(`http://localhost:8000` と、ローカルの `wpcli` コンテナ)。本番には接続しません。

## 実行手順(リポジトリのルートで実行)

```bash
# 1. デバッグログを有効にして、WordPressを起動する(-V と --pull never は必須)
docker compose -f docker-compose.yml -f docker-compose.debug.yml up -d --pull never --no-deps -V wordpress
#    PHPのバージョンを変える場合: WORDPRESS_IMAGE=wordpress:php8.5-apache を前に付ける
#    Apple Siliconでamd64のイメージを取得できない場合: WORDPRESS_PLATFORM=linux/arm64 も付ける

# 2. 巡回して、結果を表示する
python3 ci/debug-crawl/crawl.py

# 3. 元に戻す
docker compose up -d --pull never --no-deps -V wordpress
```

Python 3(標準ライブラリのみ)とDockerがあれば動きます。サイトマップの全URL(約330件)で、1分以内に終わります。`--limit 20` で先頭の20件だけにできます。

## 何をするか

1. 巡回の前に、変更されうるデータ(ログイン履歴、タームの並び順、添付ファイル、投稿、ページ閲覧数)を記録する
2. 自己確認: 既知の非推奨(`strftime()`)を出す一時的な mu-plugin を置き、`/?crawl_selftest=1` を開いて、Web側のログに記録されることを確認する(巡回の後で削除する)
3. フロントの巡回: サイトマップの全URLと、トップ、検索、404、フィード(匿名、Cookieあり。URLは日本語を含むため、Pythonでエンコードする)
4. 管理画面の巡回: wp-cli で管理者の認証Cookieを発行し(パスワード不要)、主要な画面と、投稿・ページ・カテゴリー・タグの編集画面を開く
5. OGP画像の生成、ログイン履歴のフック(成功と失敗)、ショートコード(`[clink]`、`[popular_topics]`)を実行する
6. AJAX(タグ・カテゴリーの並び順)に、正常なリクエストと、不正なリクエスト(nonceなし、値が配列、不正なキーなど)を送る。正常なリクエストは、現在の値をそのまま送るので、データは変わらない
7. 後片付け: 管理者のセッション、ページ閲覧数、自動下書き、OGP画像、ログイン履歴を元に戻し、1.の記録と一致することを確認する
8. `.debug-logs/debug.log` を、テーマ・プラグイン・コアに分類して集計する(wordpress.orgへの接続エラーは除外する)

## 結果の見方

- `data unchanged`: ローカルのデータが元に戻っている。`CHANGED: ...` と出たら、その項目が残っている
- `HTTP status`: 4xx が1件あるのは、意図的に開く404ページ。5xx や接続失敗があれば、`FAILED` として表示される
- `self test line recorded: yes`: ログが記録されている。`NO` の場合は、デバッグログの設定が効いていないので、手順1をやり直す(`-V` を付け忘れると、`wp-config.php` が作り直されない)
- `theme` のメッセージが0件なら `RESULT: OK`。1件以上あれば `FAIL`(終了コード1)。`plugin` と `core` は、参考情報として表示する(今回のテーマの対象外)

## 注意点

- 巡回で `post-new.php` を開くため、WordPressが自動下書きを作る。巡回の最後に削除する
- 中断した場合(Ctrl+Cなど)は、ページ閲覧数や管理者のセッションが残ることがある。もう一度実行すれば、その時点のデータが「元」になるので、気になる場合はDBを元に戻す
- `.debug-logs/` はgitの管理外
