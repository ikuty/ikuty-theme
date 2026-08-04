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
│   ├── nginx.conf
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

### バックアップ・復旧

```bash
# バックアップ作成
./scripts/backup.sh

# バックアップから復旧
./scripts/restore.sh /backups/wordpress_backup_YYYYMMDD_HHMMSS
```

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

# 毎週日曜日午前3時にバックアップ
0 3 * * 0 /path/to/ikuty-theme/infra/scripts/backup.sh
```

### ログ監視

```bash
# nginx アクセスログ
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

### ログファイルの場所

- nginx: `docker-compose logs nginx`
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
   - `wp theme activate`で切替(直近3世代を保持、それより古い世代は自動削除)
   - `https://ikuty.com/`へのヘルスチェック

`infra/`配下(nginx/docker-compose/mysql設定等)の変更はこのワークフローの対象外で、引き続き手動でのデプロイが必要です。

**ロールバック**: 問題が発生した場合、直前のバージョンへ手動で切り戻せます。

```bash
docker compose exec wordpress wp theme list --path=/var/www/html --allow-root
# 上記で保持されている過去のバージョン(ikuty-theme-<sha>)を確認してから:
docker compose exec wordpress wp theme activate ikuty-theme-<過去のsha> --path=/var/www/html --allow-root
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