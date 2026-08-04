#!/bin/bash

# WordPress初期設定スクリプト
# WP-CLIを使用してWordPressを自動設定します

set -e

# 設定ファイルから環境変数を読み込み
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# WordPressサイト設定
SITE_URL="http://localhost"
SITE_TITLE="WordPress Docker Site"
ADMIN_USER="admin"
ADMIN_PASS="admin_password_change_me"
ADMIN_EMAIL="admin@example.com"

echo "Starting WordPress setup..."

# WordPressコンテナにWP-CLIがインストールされているか確認
if ! docker compose exec wordpress wp --version --allow-root > /dev/null 2>&1; then
    echo "Installing WP-CLI..."
    docker compose exec wordpress sh -c "
        curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
        chmod +x wp-cli.phar
        mv wp-cli.phar /usr/local/bin/wp
    "
fi

# WordPressのインストール状況を確認
if docker compose exec wordpress wp core is-installed --path=/var/www/html --allow-root 2>/dev/null; then
    echo "WordPress is already installed."
else
    echo "Installing WordPress..."
    
    # WordPressのインストール
    docker compose exec wordpress wp core install \
        --path=/var/www/html \
        --url="$SITE_URL" \
        --title="$SITE_TITLE" \
        --admin_user="$ADMIN_USER" \
        --admin_password="$ADMIN_PASS" \
        --admin_email="$ADMIN_EMAIL" \
        --skip-email \
        --allow-root
    
    echo "WordPress installation completed!"
fi

# 基本設定の確認
echo "Checking WordPress configuration..."
docker compose exec wordpress wp core version --path=/var/www/html --allow-root
docker compose exec wordpress wp user list --path=/var/www/html --allow-root

# サイトの基本情報を表示
echo ""
echo "WordPress Site Information:"
echo "Site URL: $SITE_URL"
echo "Admin URL: $SITE_URL/wp-admin"
echo "Admin User: $ADMIN_USER"
echo "Admin Password: $ADMIN_PASS"
echo ""
echo "Database Information:"
echo "Database: $MYSQL_DATABASE"
echo "DB User: $MYSQL_USER"
echo ""
echo "WordPress setup completed successfully!"
echo "You can now access your WordPress site at: $SITE_URL"