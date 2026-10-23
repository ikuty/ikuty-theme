# テーマのPHP 8.x互換性CI

`themes/ikuty-theme` のPHP 8.x互換性を、プルリクエスト(ベース: `dev`)ごとに検査します。ワークフローは `.github/workflows/theme-ci.yml` です。デプロイ(`deploy-theme.yml`)とは独立しており、デプロイを止めません。

ツールはテーマの `composer.json` とは別に、このディレクトリで管理します。テーマの `require-dev` が古いphpcs 3系を要求し、PHPCompatibility 10(alpha)と衝突するためです。`composer.lock` でバージョンを固定しています。

## 検査内容

| ジョブ | 内容 |
|---|---|
| `lint` | PHP 8.1 / 8.3 / 8.5 で `php -l`。構文エラーに加え、コンパイル時の非推奨警告が出ても失敗にする |
| `static` | PHPCompatibilityWP 3.0 alpha(`testVersion` 8.1以上)と、PHPStan(レベル5、PHP 8.5、WordPress/WP-CLIスタブ) |

## ローカルで実行する

PHPが無くても、Dockerで実行できます(リポジトリのルートで実行)。

```bash
# ツールのインストール(初回のみ)
docker run --rm -v "$PWD":/repo -w /repo/ci composer:2 composer install --no-interaction --prefer-dist

# PHPCompatibility
docker run --rm -v "$PWD":/repo -w /repo composer:2 ci/vendor/bin/phpcs --standard=ci/phpcs.xml --basepath=. themes/ikuty-theme

# PHPStan
docker run --rm -v "$PWD":/repo -w /repo composer:2 php -d memory_limit=1G ci/vendor/bin/phpstan analyse -c ci/phpstan.neon --no-progress
```

## PHPStanのベースライン

`ci/phpstan-baseline.neon` には、対応不要な指摘(WordPressのスタブとの型の差異、Underscores標準のコードなど)だけを登録しています。**実際に直すべき指摘は登録しません。** コードを直した結果、ベースラインの項目に該当しなくなった場合は、PHPStanが「Ignored error pattern was not matched」で知らせるので、その項目を削除してください。

ベースラインを作り直す場合は、次のコマンドを使います。

```bash
docker run --rm -v "$PWD":/repo -w /repo composer:2 php -d memory_limit=1G ci/vendor/bin/phpstan analyse -c ci/phpstan.neon --no-progress --generate-baseline=ci/phpstan-baseline.neon
```

## 限界

静的解析なので、実行時にだけ起きる問題(実際に `null` が渡る経路など)は検出できません。また、本番では `WP_DEBUG` が無効のため、`E_DEPRECATED` と `E_NOTICE` はログに出ません。ローカルで `WP_DEBUG_LOG` を有効にして主要ページを巡回する確認を、別途行ってください。
