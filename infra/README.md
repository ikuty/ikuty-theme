# WordPress Docker Infrastructure with ikuty-theme

> このディレクトリは `ikuty-theme` リポジトリのモノレポ化により、旧 `ikuty-wp-infra` リポジトリの内容を `infra/` 配下に統合したものです。本番サーバー上でのパスは `~/ikuty-theme/infra/` になります。

Docker Compose を使用した WordPress インフラストラクチャです。nginx リバースプロキシ、PHP-FPM、MySQL 8.4、ikuty-themeの自動統合、Let's Encrypt SSL証明書を組み合わせたセキュアで高パフォーマンスな構成です。

🚀 **一行でセットアップ**: `./scripts/init-with-theme.sh`

🎨 **ikuty-theme自動統合**: GitHubから最新のdevブランチを自動取得・適用

📊 **高パフォーマンス**: レスポンス時間 0.017-0.032秒、nginx ↔ PHP-FPM Unixソケット通信

🔒 **開発環境HTTPS対応**: 自己署名証明書でローカル開発環境もHTTPS化

🔧 **プラグイン管理システム**: YAML駆動による宣言的プラグイン管理とバージョン制御

## 特徴

- **nginx**: リバースプロキシ、SSL/TLS終端、静的ファイル配信
- **WordPress**: PHP 8.3-FPM による高速なPHP処理、nginx ↔ PHP-FPM Unixソケット通信
- **MySQL**: 8.4 による高性能データベース
- **ikuty-theme**: カスタムテーマの自動統合
- **SSL証明書**: 開発環境では自己署名証明書、本番環境ではLet's Encrypt自動取得・更新
- **プラグイン管理**: YAML駆動による宣言的プラグイン管理、バージョン制御、自動更新設定
- **セキュリティ**: レート制限、セキュリティヘッダー、アクセス制御
- **監視**: ログ収集、パフォーマンス監視
- **バックアップ**: 自動バックアップ・復旧機能

## 必要要件

- Docker Engine 20.10+
- Docker Compose 2.0+
- 最低2GB RAM
- 最低10GB ストレージ
- 有効なドメイン名（SSL証明書用）

## クイックスタート

### 1. 環境設定

#### 開発環境（推奨）
ローカル開発では自己署名証明書を使用：

```bash
# Environment Configuration
ENVIRONMENT=development

# Domain Configuration
DOMAIN_NAME=test.ikuty.com
LETSENCRYPT_EMAIL=your-email@example.com

# MySQL Database Configuration（変更推奨）
MYSQL_DATABASE=wordpress
MYSQL_USER=wordpress
MYSQL_PASSWORD=your_secure_password
MYSQL_ROOT_PASSWORD=your_root_password
```

#### 本番環境
本番環境ではLet's Encrypt証明書を使用：

```bash
# Environment Configuration
ENVIRONMENT=production

# Domain Configuration（実際のドメインに変更）
DOMAIN_NAME=your-domain.com
LETSENCRYPT_EMAIL=your-email@example.com
```

### 2. ikuty-theme付きWordPress環境の起動

**推奨方法**: 統合初期化スクリプトを使用：

```bash
./scripts/init-with-theme.sh
```

このスクリプトは以下を自動実行します：
- ikuty-theme（devブランチ）のクローン
- Docker環境の起動
- WordPressのセットアップ
- テーマの有効化

### 3. 手動セットアップ（オプション）

個別にセットアップする場合：

```bash
# テーマのセットアップ
./scripts/setup-theme.sh

# サービスの起動
docker-compose up -d

# WordPressのセットアップ
./scripts/wordpress-setup.sh
```

### 4. WordPressへのアクセス

ブラウザで `http://localhost` にアクセスし、ikuty-themeが適用されたWordPressサイトを確認してください。

**デフォルト管理者情報:**
- **サイトURL**: http://localhost
- **管理画面**: http://localhost/wp-admin
- **ユーザー名**: admin
- **パスワード**: admin_password_change_me
- **アクティブテーマ**: ikuty-theme (original-theme)

## ファイル構成

```
ikuty-theme/infra/
├── docker-compose.yml          # Docker Compose設定
├── .env                       # 環境変数
├── nginx/                     # nginx設定
│   ├── conf.d/                # http共通設定(00-http.conf)。nginx.confはイメージ標準を使う
│   ├── sites-available/
│   ├── sites-enabled/
│   └── snippets/
├── php/                       # PHP-FPM設定
│   ├── php-fpm.conf
│   ├── uploads.ini            # アップロード設定（64MB対応）
│   └── zzz-socket.conf        # Unixソケット設定
├── mysql/                     # MySQL設定
│   └── my.cnf
├── scripts/                   # 運用スクリプト
│   ├── init-with-theme.sh    # 統合初期化（推奨）
│   ├── setup-theme.sh        # テーマセットアップ
│   ├── wordpress-setup.sh    # WordPress初期化
│   ├── manage-plugins-php.sh # プラグイン管理（PHP版）
│   ├── plugins.yml           # プラグイン設定
│   ├── php-scripts/          # PHP版プラグイン管理システム
│   │   ├── manage-plugins.php  # メインエントリーポイント
│   │   └── php-classes/        # PHPクラスライブラリ
│   │       ├── Logger.php      # 色付きログ機能
│   │       ├── PluginConfig.php# YAML設定解析
│   │       ├── WPCLIWrapper.php# WP-CLI実行ラッパー
│   │       └── PluginManager.php# プラグイン管理ロジック
│   ├── ssl-init.sh           # SSL初期化（開発・本番対応）
│   ├── ssl-renew.sh          # SSL更新（本番環境のみ）
│   ├── backup.sh             # バックアップ
│   └── restore.sh            # 復旧
├── ssl_certs/                # SSL証明書（開発環境）
│   └── live/test.ikuty.com/  # 自己署名証明書
├── themes/                   # テーマファイル
│   └── ikuty-theme/          # ikuty-theme（自動クローン）
└── .tmp/                      # 設計・タスク管理
    ├── design.md
    └── task.md
```

## 運用コマンド

### サービス管理

```bash
# 起動
docker-compose up -d

# 停止
docker-compose down

# 再起動
docker-compose restart

# ログ確認
docker-compose logs -f [service_name]
```

### ikuty-theme管理

```bash
# テーマの再セットアップ（最新版を取得）
./scripts/setup-theme.sh

# テーマの状態確認
docker-compose exec wordpress wp theme list --path=/var/www/html --allow-root

# テーマの有効化
docker-compose exec wordpress wp theme activate ikuty-theme --path=/var/www/html --allow-root

# テーマファイルの直接編集（開発用）
# ホストマシンで themes/ikuty-theme/themes/original-theme/ 以下を編集
```

### プラグイン管理

```bash
# プラグインの完全同期（推奨）
./scripts/manage-plugins.sh --sync

# プラグインの状態確認
./scripts/manage-plugins.sh --status

# インストールのみ実行
./scripts/manage-plugins.sh --install

# 不要プラグインの削除のみ実行
./scripts/manage-plugins.sh --cleanup

# auto-update設定のみ更新
./scripts/manage-plugins.sh --update-settings

# active設定のみ更新
./scripts/manage-plugins.sh --activate-plugins

# バージョン設定のみ更新
./scripts/manage-plugins.sh --update-versions
```

## ikuty-theme詳細

### テーマ情報
- **リポジトリ**: https://github.com/ikuty/ikuty-theme.git
- **ブランチ**: dev（自動取得）
- **ベーステーマ**: Underscores.me
- **バージョン**: 1.0.0

### 自動統合機能
- Docker Compose起動時にテーマを自動マウント
- WordPressでのテーマ自動認識
- ホストマシンでのテーマファイル編集対応
- 最新版自動取得機能

### テーマ構造
```
themes/ikuty-theme/themes/original-theme/
├── style.css              # メインスタイルシート
├── functions.php          # テーマ機能
├── header.php             # ヘッダーテンプレート
├── footer.php             # フッターテンプレート
├── index.php              # メインテンプレート
├── img/                   # 画像ファイル
│   └── ikutycom.png      # サイトロゴ
└── js/                    # JavaScriptファイル
    └── navigation.js      # ナビゲーション機能
```

## プラグイン管理詳細

### プラグイン設定ファイル

`scripts/plugins.yml`でプラグインを宣言的に管理：

```yaml
plugins:
  classic-editor:
    name: classic-editor
    auto-update: false      # 自動更新の有効/無効
    active: true           # プラグインの有効化/無効化
    version: latest        # latest または特定バージョン
  updraftplus:
    name: updraftplus
    auto-update: true
    active: true
    version: latest
  wps-hide-login:
    name: wps-hide-login
    auto-update: false
    active: true
    version: latest
```

### バージョン管理ルール

- **auto-update: true**: version設定に関わらず常に最新版を使用
- **auto-update: false + version: latest**: 手動での最新版管理
- **auto-update: false + version: 特定バージョン**: 指定バージョンを使用

### プラグイン管理機能

- **YAML駆動**: 設定ファイルによる宣言的プラグイン管理
- **バージョン制御**: 特定バージョンの指定とインストール
- **自動更新管理**: プラグインごとの自動更新制御
- **有効化管理**: プラグインごとの有効化/無効化制御
- **エラーハンドリング**: 無効バージョン指定時の自動フォールバック
- **統計情報**: 処理結果の詳細レポート

### SSL証明書管理

本番環境ではLet's Encrypt、開発環境では自己署名証明書を使用します。証明書の運用は、安全性を高めるために初回発行と更新のプロセスが明確に分かれています。

#### 1. 初回発行（本番環境）

サーバーの初回セットアップ時に、以下のコマンドでLet's Encrypt証明書を発行します。

```bash
# ./scripts/ssl-init.sh
```

**テスト発行（推奨）**
本番のレートリミット（週5回）を消費しないように、まずはテスト用のステージング環境で発行を試すことを強く推奨します。

```bash
# ./scripts/ssl-init.sh --staging
```
ブラウザで警告が出ますが、証明書が発行されることを確認できれば成功です。

#### 2. 証明書の自動更新（本番環境）

証明書の更新は、以下のスクリプトをcronなどで定期的に実行します。

```bash
# ./scripts/ssl-renew.sh
```
このスクリプトは `certbot renew` を実行します。証明書の有効期限が30日以内になった場合のみ、自動で更新が行われます。また、証明書が更新された場合のみ、Nginxが自動でリロードされるため、安全で効率的です。

**手動での強制更新は絶対に避けてください。`ssl-init.sh`は初回発行時のみ使用します。**

#### 3. 開発環境

開発環境では、初回起動時に自己署名証明書が自動で生成されます。手動で再生成したい場合は、以下のコマンドを実行します。

```bash
# ./scripts/ssl-init.sh
```

#### 4. 証明書の状態確認

```bash
# 本番環境のコンテナ内の証明書を確認
docker-compose exec nginx openssl x509 -in /etc/letsencrypt/live/your-domain.com/fullchain.pem -text -noout

# 開発環境のローカル証明書を確認
openssl x509 -in ssl_certs/live/test.ikuty.com/fullchain.pem -text -noout
```

#### 5. 追加ドメインの証明書発行（本番環境）

`ssl-init.sh`は`.env`の`DOMAIN_NAME`1つ分しか扱いません。別のホスト名(例:MCPサーバ用の`test.ikuty.com`、`nginx/sites-enabled/test-mcp.conf`)を公開する場合は、certbotの別lineageとして手動で発行します。

```bash
cd ~/ikuty-theme/infra
EMAIL=$(grep '^LETSENCRYPT_EMAIL=' .env | cut -d= -f2-)

# まず --dry-run で確認してから、--dry-run を外して本番の発行を行う
docker compose run --rm --no-deps certbot certonly --webroot --webroot-path=/var/www/certbot \
  -d test.ikuty.com --email "$EMAIL" --agree-tos --no-eff-email --non-interactive --dry-run
```

- 事前にDNSのAレコードが対象のVPSを指している必要があります
- ACMEのチャレンジは、port 80の既存serverブロック(`server_name _`)が`/.well-known/acme-challenge/`を受けるため、nginx設定の変更なしで通ります
- 発行した証明書は、既存の`ssl-renew.sh`(`certbot renew`)が全lineageまとめて更新するため、追加の設定は不要です
- `conf.d/00-http.conf`の`include`はファイルを個別に指定しているため、新しい設定ファイルを`sites-enabled/`に置いただけでは読み込まれません。`include`行を追加してください(globにしないのは、読み込み順で443番ポートの既定のserverブロックが決まるためです。詳細は`00-http.conf`のコメント)
- 設定ファイルの変更は、`nginx -t`で確認してから`nginx -s reload`で反映します(コンテナの再作成は不要です)

### バックアップ・復旧

定期バックアップはUpdraftPlusが行います(S3へ月1回)。`backup.sh`は定期実行せず、PHPやOSの更新など大きな作業の前に手動でバックアップを取るために使います。

```bash
# バックアップ作成(保存先は環境変数BACKUP_DIRで変更可能。未指定時は~/backups)
./scripts/backup.sh

# バックアップから復旧
./scripts/restore.sh ~/backups/wordpress_backup_YYYYMMDD_HHMMSS
```

作業前にDBだけ手早く取る場合は次のとおりです。ダンプの末尾に`-- Dump completed`があることを確認してください。

```bash
docker compose exec -T mysql sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction "$MYSQL_DATABASE"' > ~/db-$(date +%F-%H%M).sql
tail -n 1 ~/db-*.sql
```

### 更新作業(本番サーバー)

以下はすべて本番サーバー(`ssh ikuty.com`)の`infra/`ディレクトリで実行します。`infra/`配下の変更はGitHub Actionsの対象外のため、手動で反映します。

#### PHP・Dockerイメージの更新

WordPress本体のファイルは`wp_data`ボリュームにあり、PHPはDockerイメージ(`wordpress:php8.3-fpm`)に含まれます。ベースイメージのタグはマイナーバージョンを固定していないため、`--pull`を付けて再ビルドしない限り、古いPHPのまま動き続けます。月1回程度、およびPHPの重大な脆弱性が公表されたときに実施してください。

```bash
# 1. 現在のPHPを確認
docker compose exec -T wordpress php -v | head -1

# 2. DBをバックアップ(上記参照)し、ロールバック用にイメージへタグを付ける
docker tag wordpress-infra-wordpress:latest wordpress-infra-wordpress:before-php-update

# 3. 最新のベースイメージで再ビルドして入れ替え(数秒停止します)
docker compose build --pull wordpress
docker compose up -d wordpress

# 4. 確認
docker compose exec -T wordpress php -v | head -1
curl -s -o /dev/null -w "%{http_code}\n" https://ikuty.com/
docker compose logs --since 10m wordpress 2>&1 | grep -iE "fatal|warning|error"
```

注意点:

- 脆弱性対応では、`php -v`の結果がphp.netの告知にある修正版以上であることを必ず確認してください。公式イメージの更新は、修正版の公開より遅れることがあります。更新直後に8.3.35だった場合は、イメージが更新されるまで待って再実行します。
- `502 Bad Gateway`になった場合は`docker compose restart nginx`で復帰します。
- 問題がなければ、`docker rmi wordpress-infra-wordpress:before-php-update`とダングリングイメージの削除(`docker image prune -f`)で後片付けします。脆弱なPHPを含むイメージを残さないようにしてください。

#### WordPress本体の更新

マイナー更新(セキュリティ・保守リリース)はWordPressの自動更新で適用されます。メジャー更新は手動です。

```bash
docker compose exec -T wordpress wp core version --path=/var/www/html --allow-root
docker compose exec -T wordpress wp core check-update --path=/var/www/html --allow-root

# メジャー更新の場合は、DBをバックアップしたうえで実行
docker compose exec -T wordpress wp core update --path=/var/www/html --allow-root
docker compose exec -T wordpress wp core update-db --path=/var/www/html --allow-root
```

PHPの脆弱性はWordPress本体やプラグインの更新では防げません。PHPの更新が必要です。

#### OSの更新と再起動

ログイン時に`System restart required`と表示されている場合は再起動が必要です。2:00はSSL証明書の更新が走るため避けてください。

```bash
# 再起動の理由(カーネルなど)を確認
cat /var/run/reboot-required.pkgs

# 更新を適用(設定ファイルの確認で止まらないよう既存設定を保持し、サービスの自動再起動は抑える)
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=l apt-get upgrade -y -o Dpkg::Options::=--force-confold

# 再起動(数分間サイトが停止します。SSHが切れるのは正常です)
sudo reboot
```

Dockerやcontainerdの更新が含まれる場合、`apt-get upgrade`の途中でコンテナが一度再起動します。再起動後、1〜2分待って次を確認します。

```bash
uname -r
ls /var/run/reboot-required    # No such file or directory が正常
docker compose ps --format "{{.Service}} {{.Status}}"
docker compose exec -T wordpress php -v | head -1
curl -s -o /dev/null -w "%{http_code}\n" https://ikuty.com/
```

全コンテナは`restart: unless-stopped`のため、再起動後に自動で復帰します。

#### 重大な脆弱性の公表後に侵害の痕跡を確認する

修正前の期間にサイトが公開されていた場合は、更新後に次を確認します。いずれも読み取りのみです。

```bash
W="docker compose exec -T wordpress"
$W wp core verify-checksums --path=/var/www/html --allow-root      # wp-config-docker.phpの警告は公式イメージの標準ファイルで正常
$W wp plugin verify-checksums --all --path=/var/www/html --allow-root
$W wp user list --role=administrator --fields=ID,user_login,user_registered --path=/var/www/html --allow-root
$W find /var/www/html/wp-content/uploads -name "*.php"            # 保護用のindex.phpなど以外が無いこと
$W find /var/www/html -name "*.php" -mtime -4 -not -path "*/wp-content/themes/ikuty-theme-*"
```

最後のコマンドは、プラグインの自動更新(`plugins.yml`でauto-update: true)により、該当プラグイン配下のファイルが多数表示されます。プラグインのチェックサム検証が成功していれば、公式ファイルと一致しています。`wp-includes`・`wp-admin`・`uploads`など、更新対象外の場所に変更があれば要調査です。ディスク上の確認だけでは侵害の有無を断定できない点に注意してください。

#### nginx設定の変更の反映

`infra/nginx/`配下の変更は、GitHub Actionsの自動デプロイの対象外です。サーバーで最新を取得したうえで、**変更したファイルによって反映の方法が異なります。**

すべての設定ファイルが、ディレクトリ単位でマウントされています。**どのファイルを変更しても、`nginx -s reload`(無停止)で反映されます。**

| 設定の置き場所 | 内容 |
|---|---|
| `conf.d/00-http.conf` | httpコンテキストの共通設定(gzip、SSL、レート制限など)と、`sites-enabled/`の個別のinclude |
| `sites-enabled/`・`sites-available/`・`snippets/` | サイトごとの設定 |

`nginx.conf`自体は、リポジトリで管理していません(イメージ標準のものを使い、その`http`ブロックが`/etc/nginx/conf.d/*.conf`を読み込みます)。

```bash
cd ~/ikuty-theme && git fetch -q origin dev && git reset --hard origin/dev && cd infra

docker compose exec -T nginx nginx -t              # 構文確認(警告が無く successful であること)
docker compose exec -T nginx nginx -s reload
```

**`docker-compose.yml`を変更した場合(マウントの追加など)は、コンテナの作り直しが必要です。**

```bash
docker compose up -d --force-recreate nginx        # 1〜2秒、HTTP/HTTPSが停止
docker compose exec -T nginx nginx -t
```

注意点:

- **イメージ標準の`nginx.conf`と重複する設定は、`00-http.conf`に書けません。** `log_format main`、`access_log`、`sendfile`、`keepalive_timeout`、`default_type`は標準側で設定済みで、重複すると`nginx -t`がエラーになります(`00-http.conf`の冒頭のコメント参照)。標準側の値を変えたい場合は、`nginx:alpine`の標準の`nginx.conf`との差分を確認してください
- **`http`の外(`user`、`worker_processes`、`events`など)は、`conf.d/`から変更できません。** 変更が必要になった場合は、`nginx.conf`を自前で管理する構成に戻す必要があります(その場合は、ファイル単位のマウントの制約が再び生じます)
- `00-http.conf`は`error_log`をhttpコンテキストで`warn`にしています。メインコンテキスト(起動・終了のメッセージなど)は、イメージ標準の`notice`です
- `nginx:alpine`は固定のタグではないため、イメージの更新で標準の`nginx.conf`が変わる可能性があります。更新後は`nginx -t`を確認してください

### 補足: なぜファイル単位のマウントをやめたのか

以前は`nginx.conf`をファイル単位でマウントしていました(`./nginx/nginx.conf:/etc/nginx/nginx.conf:ro`)。

- Dockerはファイル単位のマウントを、コンテナ作成時のファイルの実体(inode)に紐づけます。
- `git reset`や`git pull`は、ファイルを書き換えるのではなく、新しいファイルで置き換えます。コンテナ側は古い実体を指したままになり、ホストとコンテナで内容が食い違います。
- `nginx -t`と`nginx -s reload`は、コンテナ内の(古い)内容を読むため、変更が反映されず、構文確認でも変更前の内容が検査されます。
- ディレクトリ単位のマウントは、中のファイルを名前で引き直すため、置き換えても反映されます。

実例: 2026-10に`ssl_stapling`を削除した際、`reload`では警告が消えず、コンテナの作り直しが必要でした。この問題を、#28で`conf.d/`のディレクトリ単位のマウントに移すことで解消しました。

### 補足: Docker aptリポジトリの鍵の管理(サーバー側の設定)

サーバーのaptの設定はリポジトリの管理外です。Dockerのリポジトリの鍵は、レガシーの `/etc/apt/trusted.gpg` ではなく、`/etc/apt/keyrings/docker.asc` に置き、`/etc/apt/sources.list.d/download_docker_com_linux_ubuntu.list` の `signed-by` で指定しています(#32、2026-11-07に移行済み)。

```
deb [arch=amd64 signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu noble stable
```

サーバーを作り直すときは、Docker公式の手順(`https://download.docker.com/linux/ubuntu/gpg` を `/etc/apt/keyrings/docker.asc` に保存)に従い、`apt-key add` は使いません。鍵のフィンガープリントは `9DC858229FC7DD38854AE2D88D81803C0EBFCD88` です。

移行の手順(参考。aptの設定のみで、コンテナは再起動しない):

```bash
# 1. バックアップ
B=~/apt-backup-$(date +%Y%m%d%H%M%S); mkdir -p $B
sudo cp -a /etc/apt/trusted.gpg /etc/apt/sources.list.d $B/

# 2. trusted.gpg 内の鍵を書き出し、フィンガープリントを確認
sudo install -d -m 0755 /etc/apt/keyrings
gpg --no-default-keyring --keyring /etc/apt/trusted.gpg --export --armor 8D81803C0EBFCD88 \
  | sudo tee /etc/apt/keyrings/docker.asc >/dev/null
sudo chmod a+r /etc/apt/keyrings/docker.asc
gpg --show-keys --with-colons /etc/apt/keyrings/docker.asc | grep ^fpr | head -1

# 3. signed-by を追加
sudo sed -i 's#^deb \[arch=amd64\] #deb [arch=amd64 signed-by=/etc/apt/keyrings/docker.asc] #' \
  /etc/apt/sources.list.d/download_docker_com_linux_ubuntu.list

# 4. 新しい鍵で更新できることを確認してから、trusted.gpg の鍵を削除
sudo apt-get update
sudo gpg --no-default-keyring --keyring /etc/apt/trusted.gpg --batch --yes --delete-keys 8D81803C0EBFCD88

# 5. 警告が出ないこと、候補が表示されることを確認
sudo apt-get update && apt-cache policy docker-ce | head -3
```

失敗した場合は、バックアップの `trusted.gpg` と `sources.list.d` を戻します。

## セキュリティ設定

### ファイアウォール設定例

```bash
# UFW (Ubuntu Firewall) の設定例
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw enable
```

### 管理者IP制限

`nginx/sites-available/http.conf`で管理者アクセスを制限：

```nginx
location ~ ^/(wp-admin|wp-login\.php) {
    allow 127.0.0.1;
    allow YOUR_IP_ADDRESS;  # あなたのIPアドレス
    deny all;
    # ... 他の設定
}
```

## 監視・メンテナンス

### 自動更新の設定

crontabでSSL証明書の自動更新を設定：

```bash
# 毎日午前2時に証明書更新をチェック
0 2 * * * /path/to/ikuty-theme/infra/scripts/ssl-renew.sh
```

定期バックアップはUpdraftPlus(S3へ月1回)が行うため、`backup.sh`のcron登録は不要です。

### ログ監視

```bash
# nginx アクセスログ(直近分。コンテナを再作成すると消える)
docker-compose logs nginx

# PHP-FPMログ
docker-compose logs wordpress

# MySQLログ
docker-compose logs mysql
```

## パフォーマンス情報

### 実測値（テスト環境）
- **レスポンス時間**: 0.017-0.032秒（Unixソケット通信）
- **処理能力**: 1000+ requests/sec
- **通信方式**: nginx ↔ PHP-FPM Unixソケット通信（TCPオーバーヘッド削減）
- **メモリ使用量**:
  - nginx: ~10MB
  - WordPress: ~90MB
  - MySQL: ~470MB

### パフォーマンステスト

```bash
# パフォーマンステストの実行
./scripts/performance-test.sh

# 個別テスト
curl -o /dev/null -s -w "%{time_total}\n" http://localhost
```

## 技術詳細

### nginx ↔ PHP-FPM Unixソケット通信

このインフラストラクチャでは、nginxとPHP-FPM間の通信にUnixソケットを使用しています。

#### 利点
- **パフォーマンス向上**: TCPオーバーヘッドの削減
- **セキュリティ強化**: ネットワーク経由の通信削除
- **リソース効率**: CPU使用率の軽減

#### 実装詳細
- **ソケットファイル**: `/var/run/php-fpm.sock`
- **権限**: `0666` (nginx・PHP-FPM間でアクセス可能)
- **ボリューム共有**: `php_socket`ボリューム経由で共有

#### 設定ファイル
- `php/zzz-socket.conf`: PHP-FPMソケット設定
- `nginx/sites-available/http.conf`: nginx設定
- `docker-compose.yml`: ボリューム共有設定

### 環境別SSL証明書システム

#### 開発環境（自己署名証明書）
- **証明書タイプ**: 自己署名証明書 + CA証明書
- **場所**: `./ssl_certs/live/test.ikuty.com/`
- **特徴**: 
  - ローカル開発でのHTTPS対応
  - ブラウザ警告表示（正常動作）
  - 即座のSSL環境構築

#### 本番環境（Let's Encrypt）
- **証明書タイプ**: Let's Encrypt公開証明書
- **場所**: Docker volume `ssl_certs`
- **特徴**:
  - 信頼された証明書
  - 自動更新対応
  - ブラウザ警告なし

#### 環境切り替え
- **設定**: `.env`の`ENVIRONMENT`変数
- **development**: 自己署名証明書
- **production**: Let's Encrypt証明書

## トラブルシューティング

### よくある問題

1. **ikuty-theme関連**
   - テーマが表示されない: `./scripts/setup-theme.sh` を再実行
   - テーマファイルが更新されない: コンテナ再起動 `docker-compose restart wordpress`

2. **SSL証明書関連**
   - **自己署名証明書**: ブラウザで「詳細設定」→「安全でないページに移動」で進む
   - **Let's Encrypt取得失敗**: ドメインがサーバーIPを正しく指しているか、DNSレコードが浸透しているか確認してください。また、`./scripts/ssl-init.sh --staging` を使って、本番のレートリミットを消費せずにテストできます。
   - **ファイアウォール**: 80番ポート（HTTP）が外部からアクセス可能になっているか確認してください。Let's Encryptの認証に必要です。
   - **レートリミットに達した場合**: Let's Encryptの証明書発行回数には上限があります（同一ドメインで週5回など）。`--force-renewal` のような危険なオプションの使用や、`ssl-init.sh` の頻繁な実行は避けてください。レートリミットに達してしまった場合は、以下の手順で復旧します。
     1. レートリミットが解除されるのを待ちます（通常は1週間後）。
     2. `docker-compose down` でコンテナを停止します。
     3. `docker volume rm wordpress-infra_ssl_certs` を実行し、古い証明書ボリュームを完全に削除します(`COMPOSE_PROJECT_NAME=wordpress-infra`を`.env`で固定しているため、ディレクトリ名が`infra/`になった後もボリューム名は`wordpress-infra_`接頭辞のまま)。
     4. `docker-compose up -d nginx wordpress` でサービスを起動します。
     5. `./scripts/ssl-init.sh` を実行して、新しい証明書を発行します。
     6. `docker-compose up -d` ですべてのサービスを起動します。

3. **WordPressが表示されない**
   - すべてのサービスが起動しているか確認: `docker-compose ps`
   - ログを確認: `docker-compose logs`

4. **データベース接続エラー**
   - MySQL設定と.env環境変数を確認
   - データベースの起動を確認: `docker-compose logs mysql`

5. **502 Bad Gateway エラー（ソケット通信関連）**
   - ソケットファイルの存在確認: `docker-compose exec wordpress ls -la /var/run/php-fpm.sock`
   - PHP-FPMプロセス確認: `docker-compose exec wordpress ps aux | grep php`
   - nginx ↔ PHP-FPM通信確認: `docker-compose logs nginx | grep sock`

6. **画像アップロード失敗**
   - アップロードディレクトリ権限確認: `docker-compose exec wordpress ls -la /var/www/html/wp-content/uploads`
   - PHP設定確認: `docker-compose exec wordpress php -i | grep upload_max_filesize`

### nginxアクセスログの保存とローテーション

アクセスログは、標準出力(`docker logs`)に加えて、`nginx_logs` ボリューム上の `access-detail.log` にも書きます。コンテナを再作成してもファイルは残ります(`access.log` はイメージ由来の標準出力へのシンボリックリンクなので、使いません)。

- 形式: `log_format main` に `host=`(Host)と `rt=`(応答時間、秒)を足したもの。リクエスト本文と認証ヘッダは記録しません
- 場所(サーバー): `/var/lib/docker/volumes/wordpress-infra_nginx_logs/_data/access-detail.log`
- 設定: `nginx/conf.d/00-http.conf`。反映は `nginx -s reload` だけです

ローテーション(日次、90日分を圧縮して保持)は、ホストのlogrotateで行います。初回に1度だけ、サーバーに設置します。

```bash
sudo cp ~/ikuty-theme/infra/logrotate/ikuty-nginx /etc/logrotate.d/ikuty-nginx
sudo chmod 644 /etc/logrotate.d/ikuty-nginx
sudo logrotate -d /etc/logrotate.d/ikuty-nginx   # 構文と動作の確認(実際には回さない)
```

確認の例:

```bash
sudo tail -f /var/lib/docker/volumes/wordpress-infra_nginx_logs/_data/access-detail.log
# Googlebotの応答コード別の件数
sudo grep Googlebot /var/lib/docker/volumes/wordpress-infra_nginx_logs/_data/access-detail.log | awk '{print $9}' | sort | uniq -c
```

### ログファイルの場所

- nginx: `docker-compose logs nginx`(直近分)、`access-detail.log`(上記。ボリューム上、90日保持)
- PHP-FPM: `docker-compose logs wordpress`
- MySQL: `docker-compose logs mysql`
- SSL更新: `/var/log/ssl-renew.log`

## 開発・カスタマイズ

### ikuty-theme開発

テーマファイルはホストマシンで直接編集可能：

```bash
# テーマディレクトリに移動
cd themes/ikuty-theme/themes/original-theme/

# ファイル編集後、ブラウザで変更を確認
# CSS: style.css
# PHP: functions.php, header.php, footer.php など
# JS: js/navigation.js

# 変更が反映されない場合はコンテナ再起動
docker-compose restart wordpress nginx
```

### テーマの更新

上記は手動更新の手順です。本番環境では以下の自動デプロイに置き換わっています。

### 自動デプロイ(GitHub Actions)

`themes/ikuty-theme/` 配下を変更して `dev` ブランチへpushすると、GitHub Actionsが自動的に本番へ反映します。

1. `lint`ジョブ: テーマ内の`*.php`に対して`php -l`で構文チェック
2. `deploy`ジョブ: 制限付きSSH鍵(`command=`でサーバー上の`infra/scripts/deploy-theme.sh`のみ実行可能)経由でサーバーへ接続し、以下を実行
   - `dev`ブランチの最新を取得
   - `wp-content/themes/ikuty-theme-<commit-sha>`としてバージョン付きディレクトリに配置
   - `wp theme activate`で切替
   - `wp-content/ikuty-theme-current`を新しい世代へ付け替え(CSS/JS/画像のURLをデプロイで変えないため。下記参照)
   - 直近3世代を保持し、それより古い世代は自動削除
   - `https://ikuty.com/`へのヘルスチェック

`infra/`配下(nginx/docker-compose/mysql設定等)の変更はこのワークフローの対象外で、引き続き手動でのデプロイが必要です。

**静的ファイルのURL**: PHPが読むテーマは世代ごとのディレクトリ(`ikuty-theme-<sha>`)ですが、CSS/JS/画像のURLは、デプロイで変わらない`/wp-content/ikuty-theme-current/...?ver=<内容のハッシュ>`になります(`functions.php`の`ikuty_theme_asset_uri()`)。nginxは静的ファイルを1年・immutableでキャッシュしますが、内容が変わらない限りURLも変わらないため、デプロイ後も訪問者のブラウザのキャッシュが効きます。内容を変えたファイルだけ、`ver`が変わって取り直されます。

- `ikuty-theme-current`は`wp-content/`直下のシンボリックリンクで、現在の世代を指します(`themes/`の外なので、テーマ一覧にも世代の自動削除にも含まれません)
- 有効なテーマと`ikuty-theme-current`の指す先が違うとき(ロールバックでリンクを付け替え忘れたときなど)は、テーマが自動的に従来の世代付きURLに戻すので、CSSと画像がずれることはありません
- 削除された旧世代のURL(`/wp-content/themes/ikuty-theme-<旧sha>/...`)は、nginxが現在の世代の同じパスに振り向けます(Googleが保存したHTMLが参照していても404になりません)

**ロールバック**: 問題が発生した場合、直前のバージョンへ手動で切り戻せます。テーマの切替に加えて、静的ファイル用のリンクも付け替えてください。

```bash
docker compose exec wordpress wp theme list --path=/var/www/html --allow-root
# 上記で保持されている過去のバージョン(ikuty-theme-<sha>)を確認してから:
docker compose exec wordpress wp theme activate ikuty-theme-<過去のsha> --path=/var/www/html --allow-root
# 静的ファイル用のリンクも付け替える(volume名はwordpress-infra_wp_data)
docker run --rm -v wordpress-infra_wp_data:/var/www/html alpine:latest sh -c "
  cd /var/www/html/wp-content &&
  ln -sfn themes/ikuty-theme-<過去のsha> .ikuty-theme-current.tmp &&
  mv -fT .ikuty-theme-current.tmp ikuty-theme-current"
```

### 開発環境での使用

```bash
# HTTP環境（デフォルト）
./scripts/init-with-theme.sh

# 開発用バックアップ
./scripts/backup.sh
```

### カスタマイズポイント

- `themes/ikuty-theme/themes/original-theme/`: テーマファイル
- `nginx/sites-available/http.conf`: nginx設定
- `php/php-fpm.conf`: PHP-FPM設定
- `php/zzz-socket.conf`: PHP-FPM Unixソケット設定
- `php/uploads.ini`: ファイルアップロード設定
- `mysql/my.cnf`: MySQL設定
- `docker-compose.yml`: サービス設定

## アーキテクチャ

### 通信アーキテクチャ
```
┌─────────────────────────────────────────────────────────────┐
│                     Docker Network                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐    ┌──────────────┐    ┌─────────────┐     │
│  │    nginx    │    │  WordPress   │    │   MySQL     │     │
│  │  (Reverse   │◄──►│  (php-fpm)   │◄──►│    8.4      │     │
│  │   Proxy)    │    │              │    │             │     │
│  └─────────────┘    └──────────────┘    └─────────────┘     │
│        │                   │                               │
│        ▼                   ▼                               │
│  ┌─────────────┐    ┌──────────────┐                       │
│  │   Client    │    │ Unix Socket  │                       │
│  │(Port 80/443)│    │ (/var/run/   │                       │
│  └─────────────┘    │ php-fpm.sock)│                       │
│                     └──────────────┘                       │
└─────────────────────────────────────────────────────────────┘
```

### 性能最適化技術
- **Unixソケット通信**: nginx ↔ PHP-FPM間のTCPオーバーヘッド削減
- **PHP設定最適化**: 64MBファイルアップロード、256MBメモリ制限
- **MySQL 8.4**: 高速クエリ処理とnative password認証
- **ikuty-theme統合**: 自動テーマ統合によるデプロイメント効率化

## ライセンス

MIT License

## サポート

問題や質問がある場合は、[Issues](https://github.com/your-repo/issues) で報告してください。