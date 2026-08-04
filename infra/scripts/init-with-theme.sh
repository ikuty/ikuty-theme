#!/bin/bash

# WordPress + ikuty-theme 統合初期化スクリプト
# テーマのクローン、コンテナ起動、WordPressセットアップ、テーマ有効化を一括実行
# ボリューム方式対応版

set -e

echo "=== WordPress + ikuty-theme 統合初期化スクリプト ==="
echo "ボリューム方式でテーマを統合します"
echo ""

# 1. テーマをボリュームにセットアップ
echo "Step 1: Setting up ikuty-theme..."
./scripts/setup-theme-volume.sh

# 2. コンテナを再起動（新しいマウント設定を適用）
echo "Step 2: Restarting containers with theme mount..."
docker compose down
docker compose up -d

# 3. サービスが完全に起動するまで待機
echo "Step 3: Waiting for services to be ready..."
sleep 30

# 4. WordPressが既にインストールされているかチェック
echo "Step 4: Checking WordPress installation status..."
if docker compose exec wordpress wp core is-installed --path=/var/www/html --allow-root 2>/dev/null; then
    echo "WordPress is already installed."
else
    echo "WordPress not installed. Running WordPress setup..."
    ./scripts/wordpress-setup.sh
fi

# 5. テーマディレクトリが正しく配置されているかチェック
echo "Step 5: Verifying theme installation..."
echo "テーマディレクトリ内容確認:"
docker run --rm -v wordpress-infra_wp_data:/var/www/html alpine:latest ls -la /var/www/html/wp-content/themes/

echo ""
echo "WordPressコンテナでのテーマ確認:"
if docker compose exec wordpress ls /var/www/html/wp-content/themes/ikuty-theme 2>/dev/null; then
    echo "✓ ikuty-theme is properly installed"
    
    # テーマを有効化
    echo "Step 6: Activating ikuty-theme..."
    docker compose exec wordpress wp theme activate ikuty-theme --path=/var/www/html --allow-root
    
    # 現在のテーマを確認
    echo "Step 7: Verifying active theme..."
    ACTIVE_THEME=$(docker compose exec wordpress wp theme status --path=/var/www/html --allow-root | grep "Active theme" | awk '{print $3}')
    echo "Active theme: $ACTIVE_THEME"
    
else
    echo "✗ Error: ikuty-theme not found in themes directory"
    echo "WordPress themes directory contents:"
    docker compose exec wordpress ls -la /var/www/html/wp-content/themes/ || echo "Cannot access themes directory"
    echo ""
    echo "テーマディレクトリ内容:"
    docker run --rm -v wordpress-infra_wp_data:/var/www/html alpine:latest ls -la /var/www/html/wp-content/themes/
fi

# 6. 結果の表示
echo ""
echo "=== Initialization Complete ==="
echo "WordPress Site: http://localhost"
echo "Admin Panel: http://localhost/wp-admin"
echo "Admin User: admin"
echo "Admin Password: admin_password_change_me"
echo ""
echo "テーマ管理情報:"
echo "📁 WordPressデータボリューム: wordpress-infra_wp_data"
echo "📂 テーマパス: /var/www/html/wp-content/themes/"
echo "📂 プラグインパス: /var/www/html/wp-content/plugins/"
echo ""
echo "利用可能なテーマ:"
docker compose exec wordpress wp theme list --path=/var/www/html --allow-root

echo ""
echo "ディレクトリ管理コマンド:"
echo "  テーマ内容確認: docker run --rm -v wordpress-infra_wp_data:/var/www/html alpine:latest ls -la /var/www/html/wp-content/themes/"
echo "  プラグイン確認: docker run --rm -v wordpress-infra_wp_data:/var/www/html alpine:latest ls -la /var/www/html/wp-content/plugins/"
echo "  テーマ再配置: ./scripts/setup-theme-volume.sh"
echo ""
echo "✅ WordPress + ikuty-theme 統合初期化が完了しました！"